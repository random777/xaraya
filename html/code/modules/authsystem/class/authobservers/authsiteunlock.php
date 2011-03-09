<?php
/**
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * AuthSiteUnlock Event Observer
 *
 * This observer is notified when the site lock is disengaged (on AuthSiteUnlock)
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
sys::import('xaraya.structures.events.observer');
class AuthsystemAuthSiteUnlockObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'authsystem';

    private static $sitelock;
    // private variables used during configuration methods
    private $invalid;     // array of invalid fields
    private $fieldvalues; // the array of field values from input

    public function __construct()
    {
        // This listener uses the authsystem sitelock object for storage
        // for convenience, we get that when the object is constructed
        // NOTE: (Other modules supplying a listener should use their own storage for config options)
        sys::import('modules.authsystem.class.sitelock');
        self::$sitelock = SiteLock::getInstance();
    }

/**
 * Notify observer
 *
 * Notify is the only required method for this observer
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @param  object $subject the event subject
 * @throws none
 * @return void
**/
    public function notify(ixarEventSubject $subject)
    {
        // we only supply one option here, send an email when the site is unlocked
        if (self::$sitelock->unlocked_notify) {
            $admin = xarRoles::get(self::$sitelock->site_admin);
            $to_notify = array();
            // are we notifying admin?
            if (self::$sitelock->admin_notify) {
                $to_notify[$admin->getId()] = $admin->getEmail();
            }
            // are we notifying users and groups?
            if (!empty(self::$sitelock->access_list)) {
                foreach (self::$sitelock->access_list as $rid => $notify) {
                    // not marked for notification?
                    if (empty($notify)) continue;
                    $r = xarRoles::get($rid);
                    // not a role?
                    if (!$r) {
                        unset(self::$sitelock->access_list[$rid]);
                        continue;
                    // role is user?
                    } elseif ($r->isUser()) {
                        $to_notify[$r->getId()] = $r->getEmail();
                    // role is group?
                    } elseif ($g = $r->getUsers()) {
                        foreach ($g as $u) {
                            $to_notify[$u->getId()] = $u->getEmail();
                        }
                    }
                }
            }
            // do we have anyone to notify?
            if (!empty($to_notify)) {
                // Build the email
                $subject = xarML('Site Unlock Notification');
                $from = $admin->getEmail();
                $message = xarML('The site #(1) was unlocked at #(2) on #(3) by #(4)',
                    xarModVars::get('themes', 'SiteName'),
                    xarLocaleGetFormattedTime('long', self::$sitelock->unlocked_at),
                    xarLocaleGetFormattedDate('long', self::$sitelock->unlocked_at),
                    xarUserGetVar('name', self::$sitelock->unlocked_by));
                if (!empty(self::$sitelock->unlocked_mail)) {
                    $message .= "/n/n";
                    $message .= self::$sitelock->unlocked_mail;
                }
                foreach ($to_notify as $email) {
                    try {
                        xarMod::apiFunc('mail', 'admin', 'sendmail',
                            array(
                                'info' => $email,
                                'subject' => $subject,
                                'message' => $message,
                                'from' => $from,
                            ));
                    } catch (Exception $e) {
                        continue;
                    }
                }
            }
        }
    }

/**
 * Check observer config
 *
 * This method is called by AuthSiteLock::checkconfig() before update
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @param  object $subject the event subject
 * @throws none
 * @return bool true if observer validated succesfully
**/
    public function checkconfig(ixarEventSubject $subject)
    {
        // the calling method expects a boolean true on success
        return true;
    }

/**
 * Modify observer config
 *
 * This method is called by AuthSiteLock::modifyconfig()
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @param  object $subject the event subject
 * @throws none
 * @return array string templated output
**/
    public function modifyconfig(ixarEventSubject $subject)
    {
        $data = array();
        $data['unlocked_mail'] = self::$sitelock->unlocked_mail;
        $data['unlocked_notify'] = self::$sitelock->unlocked_notify;
        // the calling method expects templated output to be displayed within a form
        return xarTplModule('authsystem', 'authsiteunlock', 'modifyconfig', $data);
    }

/**
 * Update observer config
 *
 * This method is called by AuthSiteLock::updateconfig()
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @param  object $subject the event subject
 * @throws none
 * @return bool true if observer updated succesfully
**/
    public function updateconfig(ixarEventSubject $subject)
    {
        if (!xarVarFetch('authsystem_unlocked_notify', 'checkbox',
            $unlocked_notify, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('authsystem_unlocked_mail', 'pre:trim:str:1:',
            $unlocked_mail, '', XARVAR_NOT_REQUIRED)) return;
        self::$sitelock->unlocked_notify = $unlocked_notify;
        self::$sitelock->unlocked_mail = $unlocked_mail;
        // the calling method expects a boolean true on success
        return true;
    }
}
?>
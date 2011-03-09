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
 * AuthSiteLock Event Observer
 *
 * This observer is notified when the site lock is engaged (on AuthSiteLock)
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
sys::import('xaraya.structures.events.observer');
class AuthsystemAuthSiteLockObserver extends EventObserver implements ixarEventObserver
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
        // we only supply one option here, send an email when the site is locked
        // Notifications enabled ?
        if (self::$sitelock->locked_notify) {
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
                $subject = xarML('Site Lock Notification');
                $from = $admin->getEmail();
                $message = xarML('The site #(1) was locked at #(2) on #(3) by #(4)',
                    xarModVars::get('themes', 'SiteName'),
                    xarLocaleGetFormattedTime('long', self::$sitelock->locked_at),
                    xarLocaleGetFormattedDate('long', self::$sitelock->locked_at),
                    xarUserGetVar('name', self::$sitelock->locked_by));
                if (!empty(self::$sitelock->locked_mail)) {
                    $message .= "/n/n";
                    $message .= self::$sitelock->locked_mail;
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
 * the following methods are optional, and can be supplied to allow
 * the site lock listeners configuration to be managed from the authsystem module
 * these methods are all accessed in authsystem_admin_modifylogin when
 * the site lock configuration is being modified/updated
 * During an update, checkconfig() is called first,
 * if the data validates (for all observers) updateconfig() is called
 * otherwise, modifyconfig() is called
**/

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
        /**
         * The configuration here requires no validation, this is here
         * as an example of how one might handle validation
        **/
        /*
        // fetch input
        if (!xarVarFetch('authsystem_locked_mail', 'pre:trim:str:1:',
            $fields['locked_mail'], '', XARVAR_NOT_REQUIRED)) return;
        // do some validation, set an invalid message if it fails
        if (empty($fields['locked_mail']))
            $this->invalid['locked_mail'] = xarML('You must supply a mail message');
        // check form input is valid
        if (!empty($this->invalid)) {
            // store the input so it can be returned to the form with the invalid messages
            $this->fieldvalues = $fields;
            // let the subject know we failed
            return false;
        }
        */
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
        /**
         * The configuration here requires no validation, this is here
         * as an example of how one might handle validation
        **/
        // Check for invalid messages (from checkconfig)
        if (!empty($this->invalid)) {
            // redisplay the form input...
            $data = $this->fieldvalues;
            // pass through the invalid messages
            // (note: you need to handle these in your template)
            $data['invalid'] = $this->invalid;
        } else {
            $data = array();
            // configuration for this listener
            // send notifications?
            $data['locked_notify'] = self::$sitelock->locked_notify;
            // the message to append
            $data['locked_mail'] = self::$sitelock->locked_mail;
        }
        // the calling method expects templated output to be displayed within a form
        return xarTplModule('authsystem', 'authsitelock', 'modifyconfig', $data);
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
        // NOTE: module developers supplying observers should take
        // care not to use names already in use in the form, it's
        // recommended that they prefix any names with the observer
        // module name to avoid potential collisions
        if (!xarVarFetch('authsystem_locked_mail', 'pre:trim:str:1:',
            $locked_mail, '', XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('authsystem_locked_notify', 'checkbox',
            $locked_notify, false, XARVAR_NOT_REQUIRED)) return;
        self::$sitelock->locked_mail = $locked_mail;
        self::$sitelock->locked_notify = $locked_notify;

        // the calling method expects a boolean true on success
        return true;
    }
}
?>
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

    // private variables used during configuration methods
    private $invalid;     // array of invalid fields
    private $fieldvalues; // the array of field values from input

    public function __construct()
    {
        // This listener uses the authsystem sitelock object for storage
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
        // Purge enabled ?
        if (AuthSystem::$sitelock->locked_purge) {
            $access_list = array_keys(AuthSystem::$sitelock->getAccessList());
            if(!xarMod::apiFunc('roles','admin','clearsessions', $access_list)) {
                $msg = xarML('Could not clear sessions table');
                throw new Exception($msg);
            }        
        }
        // Notifications enabled ?
        if (AuthSystem::$sitelock->locked_notify) {            
            $access_list = AuthSystem::$sitelock->getAccessList();
            $admin = xarRoles::get(AuthSystem::$sitelock->site_admin);
            // are we notifying admin?
            if (AuthSystem::$sitelock->admin_notify) {
                $access_list[$admin->getId()] = array('id' => $admin->getId(),'notify' => $admin->getEmail());
            }
            // do we have anyone to notify?
            if (!empty($access_list)) {
                // Build the email
                $subject = xarML('Site Lock Notification');
                $from = $admin->getEmail();
                $message = xarML('The site #(1) was locked at #(2) on #(3) by #(4)',
                    xarModVars::get('themes', 'SiteName'),
                    xarLocaleGetFormattedTime('long', AuthSystem::$sitelock->locked_at),
                    xarLocaleGetFormattedDate('long', AuthSystem::$sitelock->locked_at),
                    xarUserGetVar('name', AuthSystem::$sitelock->locked_by));
                // append message (if any)
                if (!empty(AuthSystem::$sitelock->locked_mail)) {
                    $message .= "\n\n";
                    $message .= AuthSystem::$sitelock->locked_mail;
                }
                // if admin logins are enabled, supply the login url 
                if (AuthSystem::$security->login_state != AuthSystem::STATE_LOGIN_USER) {
                    $message .= "\n\n";
                    $message .= 'Login URL: ';
                    if (!empty(AuthSystem::$security->login_alias)) {
                        $message .= xarServer::getBaseURL().'index.php/authsystem/';
                        $message .= AuthSystem::$security->login_alias;
                    } else {
                        $message .= xarModURL('authsystem', 'admin', 'login', array(), false);
                    }
                }   
                foreach ($access_list as $user) {
                    if (empty($user['notify'])) continue;
                    // @TODO: bcc list? 
                    try {
                        xarMod::apiFunc('mail', 'admin', 'sendmail',
                            array(
                                'info' => $user['notify'],
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
            $data['locked_notify'] = AuthSystem::$sitelock->locked_notify;
            // the message to append
            $data['locked_mail'] = AuthSystem::$sitelock->locked_mail;
            $data['locked_purge'] = AuthSystem::$sitelock->locked_purge;
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
        if (!xarVarFetch('authsystem_locked_purge', 'checkbox',
            $locked_purge, false, XARVAR_NOT_REQUIRED)) return;
        AuthSystem::$sitelock->locked_mail = $locked_mail;
        AuthSystem::$sitelock->locked_notify = $locked_notify;
        AuthSystem::$sitelock->locked_purge = $locked_purge;

        // the calling method expects a boolean true on success
        return true;
    }
}
?>
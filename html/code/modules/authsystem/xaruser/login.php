<?php
/**
 * Log user in to system
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Authsystem module
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * @param   uname users name
 * @param   pass user password
 * @param   rememberme session set to expire
 * @param   redirecturl page to return user if possible
 * @return  true if status is 3
 * @raise   exceptions raised if status is 0, 1, or 2
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 */
function authsystem_user_login()
{
    sys::import('modules.authsystem.class.xarauth');
    // Run authentication against auth modules 
    $auth = xarAuth::authenticate();

    // see if we're locking users out after consecutive failed attempts
    // no cookies and site locked state are ignored
    if ((bool) xarModVars::get('authsystem', 'uselockout') && 
        $auth != xarAuth::USER_NOCOOKIES &&
        $auth != xarAuth::USER_LOCKEDOUT) {
        // Check for locked out user
        $unlockTime  = (int) xarSession::getVar('authsystem.login.lockedout');
        $lockouttime = xarModVars::get('authsystem','lockouttime') ? 
                       xarModVars::get('authsystem','lockouttime') : 15;        
        if ((time() < $unlockTime) && (xarModVars::get('authsystem','uselockout') == true))
            $auth = xarAuth::USER_TRIESEXCEEDED;
    }
    
    if (!empty($auth) && is_array($auth)) {
        // Authenticated user, attempt to log them in 
        if (xarAuth::login($auth['uname'], $auth['pass'], $auth['rememberme'], $auth['authmod'])) {        
            // The last login time is now tracked in /roles/xareventapi.php
            // User Home Pages are now handled by a non core module 
            // NOTE: We may have exited already if a UserLogin event triggered a redirect
            
            // Do standard redirects here if a module didn't already take care of it
            if (!xarVarFetch('redirecturl','pre:trim:str:1:254',$return_url, '', XARVAR_NOT_REQUIRED)) return;
            if (empty($return_url)) $return_url = xarServer::getBaseURL();
            xarController::redirect($return_url);
        } else {
            $auth = xarAuth::LOGIN_FAILED;
        }
    } 
        
    // If we're here, the user failed authentication
    
    // see if we're locking users out after consecutive failed attempts
    // No point doing this if cookies aren't set, or the site is locked 
    if ((bool) xarModVars::get('authsystem', 'uselockout') && 
        $auth != xarAuth::USER_NOCOOKIES &&
        $auth != xarAuth::USER_LOCKEDOUT) {
        $lockouttries = xarModVars::get('authsystem','lockouttries') ? 
                        xarModVars::get('authsystem','lockouttries') : 3;
        $attempts = (int) xarSession::getVar('authsystem.login.attempts');
        $attempts++;
        if ($attempts > $lockouttries) {
            $now = time();
            xarSession::setVar('authsystem.login.lockedout', $now + (60 * $lockouttime));
            xarSession::setVar('authsystem.login.attempts', 0);
            $auth = xarAuth::USER_TRIESEXCEEDED;
            // check if we're notifying admin of lock outs 
            if ((bool) xarModVars::get('authsystem', 'lockoutnotify') == true) {
                $admin = xarRoles::get(xarModVars::get('roles', 'admin'));
                $sitename = xarModVars::get('themes','SiteName');
                $forwarded = xarServer::getVar('HTTP_X_FORWARDED_FOR');
                $ipaddr = !empty($forwarded) ? preg_replace('/,.*/', '', $forwarded) : xarServer::getVar('REMOTE_ADDR');
                $subject = $sitename . ' User Locked Out';
                $message = 'The following visitor was locked out of site ' . $sitename . "\n\n";
                $message .= 'IP address: ' . $ipaddr . "\n\n";
                $message .= 'Locked out at: ' . xarLocaleGetFormattedTime('long', $now);
                $message .= ' on ' . xarLocaleGetFormattedDate('long', $now) . "\n\n";
                // send the email
                xarMod::apiFunc('mail','admin','sendmail', array(
                    'info' => $admin->getEmail(),
                    'subject' => $subject,
                    'message' => $message,
                    'from' => $admin->getEmail(),
                ));
            }
        } else {
            xarSession::setVar('authsystem.login.attempts', $attempts);
        }
    }    

    // Determine reason for failure and return an appropriate response 
    $pageTitle = xarML('Login Error');
    switch ($auth) {
        case xarAuth::LOGIN_FAILED:
            $errorTpl = array('layout' => 'login_failed');
            break;
        case xarAuth::USER_LOCKEDOUT:
            $errorTpl = array('layout' => 'site_lock');
            $pageTitle = xarML('Site Locked');
            break;
        case xarAuth::USER_TRIESEXCEEDED:
            $errorTpl = array('layout' => 'bad_tries_exceeded');
            break;
        case xarAuth::USER_NOCOOKIES:
            $errorTpl = array('layout' => 'no_cookies');
            break;
        case xarRoles::ROLES_STATE_DELETED:
            $errorTpl = array('layout' => 'account_deleted');
            break;        
        case xarRoles::ROLES_STATE_INACTIVE:
            $errorTpl = array('layout' => 'account_inactive');
            break;            
        case xarRoles::ROLES_STATE_PENDING:
            $errorTpl = array('layout' => 'account_pending');
            break;    
        case xarRoles::ROLES_STATE_NOTVALIDATED:
            xarController::redirect(xarModURL('roles', 'user', 'getvalidation'));
            break;
        case XARUSER_AUTH_DENIED:
        case XARUSER_AUTH_FAILED:
        case xarAuth::USER_NOTFOUND:
        default:
            $errorTpl = array('layout' => 'unknown');
            break;
    }    
    xarTplSetPageTitle($pageTitle);
    return xarTPLModule('authsystem', 'user', 'errors', $errorTpl);

}
?>

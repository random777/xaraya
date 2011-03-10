<?php
/**
 * Log a user in to the system
 *
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
 * @param   string  $uname username
 * @param   string  $pass password
 * @param   boolean $rememberme optionally persist session
 * @param   string  $return_url url to redirect user to after login (default base url)
 * @param   string  $phase the authentication phase [(form)|auth|callback]
 * @param   string  $authmod authentication module to use, optional in auth phase, required in callback phase
 * @return  mixed   string tpl data for form phase or invalid login, boolean true on success
 * @throws  none
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @author  Chris Powis <crisp@xaraya.com>
 */
/**
 * Note: this is the only supported login entry point, and is used by all
 * authentication methods (optionally supplied by auth modules)
**/
sys::import('modules.authsystem.class.auth');
function authsystem_user_login(Array $args=array())
{
    extract($args);
    
    if (!xarVarFetch('return_url', 'pre:trim:str:1:254',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;

    // get logged in users out of here
    if (xarUserIsLoggedIn()) {
        if (empty($return_url))
            $return_url = xarServer::getBaseURL();
        xarController::redirect($return_url);
    }

    if (!$_COOKIE) {
        return xarTplModule('authsystem','user','errors',
            array('layout' => 'no_cookies'));
    }
    
    // are we mitigating brute force login attempts?
    $maxattempts = xarModVars::get('authsystem', 'login.attempts');
    if (!empty($maxattempts)) {
        $lockedfor = xarModVars::get('authsystem', 'login.lockedout');
        $attempts = (int) xarSession::getVar('authsystem.login.attempts');
        if ($attempts >= $maxattempts) {
            $lockedat = xarSession::getVar('authsystem.login.lockedout');
            if (time() - $lockedat < 60 * $lockedfor) {
                // user locked out due to failed attempts
                return xarTplModule('authsystem','user','errors',
                    array('layout' => 'locked_out', 'lockouttime' => $lockedfor));
            } else {
                // reset lockout
                xarSession::setVar('authsystem.login.attempts', 0);
                xarSession::delVar('authsystem.login.lockedout');
            }
        }
    }

    if (!xarVarFetch('phase', 'pre:trim:lower:enum:auth:callback',
        $phase, 'form', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('authmod', 'pre:trim:str:1:',
        $authmod, null, XARVAR_NOT_REQUIRED)) return;
        
    // @checkme: are these string lengths correct?
    if (!xarVarFetch('uname', 'pre:trim:str:1:64',
        $uname, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('pass', 'pre:trim:str:1:254',
        $pass, '', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('rememberme', 'checkbox',
        $rememberme, false, XARVAR_NOT_REQUIRED)) return;

    $data = array();
    $invalid = array();

    // get the site lock object
    sys::import('modules.authsystem.class.sitelock');
    $sitelock = SiteLock::getInstance();
    
    // get the login subject
    $loginargs = array(
        'uname' => $uname, 
        'pass' => $pass, 
        'rememberme' => $rememberme,
        'return_url' => $return_url,
    );
    $login = xarAuth::getAuthSubject('AuthLogin', $loginargs);

    // we have a login attempt
    if ($phase != 'form') {
        // attempt authentication
        switch ($phase) {
            // Entry point for basic authentication
            case 'auth':
                // authenticate login against authmod(s) 
                $userid = $login->authenticate($authmod);
            break;
            
            // Entry point for callback based authentication
            case 'callback':
                // in callback phase we need the name of the auth module expecting a callback           
                if (empty($authmod)) {
                    $userid = xarAuth::AUTH_FAILED;
                } else {
                    $userid = $login->callback($authmod);
                }
            break;
            // Unknown phase
            default:
                $userid = xarAuth::AUTH_FAILED;
            break;
        }

        if ($userid == xarAuth::LAST_RESORT || $userid == xarAuth::AUTH_FAILED) {
            $state = $userid;
        } else {
            // get the user
            $role = xarRoles::get($userid);
            if (!$role || !$role->isUser()) {
                $state = xarAuth::AUTH_FAILED;
            } else {
                $state = $role->getState();
            }
        }

        switch ($state) {
    
            case xarRoles::ROLES_STATE_DELETED:
                return xarTplModule('authsystem','user','errors',
                    array('layout' => 'account_deleted'));
            break;

            case xarRoles::ROLES_STATE_INACTIVE:
                return xarTplModule('authsystem','user','errors',
                    array('layout' => 'account_inactive'));
            break;

            case xarRoles::ROLES_STATE_NOTVALIDATED:
                xarController::redirect(xarModURL('roles', 'user', 'getvalidation'));
            break;

            case xarRoles::ROLES_STATE_PENDING:
                return xarTplModule('authsystem','user','errors',
                    array('layout' => 'account_pending'));
            break;

            case xarRoles::ROLES_STATE_ACTIVE:
            case xarAuth::LAST_RESORT:

                if (!$sitelock->checkAccess($userid)) {
                    // site locked and this user isn't on the list
                    return xarTplModule('authsystem','user','errors',
                        array('layout' => 'site_locked', 'message'  => $sitelock->lockout_msg));
                }
                // ok to go ahead and log the user in 
                if (xarAuth::userLogin($userid, $rememberme)) {
                    // @todo: optional redirects (home page, landing page, etc) 
                    if (empty($return_url)) 
                        $return_url = xarServer::getBaseURL();
                    xarController::redirect($return_url);
                }
                // if login failed we fall through...
                    
            case xarAuth::AUTH_FAILED:
            default:
                // are we mitigating brute force login attempts?
                if (!empty($maxattempts)) {
                    $attempts = (int) xarSession::getVar('authsystem.login.attempts');
                    $attempts++;
                    xarSession::setVar('authsystem.login.attempts', $attempts);
                    if ($attempts >= $maxattempts) {
                        xarSession::setVar('authsystem.login.lockedout', time());
                        return xarTplModule('authsystem','user','errors',
                            array('layout' => 'bad_tries_exceeded', 'lockouttime' => $lockedfor));
                    }
                }
                // we don't want to reveal why here, since it poses a security risk
                $invalid['login'] = xarML('There was a problem logging in, please check your credentials');
                // @TODO: log failure?  
            break;
    
        }                  
    }
    
    // if we're here, either we're in form phase or we have an invalid login    
    // pass the data to the template    
    $data['loginform'] = $login->showform();
    $data['invalid'] = $invalid;
    $data['return_url'] = $return_url;
    $data['sitelock'] = $sitelock->getInfo();
        
    return $data;
}
?>
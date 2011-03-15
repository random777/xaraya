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
sys::import('modules.authsystem.class.authsystem');
function authsystem_user_login(Array $args=array())
{
    // save wasted processing, check for cookies first
    if (!$_COOKIE) {
        return xarTplModule('authsystem','user','errors',
            array('layout' => 'no_cookies'));
    }

    extract($args);
    
    // try for return_url from request (or from $args)
    if (!xarVarFetch('return_url', 'pre:trim:str:1:254',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;
    // try for referer if it's local
    if (empty($return_url) && xarController::isLocalReferer()) 
        $return_url = xarServer::getVar('HTTP_REFERER');
    // sanity check, have return_url, return_url isn't login or account
    if (empty($return_url) || preg_match('!login!', $return_url) || preg_match('!account!', $return_url))
        $return_url = xarServer::getBaseURL();

    // get logged in users out of here
    if (xarUserIsLoggedIn()) {
        xarController::redirect($return_url);
    }
//AuthSystem::$session->login_access = false;
    // Make sure user can access this form 
    if (!AuthSystem::$session->login_access) {
        // Check if current login state is admin 
        if (AuthSystem::$security->login_state != AuthSystem::STATE_LOGIN_USER) {
            // check if the site is locked and access is restricted
            if (AuthSystem::$sitelock->locked && !empty(AuthSystem::$sitelock->lockout_state)) {
                // user logins are disabled and access is restricted to this page
                // so we display the locked page template 
                return xarTplModule('authsystem','user','errors',
                    array('layout' => 'site_locked', 'message'  => AuthSystem::$sitelock->lockout_msg));
                $data['sitelock'] = AuthSystem::$sitelock->getInfo();
                return xarTplModule('authsystem', 'user', 'locked', $data);
            } else {
                // site is unlocked or there are no viewing restrictions 
                // user logins are disabled and we can assume we didn't come from
                // the admin entry point since login_access would have been set,
                // for security we return a 404 response
                $msg = xarML('The content requested was not found on this server');
                return xarResponse::notfound($msg);   
            }
        } 
        // user logins are in operation
        AuthSystem::$session->login_access = true;
    }

    // check if user has been locked out due to failed attempts  
    if (!AuthSystem::$security->checkAccess()) {
        // user locked out due to failed attempts
        return xarTplModule('authsystem','user','errors',
            array('layout' => 'locked_out', 'lockouttime' => AuthSystem::$security->lockout_period));
    }

    // Now fetch rest of input
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
    
    // build the subject args 
    $loginargs = array(
        'uname' => $uname, 
        'pass' => $pass, 
        'rememberme' => $rememberme,
        'return_url' => $return_url,
    );
    // get auth login object
    $login = AuthSystem::getAuthSubject('AuthLogin', $loginargs);

    // we have a login attempt
    if ($phase != 'form') {
        // attempt authentication
        switch ($phase) {
            // Entry point for authentication
            case 'auth':
                // authenticate login against authmod(s) 
                $userid = $login->authenticate($authmod);
            break;
            
            // Entry point for callback based authentication, accessed from  
            // /index.php?module=authsystem&func=login&phase=callback&authmod=authmod"
            case 'callback':
                // in callback phase we need the name of the auth module expecting a callback           
                if (empty($authmod)) {
                    $userid = AuthSystem::AUTH_FAILED;
                } else {
                    // the callback should redirect to the external handler which should return
                    // to /index.php?module=authsystem&func=login&phase=auth&authmod=authmod
                    // where the authmods authenticate handler will handle the response 
                    $userid = $login->callback($authmod);
                }
            break;
            // Unknown phase
            default:
                $userid = AuthSystem::AUTH_FAILED;
            break;
        }

        if ($userid == AuthSystem::LAST_RESORT || $userid == AuthSystem::AUTH_FAILED) {
            $state = $userid;
        } else {
            // get the user
            $role = xarRoles::get($userid);
            if (!$role || !$role->isUser()) {
                $state = AuthSystem::AUTH_FAILED;
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
            case AuthSystem::LAST_RESORT:

                if (!AuthSystem::$sitelock->checkAccess($userid)) {
                    // site locked and this user isn't on the list
                    return xarTplModule('authsystem','user','errors',
                        array('layout' => 'site_locked', 'message'  => AuthSystem::$sitelock->lockout_msg));
                }
                // ok to go ahead and log the user in 
                if (AuthSystem::userLogin($userid, $rememberme)) {
                    // @todo: optional redirects (home page, landing page, etc) 
                    if (empty($return_url)) 
                        $return_url = xarServer::getBaseURL();
                    xarController::redirect($return_url);
                }
                // if login failed we fall through...
                    
            case AuthSystem::AUTH_FAILED:
            default:
                // check auth security object access  
                if (!AuthSystem::$security->checkAccess(true)) {
                    // user locked out due to failed attempts
                    return xarTplModule('authsystem','user','errors',
                        array('layout' => 'bad_tries_exceeded', 'lockouttime' => AuthSystem::$security->lockout_period));
                }
                // we don't want to reveal why here, since it poses a security risk
                $invalid['login'] = xarML('There was a problem logging in, please check your credentials'); 
            break;
    
        }                  
    }
    
    
    // if we're here, either we're in form phase or we have an invalid login
    // get the login form object  
    $loginform = AuthSystem::getAuthSubject('AuthLoginForm', $loginargs);
    // pass the data to the template    
    $data['loginform'] = $loginform->showform();
    $data['invalid'] = $invalid;
    $data['return_url'] = $return_url;
    $data['sitelock'] = AuthSystem::$sitelock->getInfo();
        
    return $data;
}
?>
<?php
sys::import('modules.authsystem.class.authsystem');
function authsystem_admin_login(Array $args=array())
{
    extract($args);
    
    // try for return_url from request (or from $args)
    if (!xarVarFetch('return_url', 'pre:trim:str:1:254',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;
    // try for referer if it's local
    if (empty($return_url) && xarController::isLocalReferer()) 
        $return_url = xarServer::getVar('HTTP_REFERER');
    // sanity check, have return_url, return_url isn't the authsystem
    if (empty($return_url) || preg_match('!authsystem!', $return_url))
        $return_url = xarServer::getBaseURL();

    // get logged in users out of here
    if (xarUserIsLoggedIn()) {
        // redirect user 
        xarController::redirect($return_url);
    }

    // when coming from obfuscated url, this will already be set...
    if (!AuthSystem::$session->login_access) {
        if (AuthSystem::$security->login_state != AuthSystem::STATE_LOGIN_ADMIN)  {
            // admin logins are disabled, for security we return a 404 response
            $msg = xarML('The content requested was not found on this server');
            return xarResponse::notfound($msg);   
        } 
        // admin logins enabled, see if url is obfuscated
        if (!empty(AuthSystem::$security->login_alias)) {
            // theoretically our short controller will have made this redundant, but just in case...
            if (!preg_match('!'.AuthSystem::$security->login_alias.'!', xarServer::getCurrentURL())) {
                // admin logins are obfuscated, for security we return a 404 response
                $msg = xarML('The content requested was not found on this server');
                return xarResponse::notfound($msg);
            }
        }
        // User has access, set state to session 
        AuthSystem::$session->login_access = true;
    }
    // pass through the return_url 
    $args['return_url'] = urlencode($return_url);
    // hand off to user login UI 
    return xarMod::guiFunc('authsystem', 'user', 'login', $args);
}
?>
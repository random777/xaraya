<?php
/**
 * ServerRequest Subject Observer
 *
 * This subject is notified on completion of each server request, that is
 * after the main module is called, but before render
 *
 * This observer is responsible for handling authsystem configuration options 
**/
sys::import('modules.authsystem.class.authsystem');
sys::import('xaraya.structures.events.observer');
class AuthsystemServerRequestObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'authsystem';
    public function notify(ixarEventSubject $subject)
    {
        $sitelock = AuthSystem::$sitelock;
        // if the site isn't locked, we're done 
        if (!$sitelock->locked) return;

        // site is locked, see if current user is logged in...
        if (xarUserIsLoggedIn()) {
            // must be on access list...
            if ($sitelock->checkAccess(xarUserGetVar('id'))) return;
            // not on access list, see if we're purging logged in users
            if (!$sitelock->locked_purge) return;
            // we are purging users, log this user out now
            // todo: 
        }
        
        // Anonymous user from here...
        
        // logins disabled but site can still be viewed (default for Aruba/Jamaica pre 2.2)
        if (empty($sitelock->lockout_state)) return;
        
        // restricted to login page only
        $security = AuthSystem::$security;
        // see where we are
        $current_url = xarServer::getCurrentURL();    
        // are admin logins enabled? 
        if ($security->login_state != AuthSystem::STATE_LOGIN_USER) {
            // admin login, are we obfuscating?
            if (!empty($security->login_alias)) {
                // obfuscated login url
                $alias_url = xarServer::getBaseURL().'/index.php/authsystem/'.$security->login_alias;
                // are we at the obfuscated url?
                if ($alias_url == $current_url) return;
            }
        }
        // are we at login with a pass?
        if (AuthSystem::$session->login_access &&
            ($current_url == xarModURL('authsystem', 'admin', 'login') ||
             $current_url == xarModURL('authsystem', 'user', 'login'))
        ) return;
                
        $login_url = xarModURL('authsystem', 'user', 'login'); 
        if ($current_url != $login_url) 
            xarController::redirect($login_url);
                
    }
}
?>
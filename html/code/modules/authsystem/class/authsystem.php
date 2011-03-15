<?php
class AuthSystem extends xarEvents implements iAuthSystem
{

    // authentication states 
    const AUTH_FAILED = -1;
    const AUTH_DENIED = -2; // purpose, context, and usage of this need explaining
    const LAST_RESORT = -3;

    // login states 1 = activate user login UI, 2 = activate admin login UI
    const STATE_LOGIN_USER = 1;  // user login function is used for logins
    const STATE_LOGIN_ADMIN = 2; // admin login function is used for logins 
    
    // Cached configuration objects, decorated by init() method 
    public static $config;   // auth config object
    public static $security; // auth security object
    public static $sitelock; // auth sitelock object
    public static $session;  // auth session object
    
    // Cached subjects, decorated by getAuthSubject() method 
    protected static $auth_subjects; // auth subject objects
/**
 * Initialize the AuthSystem
**/    
    public static function init(&$args=array())
    {
        if (empty($args))
            $args = array('config', 'security', 'sitelock', 'session');
        foreach ($args as $var) {
            self::$$var = self::getVar($var);
        }
    }    
/**
 * Log authenticated user in
 * Moved here from xaraya.users
**/
    public static function userLogin($userid, $rememberme=false)
    {
        if (xarUserIsLoggedIn()) return true;
        if ($userid == self::LAST_RESORT ||
            xarConfigVars::get(null,'Site.Session.SecurityLevel') == 'High')
            $rememberme = false;
        if (!xarSession::setUserInfo($userid, $rememberme))
            return false;
        self::notify('UserLogin', $userid);
        xarSession::delVar('privilegeset');
        return true;
    }

/**
 * Log current user out
 * Moved here from xaraya.users
**/
    public static function userLogout()
    {
        if (!xarUserIsLoggedIn()) return true;
        // get the current userid to log out
        $userid = xarSessionGetVar('id');
        // Reset user session information
        if (!xarSession::setUserInfo(_XAR_ID_UNREGISTERED, false))
            return false;
        // User logged out successfully
        self::notify('UserLogout',$userid);
        xarSession::delVar('privilegeset');
        return true;
    }

/**
 * Get an instance of an AuthSystem event subject
**/
    public static function getAuthSubject($event, $args=null)
    {
        if (isset(self::$auth_subjects[$event])) {
            if (isset($args)) self::$auth_subjects[$event]->setArgs($args);
            return self::$auth_subjects[$event];
        }
        $info = self::getSubject($event);
        if (empty($info)) return;
        if (!self::fileLoad($info)) return;
        $module = xarMod::getName($info['module_id']);
        // define class (loadFile already checked it exists)
        $classname = ucfirst($module) . $info['event'] . "Subject";
        // NOTE: here we rely on the subject constructor attaching its own observers
        // All authsystem subjects are written this way :) 
        self::$auth_subjects[$event] = new $classname($args);
        return self::$auth_subjects[$event];
    }
/**
 * return array of login states suitable for dropdowns and validations
**/
    public static function getLoginStates()
    {
        $states = array(
            self::STATE_LOGIN_USER => array('id' => self::STATE_LOGIN_USER, 'name' => xarML('User Login')),
            self::STATE_LOGIN_ADMIN => array('id' => self::STATE_LOGIN_ADMIN, 'name' => xarML('Admin Login')),
        );
        return $states;
    }

    public static function getVar($name)
    {
        $vars = array('config', 'security', 'sitelock', 'session');
        if (!in_array($name, $vars)) return;
        if (!isset(self::$$name)) {
            try {
                sys::import("modules.authsystem.class.{$name}");
                $className = 'Authsystem'.ucfirst($name);
                self::$$name = $className::getInstance();
            } catch (Exception $e) {
                
            }
        }
        return self::$$name;
    }    
  
}

interface iAuthSystem
{
}
AuthSystem::init();
?>
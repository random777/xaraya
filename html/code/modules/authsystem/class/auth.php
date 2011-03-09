<?php
class xarAuth extends xarEvents
{

    const AUTH_FAILED = -1;
    const AUTH_DENIED = -2; // purpose, context, and usage of this need explaining
    const LAST_RESORT = -3;
/**
 * Log authenticated user in
 * Moved here from xaraya.users
**/
    public static function userLogin($userid, $rememberme=false)
    {
        if (xarUserIsLoggedIn()) return true;
        if ($userid == xarAuth::LAST_RESORT ||
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
 * Helper methods
 * Used by the AuthSystem
**/
/**
 * Get an instance of an auth event subject without observers
**/
    public static function getAuthObject($event, $args=null)
    {
        $info = self::getSubject($event);
        if (empty($info)) return;
        if (!self::fileLoad($info)) return;
        $module = xarMod::getName($info['module_id']);
        // define class (loadFile already checked it exists)
        $classname = ucfirst($module) . $info['event'] . "Subject";
        $subject = new $classname($args);
        return $subject;
    }
/**
 * Get in instance of an auth event subject with all observers attached
**/
    public static function getAuthSubject($event, $args=null)
    {
        $subject = self::getAuthObject($event, $args);
        if (empty($subject)) return;
        $obsinfo = static::getObservers($subject);
        if (!empty($obsinfo)) {
            foreach ($obsinfo as $obs) {
                // Attempt to load observer
                try {
                    if (!self::fileLoad($obs)) continue;
                    $obsmod = xarMod::getName($obs['module_id']);
                    $obs['module'] = $obsmod;
                    // define class (loadFile already checked it exists)
                    $obsclass = ucfirst($obsmod) . $obs['event'] . "Observer";
                    // attach observer to subject
                    $subject->attach(new $obsclass());
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        return $subject;
    }
/**
 * Get all modules observering an auth event subject
**/
    public static function getAuthObservers($event)
    {
        $subject = self::getAuthObject($event);
        // get observer info from subject
        $obsinfo = static::getObservers($subject);
        return $obsinfo;
    }
/**
 * @TODO: overload the getObservers method to allow ordering and selection of active auth modules
**/

}
?>
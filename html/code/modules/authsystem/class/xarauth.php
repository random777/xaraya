<?php
class xarAuth extends Object
{
    // Authentication failure codes
    const LOGIN_FAILED        = -20; // xarAuth::login() failed 
    const USER_NOTFOUND       = -21; // found no matches against any auth module
    const USER_LOCKEDOUT      = -23; // Locked out by site lock
    const USER_TRIESEXCEEDED  = -24; // Reached maximum attempts to log in
    const USER_NOCOOKIES      = -25; // Cookies are required 

    /**
    * Authenticate a set of user credentials for login
    *
    * @return mixed array of user info or int failure reason code
    **/    
    public static function authenticate($uname=null, $pass=null, $rememberme=null, $authmod=null)
    {
        // Check for cookie capability
        if (!$_COOKIE) {
            return xarAuth::USER_NOCOOKIES;
        }

        static $user;
        if (isset($user))
            if (is_numeric($user) || $user['uname'] == $uname && $user['pass'] == $pass) return $user;
        
        $args = array('uname' => $uname, 'pass' => $pass, 'rememberme' => $rememberme, 'authmod' => $authmod);
        // Authenticate against observers (Auth modules) 
        $auth = self::notify('Auth', $args);
        
        switch ($auth->state) {
            // User must be active or last resort admin if we're going to log them in at all 
            case xarRoles::ROLES_STATE_ACTIVE:
            case XARUSER_LAST_RESORT:
                // check the site lock, only if not last resort admin
                if ($auth->state != XARUSER_LAST_RESORT) {
                    $sitelock = @unserialize(xarModVars::get('authsystem', 'sitelock'));
                    if (!empty($sitelock) && is_array($sitelock)) {
                        if ($sitelock['locked']) {
                            $hasaccess = false;
                            // Check for designated site admin
                            $admin = xarRoles::get(xarModVars::get('roles', 'admin'));
                            if ($admin->getUser == $auth->uname) {
                                // designated admin always has access
                                $hasaccess = true;
                            } else {
                                foreach ($sitelock['lockaccess'] as $id => $role) {
                                    $r = xarRoles::get($id);
                                    if ($r->isUser() && $r->getUser() == $auth->uname) {
                                        $hasaccess = true;
                                    } else {
                                        $group = $r->getUsers();
                                        foreach ($group as $g) {
                                            if ($g->isUser() && $g->getUser() == $auth->uname) {
                                                $hasaccess = true;
                                                break;
                                            }
                                        }
                                    }
                                    if ($hasaccess) break;
                                }
                            }
                            if (!$hasaccess) {
                                return $user = xarAuth::USER_LOCKEDOUT;
                            }
                        }
                    }
                }
                return $user = $auth->getInfo();
                break;

            // All other states return the current state code             
            case xarRoles::ROLES_STATE_DELETED:
            case xarRoles::ROLES_STATE_INACTIVE:
            case xarRoles::ROLES_STATE_PENDING:
            case xarRoles::ROLES_STATE_NOTVALIDATED:
            case xarAuth::USER_NOTFOUND:
            // @TODO: replace these with xarAuth constants 
            case XARUSER_AUTH_DENIED:
            case XARUSER_AUTH_FAILED:
            default:
                return $user = $auth->state;
                break;  
        }      
        
    }

    /**
    * The function that actually logs a user into Xaraya
    **/
    public static function login($uname, $pass, $rememberme=false, $authmod=null)
    {
        if (xarUserIsLoggedIn()) return true;

        // authenticate the user based on credentials 
        $user = self::authenticate($uname, $pass, $rememberme, $authmod);
        
        // check the authenticate method returned a user array and not an error code 
        if (empty($user) || !is_array($user)) return false;
        
        // Log the user in        
        $userId = $user['id'];
        $authmod = $user['authmod'];
        $rememberme = $user['rememberme'];          

        // Set user session information
        // TODO: make this a class static in xarSession.php
        if (!xarSession_setUserInfo($userId, $rememberme))
            return false; // throw back

        // Set user auth module information
        $modInfo = xarMod::getBaseInfo($authmod);
        $modId = $modInfo['systemid'];
        // TODO: this should be inside roles module
        $dbconn   = xarDB::getConn();
        $xartable = xarDB::getTables();
        $rolestable = $xartable['roles'];
        try {
            $dbconn->begin();
            $query = "UPDATE $rolestable SET auth_module_id = ? WHERE id = ?";
            $stmt = $dbconn->prepareStatement($query);
            $stmt->executeUpdate(array($modId,$userId));
            $dbconn->commit();
        } catch (SQLException $e) {
            $dbconn->rollback();
            throw $e;
        }

        // Set session variables
        // Keep a reference to auth module that authenticates successfully
        xarSessionSetVar('authenticationModule', $authmod);
        xarSession::delVar('privilegeset');
        
        // User logged in successfully, trigger the proper event with the new userid
        xarEvents::trigger('UserLogin',$userId);
        return true;
    }

    /**
    * The function that actually logs the current user out of Xaraya
    **/
    public static function logout()
    {

        if (!xarUserIsLoggedIn()) {
            return true;
        }
        // get the current userid before logging out
        $userId = xarSessionGetVar('id');
        // Let authenticating module know a logout is in progress?
        /*
        $authmod = xarSessionGetVar('authenticationModule');
        $args = array('id' => $userId, 'authmod' => $authmod);
        self::notify('Logout', $args);
        */
        // Reset user session information
        $res = xarSession_setUserInfo(_XAR_ID_UNREGISTERED, false);
        if (!isset($res)) {
            return; // throw back
        }

        xarSessionDelVar('authenticationModule');

        // User logged out successfully, trigger the proper event with the old userid
        xarEvents::trigger('UserLogout',$userId);

        xarSession::delVar('privilegeset');
        return true;
    }    
    /**
    * Authenticate a users Xaraya credentials (username and password)
    **/
    public static function authenticate_user($uname, $pass)
    {
        if (empty($uname) || empty($pass)) return false;
        
        $dbconn = xarDB::getConn();
        $xartable = xarDB::getTables();

        // Get user information
        $rolestable = $xartable['roles'];
        $query = "SELECT id, pass FROM $rolestable WHERE uname = ?";
        $stmt = $dbconn->prepareStatement($query);

        $result = $stmt->executeQuery(array($uname));

        if (!$result->first()) {
            $result->close();
            return false;
        }

        list($id, $realpass) = $result->fields;
        $result->close();

        // Confirm that passwords match
        if (!xarUserComparePasswords($pass, $realpass, $uname, substr($realpass, 0, 2))) {
            return false;
        }
        
        return $id;
    }    
    
    /**
    * Notify observers that an authsystem event is in progress
    **/
    public static function notify($event, $args=array())
    {
        // Look for event specific file containing the subject class 
        $filePath = sys::code() . "modules/authsystem/class/authsystem_" . strtolower($event) . ".php";        
        $importPath = 'modules.authsystem.class.authsystem_'.strtolower($event);
        if (!file_exists($filePath)) {
            // fall back to generic collection of subject classes 
            $filePath = sys::code() . "modules/authsystem/class/authsystem.php";
            if (!file_exists($filePath)) return;
            $importPath = 'modules.authsystem.class.authsystem';
        }
        sys::import($importPath);
        // All authsystem event classes are named AuthsystemEventName 
        $subjectClass = 'Authsystem' . ucfirst($event);
        if (!class_exists($subjectClass)) return;
        // create a new event subject 
        $subject = new $subjectClass($args);
        // get event observers 
        $observers = self::getObservers($event);
        foreach ($observers as $className) {
            // attach event observer to subject            
            $subject->attach(new $className());
        }
        // notify event observers 
        $subject->notify();
        return $subject;
    
    }

    /**
    * Get authsystem event observers
    **/    
    public static function getObservers($event)
    {

        static $observers = array();
        if (isset($observers[$event])) return $observers[$event];
        $mods = xarMod::apiFunc('modules','admin','getlist',
            array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        $classes = array();
        foreach ($mods as $mod) {
            $modName = $mod['name'];
            $filePath = sys::code() . "modules/{$modName}/class/" . strtolower($event) . "_" . $modName . ".php";
            if (!file_exists($filePath)) continue;
            $importPath = "modules.{$modName}.class." . strtolower($event) . "_" . $modName;
            sys::import($importPath);
            $className = ucfirst($event) . ucfirst($modName);
            if (!class_exists($className)) continue;
            $classes[$modName] = $className;
        }
        $observers[$event] = $classes;
        return $observers[$event];   

    }

}
?>
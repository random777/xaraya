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
 * Storage and methods for AuthSystem session var object
**/
sys::import('modules.authsystem.class.authsystem');
sys::import('xaraya.structures.variableobject');
Class AuthsystemSession extends xarVariableObject
{
    // required properties, these are never stored
    protected static $instance;
    protected static $variable = 'auth.session';
    protected static $scope    = 'session';
    protected static $module   = 'authsystem';

    public $auth_by;                // authenticating module for this user (if logged in)
   
    public $login_attempts = 0;     // track number of login attempts
    public $login_lockedat = 0;     // track lockout time if attempts exceed limit
    public $login_access   = false; // flag to indicate if user has access to login 
    public $login_state;            // track the last logged login state (user or admin) 
    public $lockout_state;          // track the last lockout state (open/restricted)
    
    public function __construct()
    {
        self::__wakeup();
    }
    
    /**
     * This method is called when the object is unserialized (when getInstance() is called)
    **/
    public function __wakeup()
    {
        // reset access on login state change, or site locked and lockout state change...
        if (AuthSystem::$security->login_state != $this->login_state ||
            (AuthSystem::$sitelock->locked && AuthSystem::$sitelock->lockout_state != $this->lockout_state)) {
            $this->login_access = false;
            $this->login_state = AuthSystem::$security->login_state;
            $this->lockout_state = AuthSystem::$sitelock->lockout_state;
        }
    }


    public function __sleep()
    {
        // reset session for logged in user
        // @TODO: move this to UserLogin observer 
        if (xarUserIsLoggedIn()) {
            if ($this->login_access) {
                $this->login_attempts = 0;
                $this->login_lockedat = 0;
                $this->login_access = false;
            }        
        }
            
        return parent::__sleep();
    }
}
?>
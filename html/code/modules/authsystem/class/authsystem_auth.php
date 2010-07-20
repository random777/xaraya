<?php
/**
* Run authentication against authmodules
*
* This class is responsible for notifying authentication modules
* that user authentication is in progress.
**/
sys::import('modules.authsystem.class.authsystem');
class AuthsystemAuth extends Authsystem
{
    // default values
    // if a user is found, authenticating modules' update method should return values for these
    public $id = 0;
    public $uname = '';
    public $pass = null;
    public $authmod = null;
    public $rememberme = false;
    public $state = xarAuth::USER_NOTFOUND;

    public function __construct(Array $args=array())
    {
        parent::__construct($args);
    } 
    /**
    * Notify all auth_ modules 
    *
    * @return void
    */
    public function notify()
    {
        foreach($this->observers as $obs)
        {
            if (!empty($this->authmod) && $this->authmod != $obs->module) continue;
            // each observers update method should return bool false if user not found
            // or an array containing id, uname, pass and state for the user found
            // For an example see Auth_Authsystem class   
            // in /modules/authsystem/class/authsystem/auth_authsystem.php
            $args = $obs->update($this);
            if (empty($args) || !is_array($args)) continue;
            $args['authmod'] = $obs->module;
            // update properties from args            
            self::setArgs($args);
            self::refresh($this);
            break;         
        }
    }
}
?>
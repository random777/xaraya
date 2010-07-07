<?php
/**
 * Model for all authmodule classes
**/
class Authsystem_Auth extends Object
{
    public $module          = 'authsystem';
    public $name            = 'Authsystem Authentication (Default)';
    public $desc            = 'Authenticate using standard Xaraya methods';

    private $uname          = '';
    private $pass           = '';
    private $state          = XARUSER_AUTH_FAILED;
    private $userid         = null;
    private $rememberme     = 0;
    private $return_url     = '';

    // @TODO: option to set fields to auth against (eg email and/or uname)
    public $authfields  = array('uname');

    public function __construct()
    {
        // get settings
    }
/**
 * Show login form method
 * The default is to present username, password and rememberme checkbox in login form
 * If the authentication module returns a template here, it will be displayed after the form
 * Can be used, eg to present links to authenticate remotely (via twitter, facebook, etc...)
 * @TODO: allow admins to decide which modules to show in login form
**/
    public function showloginform()
    {
        return '';
    }
/**
 * @TODO: example of remote authentication method...
 * (see authsystem user remote and response functions)
 * Remote authentication notifier
 * Remote authentication services, like Twitter, Facebook, etc require
 * a series of redirects to authenticate a user, the remote
 * function provides a means to negotiate those redirects
 * response function provides a 'listener' for the response from the remote site
**/
    public function remote()
    {
        return true;
    }
/**
 * Remote authentication listener
**/
    public function response()
    {
        return true;
    }
/**
 * Authenticate method
 * This method is required by login functions
 * and must be supplied by all auth modules
 * Method is responsible for authenticating a user, and populating the
 * uname, pass, and state properties with the xaraya user values
 * @params none
 * @throws none
 * @return int the state of the found user or XARUSER_AUTH_FAILED
**/

    public function authenticate($uname, $pass, $rememberme=0, $return_url='')
    {
        if (empty($uname) || empty($pass)) return XARUSER_AUTH_FAILED;

        // Get and check last resort admin before going to db table
        $secret = @unserialize(xarModVars::get('privileges','lastresort'));
        if (!empty($secret) && is_array($secret)) {
            if ($secret['name'] == MD5($uname) && $secret['password'] == MD5($pass)) {
                $this->setState(XARUSER_LAST_RESORT);
                $this->setRememberMe(false);
            }
        }

        // now get the user
        $user = xarMod::apiFunc('roles','user','get', array('uname' => $uname));
        if (empty($user)) {
            // Check if user has been deleted.
            try {
                $user = xarMod::apiFunc('roles','user','getdeleteduser',
                                        array('uname' => $uname));
            } catch (xarExceptions $e) {
                //getdeleteduser raised an exception
            }
        }

        // Found a user in the db
        if (!empty($user)) {
            // Check that the passwords match if not last resort admin
            if ($this->getState() != XARUSER_LAST_RESORT) {
                $userId = $this->authenticate_user($uname, $pass);
            } else {
                $userId = XARUSER_LAST_RESORT;
            }
            // One authenticated user, set info
            if ($userId != XARUSER_AUTH_FAILED) {
                // don't over-ride state of last resort admin
                if ($this->getState() != XARUSER_LAST_RESORT)
                    $this->setState($user['state']);
                $this->setUname($uname);
                $this->setPass($pass);
                $this->setUserId($userId);
                $this->setRememberMe($rememberme);
                $this->setReturnURL($return_url);

                // let the calling function know we succeeded :)
                return $this->getState();
            }
        }
        // no-one authenticated
        return XARUSER_AUTH_FAILED;
    }
/**
 * Authenticate_user (validate xaraya credentials)
 * called by authenticate method in this class
 * can be used by other auth modules to validate a xaraya uname and password
 * @param string $uname name of user we're checking password for
 * $param string $pass password to check against the db
**/
    public function authenticate_user($uname, $pass)
    {
        // The existing api function checks for a password match
        return xarMod::apiFunc('authsystem', 'user', 'authenticate_user',
            array('uname' => $uname, 'pass' => $pass));
    }

/**
 * Login method
 * This method is called by the login function right after a user has successfully logged in
 * This allows the authenticating module to perform housekeeping after login,
 * eg, to set additional session vars or user vars
 * @param  int  $id xaraya user id of the logged in user
 * @return bool true on success
**/
    public function login($id)
    {
        return true;
    }
/**
 * Logout method
 * This method is calleb by the logout function right before a user logs out
 * This allows authenticating module to perform housekeeping at logout
 * @param  int  $id xaraya user id logging out
 * @return bool true on success
**/
    public function logout($id)
    {
        return true;
    }
/**
 * ModifyConfig method
 * used in authsystem_admin_modify
**/
    public function modifyconfig()
    {

    }
/**
 * UpdateConfig method
 * used in authsystem_admin_update
**/
    public function updateconfig()
    {

    }
/**
 * Getters and setters for private properties
**/
    protected function setPass($pass)
    {
        $this->pass = $pass;
    }
    public function getPass()
    {
        return $this->pass;
    }
    protected function setUname($uname)
    {
        $this->uname = $uname;
    }
    public function getUname()
    {
        return $this->uname;
    }
    protected function setState($state)
    {
        $this->state = $state;
    }
    public function getState()
    {
        return $this->state;
    }
    protected function setRememberMe($rememberme)
    {
        $this->rememberme = $rememberme;
    }
    public function getRememberMe()
    {
        return $this->rememberme;
    }
    protected function setReturnURL($return_url)
    {
        $this->return_url = $return_url;
    }
    public function getReturnURL()
    {
        return $this->return_url;
    }
    protected function setUserId($userid)
    {
        $this->userid = $userid;
    }
    public function getUserId()
    {
        return $this->userid;
    }
    public function getInfo()
    {
        return array(
            'id' => $this->getUserId(),
            'uname' => $this->getUname(),
            'pass' => $this->getPass(),
            'state' => $this->getState(),
            'rememberme' => $this->getRememberMe(),
            'return_url' => $this->getReturnURL(),
            'authmodule' => $this->module,
        );
    }
}
?>
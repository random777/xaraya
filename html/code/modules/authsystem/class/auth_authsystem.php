<?php
/**
* Authenticate in Authsystem (standard Xaraya authentication)
**/
class AuthAuthsystem extends Object implements SplObserver
{

    public $module = 'authsystem';  // name of this authentication module
    /**
    * Update
    *
    * Respond to AuthsystemAuth::notify()
    * @param AuthsystemAuth object implementing SplSubject interface
    * @return mixed array containing id, uname, pass, and state for authenticated user
    *               or bool false if no matches 
    **/    
    public function update(SplSubject $subject)
    {
        // Attempt to fetch required params from input (showloginform/login block)
        // Fall back to AuthsystemAuth properties 
        if (!xarVarFetch('uname', 'pre:trim:str:1:64', $uname, $subject->uname, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('pass', 'pre:trim:str:1:254', $pass, $subject->pass, XARVAR_DONT_SET)) return;
        if (!xarVarFetch('rememberme', 'checkbox', $rememberme, $subject->rememberme, XARVAR_NOT_REQUIRED)) return;

        if (empty($uname) || empty($pass)) return false;

        // get the user
        $user = xarMod::apiFunc('roles','user','get', array('uname' => $uname));
        // check for last resort admin, and set state and id if required 
        $secret = @unserialize(xarModVars::get('privileges','lastresort'));
        if (!empty($secret) && is_array($secret)) {
            if ($secret['name'] == MD5($uname) && $secret['password'] == MD5($pass)) {
                $user['state'] = $user['id'] = XARUSER_LAST_RESORT;
                $rememberme = 0;  // Don't remember last resort admin's session 
            }
        }

        if (empty($user)) {
            // Check if user has been deleted.
            try {
                $user = xarMod::apiFunc('roles','user','getdeleteduser',
                                        array('uname' => $uname));
            } catch (xarExceptions $e) {
                //getdeleteduser raised an exception
            }
        }
        
        if (empty($user)) return false;
        
        if (!xarAuth::authenticate_user($uname, $pass)) return false;
        
        // return array of details (the Xaraya user to login) 
        return array(
            'id' => $user['id'], 
            'uname' => $user['uname'], 
            'pass' => $pass, 
            'rememberme' => $rememberme,
            'state' => $user['state']
           );

    }
}
?>
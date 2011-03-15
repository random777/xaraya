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
 * AuthSecurity ModVar Object
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
sys::import('modules.authsystem.class.authsystem');
sys::import('xaraya.structures.variableobject');
Class AuthsystemSecurity extends xarVariableObject
{
    // required properties, these are never stored
    protected static $instance;
    protected static $variable = 'authsecurity';
    protected static $scope    = 'module';
    protected static $module   = 'authsystem';

    public $login_template    = '';    // alternative user-login-{$template}.xt to use
    public $login_state       = AuthSystem::STATE_LOGIN_USER;
    public $login_alias       = '';    // optional alias to further obfuscate admin logins 
    
    public $login_attempts = 3;     // maximum login attempts before lockout
    public $lockout_period = 15;    // period to lockout for when attempts exceeded
    public $lockout_notify = false; // optionally send email to admin detailing lockout (time, ip and uname)
    public $log_attempts   = false; // optionally log failed attempts (time, ip and uname) 

    public function checkAccess($newattempt=false)
    {
        // empty login attempts = unlimited, pass through 
        if (empty($this->login_attempts)) return true;
        // get current attempt count
        $attempts = AuthSystem::$session->login_attempts;

        $now = time();
        if ($this->log_attempts || $this->lockout_notify) {
            $ip = $this->getIP();
            $time = xarLocale::getFormattedTime('long', $now);
            $date = xarLocale::getFormattedDate('long', $now);
        }
        
        // is this a new attempt? (called when auth failed)         
        if ($newattempt) {
            // incremement attempt count
            AuthSystem::$session->login_attempts++;
            // are we logging failed attempts ?
            if ($this->log_attempts) {
                $message = xarML('AuthSecurity: Failed login (attempt #(1)) from IP #(2) at #(3) on #(4)',$attempts,$ip, $time, $date);
                xarLogMessage($message);             
            }
        }
        
        // not reached the limit, pass through 
        if (AuthSystem::$session->login_attempts < $this->login_attempts) return true;

        // at the limit, check if we already locked this session
        $islocked = AuthSystem::$session->login_lockedat;
        
        // if not locked, lock now
        if (empty($islocked)) {
            // set lock time 
            $islocked = $now;
            AuthSystem::$session->login_lockedat = $islocked;
            if ($this->lockout_notify || $this->log_attempts) {
                $message = xarML('AuthSystem Security: User authenticating from IP #(1) was locked out at #(2) on #(3) for #(4) minutes after #(5) consecutive failed login attempts', $ip, $time, $date, $this->lockout_period, $this->login_attempts);            
                // notify admin of lockout
                if ($this->lockout_notify) {
                    $admin = xarRoles::get(xarModVars::get('roles', 'admin'));
                    $email = $from = $admin->getEmail();
                    $subject = xarModVars::get('themes', 'SiteName')." :: Lockout Notification";
                    try {
                        xarMod::apiFunc('mail', 'admin', 'sendmail',
                            array(
                                'info' => $email,
                                'subject' => $subject,
                                'message' => $message,
                                'from' => $from,
                            ));
                    } catch (Exception $e) {
                        
                    }                
                }
                // log lockout
                if ($this->log_attempts)
                    xarLogMessage($message);
            }
            // throw back 
            return false;
        }        

        // check if we're still in the lockout period 
        if ( (time() - $islocked) < (60 * $this->lockout_period) ) {
            // throw back 
            return false;
        }

        // if we're here, no longer locked out, reset session
        AuthSystem::$session->login_attempts = 0;
        AuthSystem::$session->login_lockedat = 0;
        
        // pass through
        return true;
    }

    public function getIP()
    {
        // Get  client IP 
        $forwarded = xarServer::getVar('HTTP_X_FORWARDED_FOR');
        if (!empty($forwarded)) {
            $ipAddress = preg_replace('/,.*/', '', $forwarded);
        } else {
            $ipAddress = xarServer::getVar('REMOTE_ADDR');
        }
        return $ipAddress;
    }

    public function getInfo()
    {
        return $this->getPublicProperties();
    }

    public function __wakeup()
    {
    }

    public function __sleep()
    {
        return parent::__sleep();
    }

}
?>
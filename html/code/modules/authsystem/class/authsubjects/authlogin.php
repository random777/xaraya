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
 * AuthLogin event subject
 *
 * These event methods are raised by authsystem_user_login
 *
 * NOTE: observers must supply at least the authenticate method, the others are optional
**/
// The base AuthSubject class supplies all the methods we need 
sys::import('modules.authsystem.class.authsubjects.authsubject');
class AuthsystemAuthLoginSubject extends AuthsystemAuthSubject implements ixarEventSubject
{
    protected $subject = 'AuthLogin';

    public function showform()
    {
        $output = array();
        // notify observers that the login form is being displayed 
        foreach ($this->observers as $obs) {
            try {
                $result = $obs->showform($this);
                // observers should return templated output
                if (!empty($result) && is_string($result))
                    // put result in output array, keyed by observer module 
                    $output[$obs->module] = $result;
            } catch (Exception $e) {
                continue;
            }
        }
        return $output;  
    }

    public function showformblock()
    {
        $output = array();
        // notify observers that the login form is being displayed 
        foreach ($this->observers as $obs) {
            try {
                $result = $obs->showformblock($this);
                // observers should return templated output
                if (!empty($result) && is_string($result))
                    // put result in output array, keyed by observer module 
                    $output[$obs->module] = $result;
            } catch (Exception $e) {
                continue;
            }
        }
        return $output;  
    }

    public function authenticate($authmod=null)
    {
        foreach ($this->observers as $obs) {
            try {
                if (!empty($authmod) && $authmod != $obs->module) continue;
                $result = $obs->authenticate($this);
                if (empty($result) || $result == xarAuth::AUTH_FAILED) continue;
                // return the result from the first observer that didn't fail to authenticate
                return $result;
            } catch (Exception $e) {
                continue;
            }
        }
        return xarAuth::AUTH_FAILED;    
    }
    
    public function callback($authmod)
    {
        foreach ($this->observers as $obs) {
            try {
                if (!empty($authmod) && $authmod != $obs->module) continue;
                $result = $obs->callback($this);
                if (empty($result) || $result == xarAuth::AUTH_FAILED) continue;
                // return the result from the first observer that didn't fail to authenticate
                return $result;
            } catch (Exception $e) {
                continue;
            }
        }
        return xarAuth::AUTH_FAILED;     
    }

}
?>
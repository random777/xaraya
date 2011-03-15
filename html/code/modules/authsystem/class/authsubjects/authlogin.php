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
    
    protected $auth_mods;

/**
 * AuthLogin Subject constructor
 * This subject is responsible for attaching its own observers ;)
 * Here we attach all available AuthLogin observers and their info
**/ 
    public function __construct($args=null)
    {
        parent::__construct($args);
        // get all AuthLogin subject observers
        $auth_mods = AuthSystem::getObservers($this);        
        // get the configured AuthLogin observer order
        $obs_order = AuthSystem::$config->auth_order;
        if (empty($obs_order)) {
            $obs_order = array_keys($auth_mods);
        } else {
            $obs_order += array_keys($auth_mods);
        }
        // get the configured AuthLogin observer states
        $obs_state = AuthSystem::$config->auth_active;
        // attach configured observers 
        foreach ($obs_order as $obs_mod) {
            // module must exist
            if (!isset($auth_mods[$obs_mod])) continue;
            // Attempt to load observer            
            $obs = $auth_mods[$obs_mod];
            try {
                if (!AuthSystem::fileLoad($obs)) continue;
                $obsmod = xarMod::getName($obs['module_id']);
                $obs['module'] = $obsmod;
                // define class (loadFile already checked it exists)
                $className = ucfirst($obsmod) . $obs['event'] . "Observer";
                $obsclass = new $className();
                // set extra info about this observer 
                $obs['has_callback'] = method_exists($obsclass, 'callback');
                $obs['has_authenticate'] = method_exists($obsclass, 'authenticate');
                $obs['is_active'] = !isset($obs_state[$obs_mod]) || !empty($obs_state[$obs_mod]);
                // attach observer to subject
                $this->attach($obsclass);
                // add observer info to subject 
                $this->auth_mods[$obs_mod] = $obs;
            } catch (Exception $e) {
                continue;
            }            
        }   
                      
    }

/**
 * Notify observer(s) that we're in authenticate phase
**/   
    public function authenticate($authmod=null)
    {
        foreach ($this->observers as $obs) {
            try {
                if (!empty($authmod) && $authmod != $obs->module) continue;
                // only active modules allowed here
                if (empty($this->auth_mods[$obs->module]['is_active']) ||
                    empty($this->auth_mods[$obs->module]['has_authenticate'])) continue;
                $result = $obs->authenticate($this);
                if (empty($result) || $result == AuthSystem::AUTH_FAILED) continue;
                // keep track of authenticating module 
                AuthSystem::$session->auth_by = $obs->module;
                AuthSystem::$session->auth_userid = $result;
                return $result;
            } catch (Exception $e) {
                continue;
            }
        }
        return AuthSystem::AUTH_FAILED;    
    }

    public function getAuthModules()
    {
        return $this->auth_mods;
    }    

/**
 * Notify specified observer that we're in callback phase
**/            
    public function callback($authmod)
    {
        // get configured observer states
        $states = AuthSystem::$config->auth_active;
        foreach ($this->observers as $obs) {
            try {
                if (!empty($authmod) && $authmod != $obs->module) continue;
                // only active modules allowed here
                if (empty($states[$obs->module])) continue;
                $result = $obs->callback($this);
                if (empty($result) || $result == AuthSystem::AUTH_FAILED) continue;
                // return the result from the first observer that didn't fail to authenticate
                return $result;
            } catch (Exception $e) {
                continue;
            }
        }  
        return AuthSystem::AUTH_FAILED; 
    }

/**
 * Notify specified observer that the user was logged in
 * NOTE: This fires *before* the UserLogin event to allow
 * the authenticating observer to do any clean up and post log-in operations
**/     
    public function login($authmod)
    {
        // get configured observer states
        $states = AuthSystem::$config->auth_active;
        foreach ($this->observers as $obs) {
            try {
                if (!empty($authmod) && $authmod != $obs->module) continue;
                // only active modules allowed here
                if (empty($states[$obs->module])) continue;
                $obs->login($this);
            } catch (Exception $e) {
                continue;
            }
        }
    }

}
?>
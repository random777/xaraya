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
class AuthsystemAuthLoginFormSubject extends AuthsystemAuthSubject implements ixarEventSubject
{
    protected $subject = 'AuthLoginForm';
    protected $auth_mods;
/**
 * AuthLogin Subject constructor
 * This subject is responsible for attaching its own observers ;)
 * Here we attach all available AuthLogin observers and their info
 * using the order defined in authsystem_admin_authentication [Login Form] tab
**/ 
    public function __construct($args=null)
    {
        parent::__construct($args);
        // get AuthLoginForm subject observers
        $auth_mods = AuthSystem::getObservers($this);
        // get config 
        $config = AuthSystem::$config;
        // get the AuthLoginForm observer order
        $obs_order = $config->form_order;
        // merge any missing auth_mods 
        $obs_order = empty($obs_order) ? array_keys($auth_mods) : $obs_order += array_keys($auth_mods);
        // get the AuthLoginForm observer states
        $obs_state = $config->form_active;
        // get the AuthLogin observer states 
        $auth_state = $config->auth_active;
        foreach ($obs_order as $key => $obs_mod) {
            // module must exist
            if (!isset($auth_mods[$obs_mod])) {
                unset($obs_order[$key]);            
                continue;
            }
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
                $obs['has_showform'] = method_exists($obsclass, 'showform');
                $obs['has_showformblock'] = method_exists($obsclass, 'showformblock');
                // we set all unknown auth modules active, otherwise use current setting
                $obs['is_active'] = !isset($auth_state[$obs_mod]) || !empty($auth_state[$obs_mod]);
                // only show form if auth module is active and form state is display
                $obs['is_displayed'] = $obs['is_active'] && !empty($obs_state[$obs_mod]);
                $obs['upurl'] = !empty($key) && isset($obs_order[$key-1]);
                $obs['downurl'] = $key < count($obs_order)-1 && isset($obs_order[$key+1]);
                // attach observer to subject
                $this->attach($obsclass);
                // add observer info to subject 
                $this->auth_mods[$obs_mod] = $obs;
            } catch (Exception $e) {
                continue;
            }           
        }
        // synch lists
        $config->form_active = $obs_state;
        $config->form_order = $obs_order;
    }

    public function showform()
    {
        $output = array();
        // notify observers that the login form is being displayed 
        foreach ($this->observers as $obs) {
            try {
                // only notify observers set active in authsystem_admin_authentication [Login Form] tab
                if (empty($this->auth_mods[$obs->module]['is_displayed'])) continue;
                $result = $obs->showform($this);
                // observers should return a string of templated output or an array of tpldata
                if (!empty($result)) {
                    if (is_string($result)) {
                        // put result string in output array, keyed by observer module 
                        $output[$obs->module] = $result;
                    } elseif (is_array($result)) {
                        // pass array to template, and put resulting string in output array 
                        $tplmodule = !empty($obs->tplmodule) ? $obs->tplmodule : $obs->module;
                        $tplbase   = !empty($obs->tplbase) ? $obs->tplbase : strtolower($this->getSubject());
                        $template  = !empty($obs->tplform) ? $obs->tplform : 'showform';
                        $output[$obs->module] = xarTplModule($tplmodule, $tplbase, $template, $result);
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
        return $output;  
    }

    public function getAuthModules()
    {
        return $this->auth_mods;
    } 

    public function showformblock()
    {
        $output = array();
        // notify observers that the login form is being displayed 
        foreach ($this->observers as $obs) {
            try {
                // only notify active observers
                if (empty($this->auth_mods[$obs->module]['is_displayed'])) continue;
                $result = $obs->showformblock($this);
                // observers should return templated output
                if (!empty($result)) {
                    if (is_string($result)) {
                        // put result string in output array, keyed by observer module 
                        $output[$obs->module] = $result;
                    } elseif (is_array($result)) {
                        // pass array to template, and put resulting string in output array 
                        $tplmodule = !empty($obs->tplmodule) ? $obs->tplmodule : $obs->module;
                        $tplbase   = !empty($obs->tplbase) ? $obs->tplbase : strtolower($this->getSubject());
                        $template  = !empty($obs->tplblock) ? $obs->tplblock : 'showformblock';
                        $output[$obs->module] = xarTplModule($tplmodule, $tplbase, $template, $result);
                    }
                }
            } catch (Exception $e) {
                continue;
            }
        }
        return $output;  
    }

}
?>
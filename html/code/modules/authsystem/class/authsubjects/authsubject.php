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
 * Base AuthSubject Class
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
sys::import('modules.authsystem.class.authsystem');
sys::import('xaraya.structures.events.subject');
abstract class AuthsystemAuthSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'AuthSubject';
    protected $auth_modules = array();
    protected $invalid = array();
    protected $output = array();

/**
 * Auth subjects are responsible for attaching their own observers ;)
**/ 
    public function __construct($args=null)
    {
        parent::__construct($args);
        $obs_mods = AuthSystem::getObservers($this);
        foreach ($obs_mods as $obs_mod => $obs) {
            try {
                if (!AuthSystem::fileLoad($obs)) continue;
                $obsmod = xarMod::getName($obs['module_id']);
                $obs['module'] = $obsmod;
                // define class (loadFile already checked it exists)
                $className = ucfirst($obsmod) . $obs['event'] . "Observer";
                $obsclass = new $className();
                // attach observer to subject
                $this->attach($obsclass);
                // keep track of the observer info 
                $this->auth_modules[$obsclass->module] = $obs;
            } catch (Exception $e) {
                continue;
            }            
        }                         
    }

/**
 * Notify observers
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @throws none
 * @return void
**/
    public function notify()
    {
        // Notify observers 
        return parent::notify();
    }
    
    final public function getAuthObservers()
    {
        return $this->auth_observers;
    }

    final public function getObservers()
    {
        return $this->observers;
    }

/**
 * Modify observer config
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @throws none
 * @return array of observer responses
**/
    public function modifyconfig($authmod=null)
    {
        // notify observers that the site lock config is being modified
        foreach ($this->observers as $obs) {
            if (isset($authmod) && $obs->module != $authmod) continue;
            if (!method_exists($obs, 'modifyconfig')) continue;
            try {
                $result = $obs->modifyconfig($this);
                // modifyconfig method should return templated output
                if (!empty($result) && is_string($result))
                    // put result in output array, keyed by observer module
                    $this->output[$obs->module] = $result;
            } catch (Exception $e) {
                continue;
            }
        }
        return $this->output;
    }

/**
 * Check observer config
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @throws none
 * @return bool true if all observers validated succesfully
**/
    public function checkconfig($authmod=null)
    {
        // notify observers that the site lock config is being validated
        foreach ($this->observers as $obs) {
            try {
                if (isset($authmod) && $obs->module != $authmod) continue;
                if (!method_exists($obs, 'checkconfig')) continue;
                $result = $obs->checkconfig($this);
                // checkconfig method should return bool
                if ($result) continue;
                $this->invalid[$obs->module] = true;
            } catch (Exception $e) {
                continue;
            }
        }
        return empty($this->invalid);
    }

/**
 * Update observer config
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @throws none
 * @return bool true if all observers updated succesfully
**/
    public function updateconfig($authmod=null)
    {
        $isvalid = true;
        // notify observers that the site lock config is being updated
        foreach ($this->observers as $obs) {
            if (isset($authmod) && $obs->module != $authmod) continue;
            if (!method_exists($obs, 'updateconfig')) continue;
            try {
                $result = $obs->updateconfig($this);
                // updateconfig method should return bool
                if ($result) continue;
                $this->invalid[$obs->module] = true;
            } catch (Exception $e) {
                continue;
            }
        }
        return empty($this->invalid);
    }

    public function getInvalid()
    {
        return $this->invalid;
    }

}
?>
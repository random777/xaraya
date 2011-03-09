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
sys::import('xaraya.structures.events.subject');
abstract class AuthsystemAuthSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'AuthSubject';

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
        // Notify listeners that the site was locked
        return parent::notify();
    }

/**
 * Modify observer config
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @throws none
 * @return array of observer responses
**/
    public function modifyconfig()
    {
        $output = array();
        // notify observers that the site lock config is being modified
        foreach ($this->observers as $obs) {
            try {
                $result = $obs->modifyconfig($this);
                // modifyconfig method should return templated output
                if (!empty($result) && is_string($result))
                    // put result in output array, keyed by observer module
                    $output[$obs->module] = $result;
            } catch (Exception $e) {
                continue;
            }
        }
        return $output;
    }

/**
 * Check observer config
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @throws none
 * @return bool true if all observers validated succesfully
**/
    public function checkconfig()
    {
        $isvalid = true;
        // notify observers that the site lock config is being validated
        foreach ($this->observers as $obs) {
            try {
                $result = $obs->checkconfig($this);
                // checkconfig method should return bool
                if ($result) continue;
                // only takes one observer to fail, but we still have to notify the rest
                $isvalid = false;
            } catch (Exception $e) {
                continue;
            }
        }
        return $isvalid;
    }

/**
 * Update observer config
 *
 * @author Chris Powis <crisp@xaraya.com>
 * @params none
 * @throws none
 * @return bool true if all observers updated succesfully
**/
    public function updateconfig()
    {
        $isvalid = true;
        // notify observers that the site lock config is being updated
        foreach ($this->observers as $obs) {
            try {
                $result = $obs->updateconfig($this);
                // updateconfig method should return bool
                if ($result) continue;
                // only takes one observer to fail, but we still have to notify the rest
                $isvalid = false;
            } catch (Exception $e) {
                continue;
            }
        }
        return $isvalid;
    }

}
?>
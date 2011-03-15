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
sys::import('xaraya.structures.events.observer');
abstract class AuthsystemAuthObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'authsystem';

    protected $invalid = array();
    
    public function setInvalid($property, $msg)
    {
        if (!isset($this->$property)) return false;
        $this->invalid[$property] = $msg;
        return true;
    }
    
    public function getInvalid()
    {
        return $this->invalid;
    }
}
?>
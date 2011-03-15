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
 * AuthLogin Event Observer
 *
 * This observers methods are called during authsystem login operations
 *
 * @author Chris Powis <crisp@xaraya.com>
**/
sys::import('xaraya.structures.events.observer');
class AuthsystemAuthLoginFormObserver extends EventObserver implements ixarEventObserver
{
    public $module = 'authsystem';
    public $tplmodule = 'authsystem';
    public $tplbase = 'authlogin';
    public $tplform = 'showform';
    public $tplblock = 'showformblock';
    
    public function showform(ixarEventSubject $subject)
    {
        return $subject->getArgs();
        //$data = $subject->getArgs();
        //return xarTplModule($this->tplmodule, $this->tplbase, $this->tplform, $data);
    }
    
    public function showformblock(ixarEventSubject $subject)
    {
        return $subject->getArgs();
        //$data = $subject->getArgs();
        //return xarTplModule($this->tplmodule, $this->tplbase, $this->tplblock, $data);    
    }

}
?>
<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

class UpgradeStep extends Object
{
    public $task        = '';
    public $success     = true;
    public $reply;
    public $name;
    
    public function __construct() {
        $this->reply = xarML("Success!");
        $this->name = get_class($this);
    }
    
    public function runx()
    {
        return $this->success;
    }

    protected function fail()
    {
        $this->success = false;
        $this->reply = xarML("
        Failed!
        ");
        return true;
    }
}

?>
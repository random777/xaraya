<?php
// {{{ Header
/*
 * -File       $Id: TaskdefTask.php,v 1.17 2003/04/09 15:59:23 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.Task');
/**
 *  @package  phing.tasks.system
 */
class TaskdefTask extends Task {

    var $name    = null;
    var $defclass = null;

    function main() {
        $this->_addDefinition($this->name, $this->defclass);
    }

    function setName($name) {
        $this->name = (string) $name;
    }

    function setClassname($class) {
        $this->defclass = (string) $class;
    }

    function _addDefinition($name, $class) {
        $this->project->addTaskDefinition($name, $class);
    }
}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

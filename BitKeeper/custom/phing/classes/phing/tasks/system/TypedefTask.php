<?php
// {{{ Header
/*
 * -File       $Id: TypedefTask.php,v 1.12 2003/04/09 15:59:23 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, 2002 Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.Task');
/**
 *  @package  phing.tasks.system
 */
class TypedefTask extends Task {

    var $name     = null;
    var $defclass = null;

    function main() {
        $this->_addDefinition($this->name, $this->defclass);
    }

    function setName($name)	{
        $this->name = (string) $name;
    }

    function setClassname($class) {
        $this->defclass = (string) $class;
    }

    function _addDefinition($name, $class) {
        $this->project->addDataTypeDefinition($name, $class);
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

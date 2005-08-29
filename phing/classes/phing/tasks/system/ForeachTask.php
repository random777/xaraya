<?php
// {{{ Header
/*
 * -File       $Id: ForeachTask.php,v 1.12 2003/05/03 16:12:49 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2003, Eye Integrated Communications
 * -Author     jason hines <jason@greenhell.com>
 */
// }}}

import("phing.Task");
import("phing.BuildException");
import("phing.tasks.system.PhingTask");

/**
 * <foreach> task
 *
 * Task definition for the foreach task.  This task takes a list with
 * delimited values, and executes a target with set param.
 *
 * Usage:
 * <foreach list="values" target="targ" param="name" delimiter="|" />
 *
 * Attributes:
 * list      --> The list of values to process, with the delimiter character,
 *               indicated by the "delimiter" attribute, separating each value.
 * target    --> The target to call for each token, passing the token as the
 *               parameter with the name indicated by the "param" attribute.
 * param     --> The name of the parameter to pass the tokens in as to the
 *               target.
 * delimiter --> The delimiter string that separates the values in the "list"
 *               parameter.  The default is ",".
 *
 * @author    jason hines <jason@greenhell.com>
 * @version   $Revision: 1.12 $ $Date: 2003/05/03 16:12:49 $
 * @access    public
 * @package   phing.tasks.system
 */
class ForeachTask extends Task {

    var $list		  = NULL;
    var $param		= NULL;
    var $target		= NULL;
    var $delimiter = ",";
    var $callee = null;

    function init() {
        $prj =& $this->getProject();
        $this->callee =& $prj->createTask("phingcall");
        $this->callee->setOwningTarget($this->getOwningTarget());
        $this->callee->setTaskName($this->getTaskName());
        $this->callee->setLocation($this->getLocation());
        $this->callee->init();
    }

    /**
     * This method does the work.
     * 
     * @param	string	desc
     * @access	public
     */   
    function main() {
        if ($this->list === NULL) {
            throw (new BuildException("Missing list to iterate through", __FILE__, __LINE__));
        }
        if ($this->param === NULL) {
            throw (new BuildException("You must supply a property name to set on each iteration in param", __FILE__, __LINE__));
        }
        if ($this->target === NULL) {
            throw (new BuildException("You must supply a target to perform", __FILE__, __LINE__));
        }

        $prj =& $this->getProject();
        $callee =& $this->callee;
        $callee->setTarget($this->target);
        $callee->setInheritAll(true);
        $callee->setInheritRefs(true);

        $list = $prj->getProperty($this->list);
        $arr = explode($this->delimiter,$list);
        foreach ($arr as $value) {
            $this->log("Setting param '$this->param' to value '$value'", PROJECT_MSG_VERBOSE);

            $prop =& $callee->createProperty();
            $prop->setOverride(true);
            $prop->setName($this->param);
            $prop->setValue($value);

            $callee->main();
        }
    }

    function setList($list) {
        $this->list = (string) $list;
    }

    function setTarget($target) {
        $this->target = (string) $target;
    }

    function setParam($param) {
        $this->param = (string) $param;
    }

    function setDelimiter($delimiter) {
        $this->delimiter = (string) $delimiter;
    }

    function &createProperty() {
        return $this->callee->createProperty();
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

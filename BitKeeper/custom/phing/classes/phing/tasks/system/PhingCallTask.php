<?php
// {{{ Header
/*
 * -File       $Id: PhingCallTask.php,v 1.8 2003/05/03 16:03:49 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell  
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.Task");
import("phing.BuildException");

/**
 *  Call another target in the same project.
 *
 *   <pre>
 *    <target name="foo">
 *      <phingcall target="bar">
 *        <property name="property1" value="aaaaa" />
 *        <property name="foo" value="baz" />
 *       </phingcall>
 *    </target>
 *
 *    <target name="bar" depends="init">
 *      <echo message="prop is ${property1} ${foo}" />
 *    </target>
 *  </pre>
 *
 *  <p>This only works as expected if neither property1 nor foo are
 *  defined in the project itself.
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.8 $ $Date: 2003/05/03 16:03:49 $
 *  @access    public
 *  @package   phing.tasks.system
 */

class PhingCallTask extends Task {

    var $callee;
    var $subTarget;
    // must match the default value of PhingTask#inheritAll
    var $inheritAll = true;
    // must match the default value of PhingTask#inheritRefs
    var $inheritRefs = false;

    /**
     *  If true, pass all properties to the new Phing project.
     *  Defaults to true. Future use.
     *  @param boolean new value
     */
    function setInheritAll($inherit) {
        $this->inheritAll = (boolean) $inherit;
    }

    /**
     *  If true, pass all references to the new Phing project.
     *  Defaults to false. Future use.
    *
     *  @param boolean new value
     */
    function setInheritRefs($inheritRefs) {
        $this->inheritRefs = (boolean) $inheritRefs;
    }

    /**
     *  init this task by creating new instance of the phing task and
     *  configuring it's by calling its own init method.
     */
    function init() {
        $prj =& $this->getProject();
        $this->callee =& $prj->createTask("phing");
        $this->callee->setOwningTarget($this->getOwningTarget());
        $this->callee->setTaskName($this->getTaskName());
        $this->callee->setLocation($this->getLocation());
        $this->callee->init();
    }

    /**
     *  hand off the work to the phing task of ours, after setting it up
     *  @throws BuildException on validation failure or if the target didn't
     *  execute
     */
    function main() /* throws BuildException */ {
        $this->log("Running PhingCallTask for target '" . $this->subTarget . "'", PROJECT_MSG_DEBUG);
        if ($this->callee == null) {
            $this->init();
        }

        if ($this->subTarget == null) {
            throw(new BuildException("Attribute target is required.", $this->location), __FILE__, __LINE__);
        }

        $prj =& $this->getProject();
        $this->callee->setPhingfile($prj->getProperty("phing.file"));
        $this->callee->setTarget($this->subTarget);
        $this->callee->setInheritAll($this->inheritAll);
        $this->callee->setInheritRefs($this->inheritRefs);
        $this->callee->main();
    }

    /**
     * Property to pass to the invoked target.
     */
    function &createProperty() {
        if ($this->callee == null) {
            $this->init();
        }
        return $this->callee->createProperty();
    }

    /**
     * Target to execute, required.
     */
    function setTarget($target) {
        $this->subTarget = (string) $target;
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

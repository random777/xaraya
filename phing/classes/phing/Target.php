<?php
/*
 * $Id: Target.php,v 1.11 2003/05/06 20:54:27 openface Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://binarycloud.com/phing/>.
 */

import('phing.TaskContainer');

/**
 *  The Target component. Carries all required target data. Implements the
 *  abstract class {@link TaskContainer}
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.11 $ $Date: 2003/05/06 20:54:27 $
 *  @access    public
 *  @see       TaskContainer
 *  @package   phing
 */

class Target extends TaskContainer {

    var $name            = null;    // name of target
    var $dependencies    = array(); // dependencies
    var $children        = array(); // holds objects of children of this target
    var $ifCondition     = "";      // the if cond. from xml
    var $unlessCondition = "";      // the unless cond. from xml
    var $description     = null;    // description of this target
    var $project         = null;    // reference to project


    /**
     *  References the project to the current component.
     *
     *  @param    object    The reference to the current project
     *  @access   public
     */
    function setProject(&$project) {
        $this->project =& $project;
    }

    /**
     *  Returns reference to current project
     *
     *  @return   object   Reference to current porject object
     *  @access   public
     */
    function &getProject() {
        return $this->project;
    }

    /**
     *  Sets the target dependencies from xml
     *
     *  @param  string  Comma separated list of targetnames that depend on
     *                  this target
     *  @access   public
     *  @throws   BuildException
     */
    function setDepends($_depends) {
        // FIMXE: add checks, Exceptions use StringTokenizer not explode
        $deps = explode(",", $_depends);
        for ($i=0; $i<count($deps); ++$i) {
            $this->addDependency(trim($deps[$i]));
        }
    }

    /**
     *  Adds a singular dependent target name to the list
     *
     *  @param   string   The dependency target to add
     *  @access  public
     */
    function addDependency($dependency) {
        array_push($this->dependencies, (string) $dependency);
    }

    /**
     *  Returns reference to indexed array of the dependencies this target has.
     *
     *  @return  array  Referece to target dependencoes
     *  @access  public
     */
    function getDependencies() {
        return $this->dependencies;
    }

    /**
     *  Sets the name of the target
     *
     *  @param  string   Name of this target
     *  @access public
     */
    function setName($name) {
        $this->name = (string) $name;
    }

    /**
     *  Returns name of this target.
     *
     *  @return  string     The name of the target
     *  @access   public
     */
    function getName() {
        return (string) $this->name;
    }

    /**
     *  Adds a task element to the list of this targets child elements
     *
     *  @param   object  The task object to add
     *  @access  public
     */
    function addTask(&$task) {
        $this->children[] =& $task;
    }

    /**
     *  Adds a runtime configurable element to the list of this targets child
     *  elements.
     *
     *  @param   object  The RuntimeConfigurabel object
     *  @access  public
     */
    function addDataType(&$rtc) {
        $this->children =& $rtc;
    }

    /**
     *  Returns an array of all tasks this target has as childrens.
     *
     *  The task objects are copied here. Don't use this method to modify
     *  task objects.
     *
     *  @return  array  An array of task objects
     *  @access  public
     */
    function getTasks() {
        $tasks = array();
        for ($i=0; $i<count($this->children); ++$i) {
            $tsk = $this->children[$i];
            if (is_a($tsk, "Task")) {
                // note: we're copying objects here !!!
                // ZE2: use $tsk->__clone() here
                $tasks[] = $tsk;
            }
        }
        return $tasks;
    }

    /**
     *  Set the if-condition from the XML tag, if any. The property name given
     *  as parameter must be present so the if condition evaluates to true
     *
     *  @param   string  The property name that has to be present
     *  @access  public
     */
    function setIf($property) {
        $this->ifCondition = ($property === null) ? "" : $property;
    }

    /**
     *  Set the unless-condition from the XML tag, if any. The property name
     *  given as parameter must be present so the unless condition evaluates
     *  to true
     *
     *  @param   string  The property name that has to be present
     *  @access  public
     */
    function setUnless($property) {
        $this->unlessCondition = ($property === null) ? "" : $property;
    }

    /**
     *  Sets a textual description of this target.
     *
     *  @param   string  The description text
     *  @access  public
     */
    function setDescription($description) {
        if ($description !== null && strcmp($description, "") !== 0) {
            $this->description = (string) $description;
        } else {
            $this->description = null;
        }
    }

    /**
     *  Returns the description of this target.
     *
     *  @return  string  The description text of this target
     *  @access  public
     */
    function getDescription() {
        return $this->description;
    }

    /**
     *  Returns a string representation of this target. In our case it
     *  simply returns the target name field
     *
     *  @return  string  The string representation of this target
     *  @access  public
     */
    function toString() {
        return (string) $this->name;
    }

    /**
     *  The entry point for this class. Does some checking, then processes and
     *  performs the tasks for this target.
     *
     *  @access  public
     */
    function main() {
        if ($this->testIfCondition() && $this->testUnlessCondition()) {
            for ($i=0; $i<count($this->children); ++$i) {
                $o =& $this->children[$i];
                if (is_a($o, "Task")) {
                    // child is a task
                    $o->perform();
                } else {
                    // child is a RuntimeConfigurable
                    $o->maybeConfigure($this->project);
                }
            }
        } else if (!$this->testIfCondition()) {
            $this->project->log("Skipped target '{$this->name}' because property '{$this->ifCondition}' not set.", PROJECT_MSG_VERBOSE);
        } else {
            $this->project->log("Skipped target '{$this->name}' because property '{$this->unlessCondition}' set.", PROJECT_MSG_VERBOSE);
        }
    }

    /**
     *  Performs the tasks by calling the main method of this target that
     *  actually executes the tasks.
     *
     *  This method is for ZE2 and used for proper exception handling of
     *  task exceptions.
     *
     *  @access   public
     */
    function performTasks() {
        {// try to execute this target
            $this->project->fireTargetStarted($this);
            $this->main();
            $this->project->fireTargetFinished($this, $null=null);
        }
        if (catch("RuntimeException", $exc)) {
            // log here and rethrow
            $this->project->fireTargetFinished($this, $exc);
            throw ($exc);
            return;
        }
    }

    /**
     *  Replaces a task in the children list with another task.
     *
     *  @param   object  The task object to be replaces
     *  @param   object  The task object replacement
     *  @access  public
     */
    function replaceTask(&$el, &$task) {
        $index = -1;
        while (($index = $this->_indexOf($this->children, $el)) >= 0) {
            $this->children[$index] =& $task;
        }
    }

    /**
     *  Returns the array index of an object reference stored in the
     *  given stack of references.
     *
     *  @param   object  The array of object references
     *  @param   object  The object which position needs to be determined
     *  @return  integer The position of the object reference within the stack;
     *                   or -1 if the object is not in the stack
     *  @access  private
     */
    function _indexOf(&$stack, &$object) {
        for ($i=0; $i<count($stack); ++$i) {
            if (compareReferernces($stack, $object)) {
                return $i;
            }
        }
        return -1;
    }

    // {{{ method testIfCondition()
    /**
     *  Tests if the property set in ifConfiditon exists.
     *
     *  @return  boolean  <code>true</code> if the property specified
     *                    in <code>$this->ifCondition</code> exists;
     *                    <code>false</code> otherwise
     *  @access  public
     */
    function testIfCondition() {
        if ($this->ifCondition === "") {
            return true;
        }

        $properties = explode(",", $this->ifCondition);

        $result = true;
        foreach ($properties as $property) {
            $test = ProjectConfigurator::replaceProperties($this->getProject(), $property, $this->project->getProperties());
            $result = $result && ($this->project->getProperty($test) !== null);
        }

        return $result;
    }
    // }}}
    // {{{ method testUnlessCondition()
    /**
     *  Tests if the property set in unlessCondition exists.
     *
     *  @return  boolean  <code>true</code> if the property specified
     *                    in <code>$this->unlessCondition</code> exists;
     *                    <code>false</code> otherwise
     *  @access  public
     */
    function testUnlessCondition() {
        if ($this->unlessCondition === "") {
            return true;
        }
        
        $properties = explode(",", $this->unlessCondition);

        $result = true;
        foreach ($properties as $property) {
            $test = ProjectConfigurator::replaceProperties($this->getProject(), $property, $this->project->getProperties());
            $result = $result && ($this->project->getProperty($test) === null);
        }
        return $result;
    }
    // }}}
}
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

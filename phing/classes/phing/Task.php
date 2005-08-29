<?php
/*
 * $Id: Task.php,v 1.8 2003/04/09 15:58:09 thyrell Exp $
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

import('phing.ProjectComponent');
import('phing.RuntimeConfigurable');

/**
 *  The base class for all tasks.
 *
 *  <p>Use {@link Project#createTask} to create a new Task.
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.8 $ $Date: 2003/04/09 15:58:09 $
 *  @access    public
 *  @see       Project#createTask
 *  @package   phing
 */

class Task extends ProjectComponent {

    var $_target      = null;       // owning target ref
    var $_description = null;		// description of the task
    var $_taskType    = null;       // internal taskname (req)
    var $_taskName    = null;       // taskname for logger

    var $_location = null;          // stored buildfile location
    var $_wrapper  = null;          // wrapper of the task

    /**
     * Sets the owning target this task belongs to.
     *
     * @param   object  Reference to owning target
     * @access  public
     */
    function setOwningTarget(&$target) {
        if (!isInstanceOf($target, "Target")) {
            throw (new RuntimeException("Expected object of type 'Target' got something else"), __FILE__, __LINE__);
            System::halt(-1);
            return;
        }
        $this->_target =& $target;
    }

    /**
     *  Returns the owning target of this task.
     *
     *  @return  object    The target object that owns this task
     *  @access  public
     */
    function &getOwningTarget() {
        return $this->_target;
    }

    /**
     *  Returns the name of task, used only for log messages
     *
     *  @return  string  Name of this task
     *  @access  public
     */
    function getTaskName() {
        return $this->_taskName;
    }

    /**
     *  Sets the name of this task for log messages
     *
     *  @return  string  A string representing the name of this task for log
     *  @access  public
     */
    function setTaskName($name) {
        $this->_taskName = (string) $name;
    }

    /**
     *  Returns the name of the task under which it was invoked,
     *  usually the XML tagname
     *
     *  @return  string  The type of this task (XML Tag)
     *  @access  public
     */
    function getTaskType() {
        return $this->_taskType;
    }

    /**
     *  Sets the type of the task. Usually this is the name of the XML tag
     *
     *  @param   string  The type of this task (XML Tag)
     *  @access  public
     */
    function setTaskType($name) {
        $this->_taskType = (string) $name;
    }

    /**
     *  Provides a project level log event to the task.
     *
     *  @param   string  The message to log
     *  @param   integer The priority of the message
     *  @access  public
     *  @see     Project
     *  @see     BuildEvent
     *  @see     BuildListener
     */
    function log($msg, $level = PROJECT_MSG_INFO) {
        $this->project->logObject($this, $msg, $level);
    }

    /**
     *  Sets a textual description of the task
     *
     *  @param   string  The text describing the task
     *  @access  public
     */
    function setDescription($desc) {
        $this->description = (string) $desc;
    }

    /**
     *  Returns the textual description of the task
     *
     *  @return  string  The text description of the task
     *  @access  public
     */
    function getDescription() {
        return $this->description;
    }

    /**
     *  Called by the parser to let the task initialize properly.
     *  Should throw a BuildException if something goes wrong with the build
     *
     *  This is abstract here. Can be overloaded by real tasks.
     *
     *  @access  public
     */
    function init() {}

    /**
     *  Called by the project to let the task do it's work. This method may be
     *  called more than once, if the task is invoked more than once. For
     *  example, if target1 and target2 both depend on target3, then running
     *  <em>phing target1 target2</em> will run all tasks in target3 twice.
     *
     *  Should throw a BuildException if someting goes wrong with the build
     *
     *  This is abstract here. Must be overloaded by real tasks.
     *
     *  @access  public
     */
    function main() {}

    /**
     *  Returns the location within the buildfile this task occurs. Used
     *  by {@link BuildException} to give detailed error messages.
     *
     *  @return  object  The location object describing the position of this
     *                   task within the buildfile.
     *  @access  public
     */
    function getLocation() {
        return $this->_location;
    }

    /**
     *  Sets the location within the buildfile this task occurs. Called by
     *  the parser to set location information.
     *
     *  @return  object  The location object describing the position of this
     *                   task within the buildfile.
     *  @access  public
     */
    function setLocation($location) {
        // type check, error must never occur, bad code of it does
        if (!isInstanceOf($location, 'Location')) {
            throw (new RuntimeException("Excpected Location object, got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }
        $this->_location = $location;
    }

    /**
     *  Returns the wrapper object for runtime configuration
     *
     *  @return  object  The wrapper object used by this task
     *  @access  public
     */
    function &getRuntimeConfigurableWrapper() {
        if ($this->_wrapper === null) {
            $this->_wrapper =& new RuntimeConfigurable($this, $this->getTaskName());
        }
        return $this->_wrapper;
    }

    /**
     *  Sets the wrapper object this task should use for runtime
     *  configurable elements.
     *
     *  @param   object  The wrapper object this task should use
     *  @access  public
     */
    function setRuntimeConfigurableWrapper(&$wrapper) {
        // type check, error must never occur, bad code of it does
        if (!is_a($wrapper, "RuntimeConfigurable")) {
            throw (new RuntimeException("Expected a 'RuntimeConfigurable' object, got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }
        $this->_wrapper =& $wrapper;
    }

    /**
     *  Configure this task if it hasn't been done already.
     *
     *  @access  public
     */
    function maybeConfigure() {
        if ($this->_wrapper !== null) {
            $this->_wrapper->maybeConfigure($this->project);
        }
    }

    /**
     *  Perfrom this task
     *
     *  @access  public
     */
    function perform() {
        { // try executing task
            $this->project->fireTaskStarted($this);
            $this->maybeConfigure();
            $this->main();
            $this->project->fireTaskFinished($this, $null=null);
        }
        if (catch("RuntimeException", $exc)) {
            if (is_a($exc, "BuildException")) {
                $be =& $exc;
                if ($be->getLocation() === null) {
                    $be->setLocation($this->getLocation());
                }
            }
            $this->project->fireTaskFinished($this, $exc);
            throw ($exc);
        }
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

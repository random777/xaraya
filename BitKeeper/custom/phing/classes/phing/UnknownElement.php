<?php
/*
 * $Id: UnknownElement.php,v 1.7 2003/04/30 18:46:56 openface Exp $
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

import("phing.Task");

/*
 *  Wrapper class that holds all information necessary to create a task
 *  that did not exist when Phing started.
 *
 *  <em> This has something to do with phing encountering an task XML element
 *  it is not aware of at start time. This is a situation where special steps
 *  need to be taken so that the element is then known.</em>
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.7 $ $Date: 2003/04/30 18:46:56 $
 *  @access    public
 *  @package   phing
 */

class UnknownElement extends Task {

    var $elementName;
    var $realTask;
    var $children = array();
    var $target;

    /**
     *  Constructs a UnknownElement object
     *
     *  @param    string  The XML element name that is unknown
     *  @access   public
     */
    function UnknownElement($elementName) {
        $this->elementName = (string) $elementName;
    }

    /**
     *  Return the XML element name that this <code>UnnownElement</code>
     *  handles.
     *
     *  @return  string  The XML element name that is unknown
     *  @access  public
     */
    function getTag() {
        return (string) $this->elementName;
    }


    /**
     *  Tries to configure the unknown element
     *
     *  @throws  BuildException if the element can not be configured
     *  @access  public
     */
    function maybeConfigure() {
        $this->realTask =& $this->_makeTask($this, $this->_wrapper);
        $this->_wrapper->setProxy($this->realTask);
        $this->realTask->setRuntimeConfigurableWrapper($this->_wrapper);
        $this->handleChildren($this->realTask, $this->_wrapper);
        $this->realTask->maybeConfigure();
        $this->target->replaceTask($this, $this->realTask);
    }

    /**
     *  Called when the real task has been configured for the first time.
     *
     *  @throws  BuildException if the task can not be created
     *  @access  public
     */
    function main() {
        if ($this->realTask === null) {
            // plain impossible to get here, maybeConfigure should
            // have thrown an exception.
            throw (new BuildException("Could not create task of type: {$this->elementName}"));
            return;
        }
        $this->realTask->main();
    }

    /**
     *  Add a child element to the unknown element
     *
     *  @param   object  The object representing the child element
     *  @access  public
     */
    function addChild(&$child) {
        $this->children[] = &$child;
    }

    /**
     *  Handle child elemets of the unknown element, if any.
     *
     *  @param   object  The parent object the unkown element belongs to
     *  @param   object  The parent wrapper object
     *  @access  public
     */
    function handleChildren(&$parent, &$parentWrapper) {

        //if (parent instanceof TaskAdapter) {
        //$parent = $arent->getProxy();
        //}

        $parentClass = get_class($parent);
        $ih =& IntrospectionHelper::getHelper($parentClass);

        for ($i=0; $i<count($this->children); ++$i) {

            $childWrapper =& $parentWrapper->getChild($i);
            $child =& $this->children[$i];
            $realChild = null;
            if (isInstanceOf($parent, "TaskContainer")) {
                $realChild =& $this->_makeTask($child, $childWrapper);
                $parent->addTask($realChild);
            } else {
                $realChild =& $ih->createElement($this->project, $parent, $child->getTag());
            }

            $childWrapper->setProxy($realChild);
            if (isInstanceOf($realChild, "Task")) {
                $realChild->setRuntimeConfigurableWrapper($childWrapper);
            }

            $child->handleChildren($realChild, $childWrapper);
            if (isInstanceOf($realChild, "Task")) {
                $realChild->maybeConfigure();
            }
        }
    }

    /**
     *  Create a named task and configure it up to the init() stage.
     *
     *  @param  object  The unknwon element to create a task from
     *  @param  object  The wrapper object
     *  @return object  The freshly created task
     *  @access private
     */
    function &_makeTask(&$ue, &$w) {
        $task =& $this->project->createTask($ue->getTag());
        if ($task === null) {
            $this->log("Could not create task of type: {$this->elementName} Common solutions are adding the task to phing/tasks/defaults.properties.", PROJECT_MSG_DEBUG);
            throw (new BuildException("Could not create task of type: {$this->elementName}. Common solutions are to use taskdef to declare your task"));
            return;
        }

        // used to set the location within the xmlfile so that exceptions can
        // give detailed messages

        $task->setLocation($this->getLocation());
        $attrs = $w->getAttributes();
        if (isset($attrs['id']) && ($attrs['id'] !== null)) {
            $this->project->addReference($attrs['id'], $task);
        }

        // UnknownElement always has an associated target
        $task->setOwningTarget($this->target);

        $task->init();
        return $task;
    }

    /**
     *  Get the name of the task to use in logging messages.
     *
     *  @return  string  The tasks name
     *  @access  public
     */
    function getTaskName() {
        return $this->realTask === null ? parent::getTaskName() : $this->realTask->getTaskName();
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

<?php
/*
 * $Id: TaskFilter.php,v 1.7 2003/04/09 15:58:10 thyrell Exp $
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

import("phing.UnknownElement");

/**
 * The task handler class.
 *
 * This class handles the occurance of a <task> tag and it's possible
 * nested tags (datatypes and tasks) that may be unknown off bat and are
 * initialized on the fly.
 *
 * @author	  Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.7 $ $Date: 2003/04/09 15:58:10 $
 * @access    public
 * @package   phing.parser
 */

class TaskFilter extends AbstractFilter {

    /**
     * Reference to the target object that contains the currently parsed
     * task
     * @var object the target instance
     */
    var $target;

    /**
     * Reference to the target object that represents the currently parsed
     * target. This must not necessarily be a target, hence extra variable.
     * @var object the target instance
     */
    var $container;

    /**
     * Reference to the task object that represents the currently parsed
     * target.
     * @var object the target instance
     */
    var $task;

    var $wrapper = null;

    /**
     * The phing project configurator object
     * @var object the ProjectConfigurator
     */
    var $configurator;

    /**
     * Constructs a new TaskFilter and sets up everything.
     *
     * @param  object  the ExpatParser object
     * @param  object  the parent handler that invoked this handler
     * @param  object  the ProjectConfigurator object
     * @param  object  the container object this task is contained in
     * @param  object  the target object this task is contained in
     * @access public
     */
    function TaskFilter(&$parser, &$parentHandler, &$configurator, &$container, &$target) {
        parent::AbstractFilter($parser, $parentHandler);

        if (!is_a($configurator, "ProjectConfigurator")) {
            throw (new RuntimeException("Argument expected to be a ProjectConfigurator, got something else"), __FILE__, __LINE__);
            return;
        }
        if (($container !== null) && !is_a($container, "TaskContainer")) {
            throw (new RuntimeException("Argument expected to be a TaskContainer, got something else"), __FILE__, __LINE__);
            return;
        }
        if (($target !== null) && !is_a($target, "Target")) {
            throw (new RuntimeException("Argument expected to be a Target, got something else"), __FILE__, __LINE__);
            return;
        }

        $this->container =& $container;
        $this->target =& $target;
        $this->configurator =& $configurator;
    }

    /**
     * Executes initialization actions required to setup the data structures
     * related to the tag.
     * <p>
     * This includes:
     * <ul>
     * <li>creation of the task object</li>
     * <li>calling the setters for attributes</li>
     * <li>adding the task to the container object</li>
     * <li>adding a reference to the task (if id attribute is given)</li>
     * <li>executing the task if the container is the &lt;project&gt;
     * element</li>
    	 * </ul>
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @throws ExpatParseException if attributes are incomplete or invalid
     * @access public
     */
    function init($tag, $attrs) {
        // shorthands
        $configurator =& $this->configurator;
        $project =& $this->configurator->project;

        $this->task =& $project->createTask($tag);
        if (catch('BuildException', $be)) {
            // swallow here, will be thrown again in
            // UnknownElement->maybeConfigure if the problem persists.
        }

        // the task is not known of bat, try to load it on thy fly
        if ($this->task === null) {
            $this->task =& new UnknownElement($tag);
            $this->task->setProject($project);
            $this->task->setTaskType($tag);
            $this->task->setTaskName($tag);
        }

        // add file position information to the task (from parser)
        // should be used in task exceptions to provide details
        $this->task->setLocation($this->parser->getLocation());
        $configurator->configureId($task, $attrs);

        // Top level tasks don't have associated targets
        if ($this->target !== null) {
            $this->task->setOwningTarget($this->target);
            $this->container->addTask($this->task);
            $this->task->init();
            $this->wrapper =& $this->task->getRuntimeConfigurableWrapper();
            $this->wrapper->setAttributes($attrs);
        } else {
            $this->task->init();
            $configurator->configure($this->task, $attrs, $project);
        }
    }

    /**
     * Executes the task at once if it's directly beneath the <project> tag.
     * @access private
     */
    function _finished() {
        if ($this->task !== null && $this->target === null) {
            $this->task->main();
        }
    }

    /**
     * Handles character data.
     *
     * @param  string  the CDATA that comes in
     * @access public
     */
    function characters($data) {
        if ($this->wrapper === null) {
            { // try
                $configurator->addText($project, $this->task, $data);
            }
            if (catch ("BuildException", $exc)) {
                throw (new ExpatParseException($exc->getMessage(), $this->parser->getLocation()));
                return;
            }
        } else {
            $this->wrapper->addText($data);
        }
    }

    /**
     * Checks for nested tags within the current one. Creates and calls
     * handlers respectively.
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @access public
     */
    function startElement($name, $attrs) {
        $project =& $this->configurator->project;
        if (is_a($this->task, "TaskContainer")) {
            $th =& new TaskFilter($this->parser, $this, $this->configurator, $this->target);
            $th->init($name, $attrs);
        } else {
            $tmp =& new NestedElementFilter($this->parser, $this, $this->configurator, $this->task, $this->wrapper, $this->target);
            $tmp->init($name, $attrs);
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

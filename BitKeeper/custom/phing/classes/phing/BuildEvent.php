<?php
/*
 * $Id: BuildEvent.php,v 1.6 2003/04/09 15:58:09 thyrell Exp $
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

import("phing.system.lang.EventObject");

/**
 *  Encapsulates a build specific event.
 *
 *  <p>We have three sources of events all handled by this class:
 *
 *  <ul>
 *   <li>Project level events</li>
 *   <li>Target level events</li>
 *   <li>Task level events</li>
 *  </ul>
 *
 *  <p> Events are all fired from the project class by creating an event object
 *  using this class and passing it to the listeners.
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.6 $ $Date: 2003/04/09 15:58:09 $
 *  @access    public
 *  @package   phing
 */

class BuildEvent extends EventObject {

    /**
     *  A reference to the project
     *
     *  @var    object
     *  @access private
     */
    var $project = null;

    /**
     *  A reference to the target
     *
     *  @var    object
     *  @access private
     */
    var $target = null;

    /**
     *  A reference to the task
     *
     *  @var    object
     *  @access private
     */
    var $task = null;

    /**
     *  The message of this event, if the event is a message
     *  @var    string
     *  @access private
     */
    var $message = null;

    /**
     *  The priority of the message
     *
     *  @var    string
     *  @see    $message
     *  @access private
     */
    var $priority = PROJECT_MSG_VERBOSE;

    /**
     *  The execption that caused the event, if any
     *
     *  @var    object
     *  @access private
     */
    var $exception = null;

    /**
     *  Construct a BuildEvent for a project, task or target source event
     *
     *  @param  object  project the project that emitted the event.
     *  @access public
     */
    function BuildEvent(&$source) {
        parent::EventObject($source);
        if (is_a($source, "Project")) {
            $this->project =& $source;
            $this->target = null;
            $this->task = null;
        } else if (is_a($source, "Target")) {
            $this->project =& $source->getProject();
            $this->target =& $source;
            $this->task = null;
        } else if (is_a($source, "Task")) {
            $this->project =& $source->getProject();
            $this->target =& $source->getOwningTarget();
            $this->task =& $source;
        } else {
            $this = null;
            throw (new RuntimeException("Can not construct BuildEvent, unknown source given"), __FILE__, __LINE__);
            System::halt(-1);
        }
    }

    /**
     *  Sets the message with details and the message priority for this event.
     *
     *  @param  string   The string message of the event
     *  @param  integer  The priority this message should have
     *  @access public
     */
    function setMessage($message, $priority) {
        $this->message = (string) $message;
        $this->priority = (int) $priority;
    }

    /**
     *  Set the exception that was the cause of this event.
     *
     *  @param  object   The exception that caused the event
     *  @access public
     */
    function setException(&$exception) {
        $this->exception =& $exception;
    }

    /**
     *  Returns the project instance that fired this event.
     *
     *  The reference to the project instance is set by the constructor if this
     *  event was fired from the project class.
     *
     *  @return  object  The project instance that fired this event
     *  @access  public
     */
    function &getProject() {
        return $this->project;
    }

    /**
     *  Returns the target instance that fired this event.
     *
     *  The reference to the target instance is set by the constructor if this
     *  event was fired from the target class.
     *
     *  @return  object  The target that fired this event
     *  @access  public
     */
    function &getTarget() {
        return $this->target;
    }

    /**
     *  Returns the target instance that fired this event.
     *
     *  The reference to the task instance is set by the constructor if this
     *  event was fired within a task.
     *
     *  @return  object  The task that fired this event
     *  @access  public
     */
    function &getTask() {
        return $this->task;
    }

    /**
     *  Returns the logging message. This field will only be set for
     *  "messageLogged" events.
     *
     *  @return  string   The log message
     *  @access  public
     */
    function getMessage() {
        return $this->message;
    }

    /**
     *  Returns the priority of the logging message. This field will only
     *  be set for "messageLogged" events.
     *
     *  @return  integer  The message priority
     *  @access  public
     */
    function getPriority() {
        return $this->priority;
    }

    /**
     *  Returns the exception that was thrown, if any.
     *  This field will only be set for "taskFinished", "targetFinished", and
     *  "buildFinished" events.
     *
     *  @see BuildListener::taskFinished()
     *  @see BuildListener::targetFinished()
     *  @see BuildListener::buildFinished()
     */
    function &getException() {
        return $this->exception;
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

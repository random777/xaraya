<?php
/*
 * $Id: DefaultLogger.php,v 1.8 2003/05/03 16:03:49 purestorm Exp $
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

import("phing.system.lang.System");
import("phing.BuildListener");

/**
 *  Writes a build event to the console.
 *
 *  Currently, it only writes which targets are being executed, and
 *  any messages that get logged.
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.8 $ $Date: 2003/05/03 16:03:49 $
 *  @access    public
 *  @see       BuildEvent
 *  @package   phing
 */


class DefaultLogger extends BuildListener {

    /**
     *  Size of the left column in output. The default char width is 12.
     *
     *  @var    integer
     *  @access private
     */
    var $LEFT_COLUMN_SIZE = 12;

    /**
     *  The message output level that should be used. The default is
     *  <code>PROJECT_MSG_VERBOSE</code>.
     *
     *  @var    integer
     *  @access private
     *  @see    Project
     */
    var $msgOutputLevel   = PROJECT_MSG_ERR;

    /**
     *  Time that the build started
     *
     *  @var    integer
     *  @access private
     */
    var $startTime        = null;

    /**
     *  Char that should be used to seperate lines. Default is the system
     *  property <em>line.seperator</em>.
     *
     *  @var    string
     *  @access private
     */
    var $lSep             = null;

    /**
     *  Construct a new default logger.
     *
     *  @access public
     */
    function DefaultLogger() {
        $this->lSep = System::getProperty("line.separator");
        $this->msgOutputLevel = PROJECT_MSG_ERR;
    }

    /**
     *  Set the msgOutputLevel this logger is to respond to.
     *
     *  Only messages with a message level lower than or equal to the given
     *  level are output to the log.
     *
     *  <p> Constants for the message levels are in Project.php. The order of
     *  the levels, from least to most verbose, is:
     *
     *  <ul>
     *    <li>PROJECT_MSG_ERR</li>
     *    <li>PROJECT_MSG_WARN</li>
     *    <li>PROJECT_MSG_INFO</li>
     *    <li>PROJECT_MSG_VERBOSE</li>
     *    <li>PROJECT_MSG_DEBUG</li>
     *  </ul>
     *
     *  The default message level for DefaultLogger is PROJECT_MSG_ERR.
     *
     *  @param  integer  the logging level for the logger.
     *  @access public
     */
    function setMessageOutputLevel($level) {
        $this->msgOutputLevel = (int) $level;
    }

    /**
    *  Sets the start-time when the build started. Used for calculating
    *  the build-time.
    *
    *  @param  object  The BuildEvent
    *  @access public
    */

    function buildStarted(&$event) {
        $this->startTime = getMicrotime();
    }

    /**
     *  Prints whether the build succeeded or failed, and any errors that
     *  occured during the build. Also outputs the total build-time.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getException()
     */
    function buildFinished(&$event) {
        $error =& $event->getException();
        if ($error === null) {
            System::println($this->lSep . "BUILD SUCCESSFUL");
        } else {
            System::println($this->lSep . "BUILD FAILED" . $this->lSep);
            if (PROJECT_MSG_VERBOSE <= $this->msgOutputLevel || !isInstanceOf($error, "BuildException")) {
                $error->printStackTrace();
            } else {
                if (isInstanceOf($error, "BuildException")) {
                    System::println($error->toString());
                } else {
                    System::println($error->getMessage());
                }
            }
        }
        System::println($this->lSep . "Total time: " .$this->_formatTime(getMicrotime() - $this->startTime));
    }

    /**
     *  Prints the current target name
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getTarget()
     */
    function targetStarted(&$event) {
        if (PROJECT_MSG_INFO <= $this->msgOutputLevel) {
            $name = $event->getTarget();
            $name = $name->getName();
            System::println($this->lSep . $name . ":");
        }
    }

    /**
     *  Fired when a target has finished. We don't need specific action on this
     *  event. So the methods are empty.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getException()
     */
    function targetFinished(&$event) {}

    /**
     *  Fired when a task is started. We don't need specific action on this
     *  event. So the methods are empty.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getTask()
     */
    function taskStarted(&$event) {}

    /**
     *  Fired when a task has finished. We don't need specific action on this
     *  event. So the methods are empty.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getException()
     */
    function taskFinished(&$event) {}

    /**
     *  Print a message to the stdout.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getMessage()
     */
    function messageLogged(&$event) {
        if ($event->getPriority() <= $this->msgOutputLevel) {
            if ($event->getTask() !== null) {
                $name = $event->getTask();
                $name = $name->getTaskName();
                $msg = "[$name] ";
                for ($i=0; $i < ($this->LEFT_COLUMN_SIZE - strlen($msg)); ++$i) {
                    print(" ");
                }
                print($msg);
            }
            System::println($event->getMessage());
        }
    }

    /**
     *  Formats a time micro integer to human readable format.
     *
     *  @param  integer The time stamp
     *  @access private
     */
    function _formatTime($micros) {
        $seconds = $micros;
        $minutes = $seconds / 60;
        if ($minutes > 1) {
            return (string) sprintf("%1.0f minute%s %0.2f second%s",
                                    $minutes, ($minutes === 1 ? " " : "s "),
                                    $seconds - floor($seconds/60) * 60, ($seconds%60 === 1 ? "" : "s"));
        } else {
            return (string) sprintf("%0.4f second%s", $seconds, ($seconds%60 === 1 ? "" : "s"));
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

<?php
/*
 * $Id: BuildListener.php,v 1.5 2003/04/09 15:58:09 thyrell Exp $
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

/**
 *  Abstract class for build listeners.
 *
 *  Classes that implement a listener must extend this class and (faux)implement
 *  all methods that are decleard as dummies below.
 *
 *  FIXME
 *  throw exceptions when the method's not defined by the derived
 *  listener
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.5 $ $Date: 2003/04/09 15:58:09 $
 *  @access    public
 *  @see       BuildEvent, Project::addBuildListener()
 *  @package   phing
 */

class BuildListener {

    /**
    *  Fired before any targets are started.
    *
    *  @param  object  The BuildEvent
    */
    function buildStarted(&$event) {}

    /**
    *  Fired after the last target has finished.
    *
    *  @param  object  The BuildEvent
    *  @access public
     *  @see    BuildEvent#getException()
    */
    function buildFinished(&$event) {}

    /**
    *  Fired when a target is started.
    *
    *  @param  object  The BuildEvent
    *  @access public
     *  @see    BuildEvent#getTarget()
    */
    function targetStarted(&$event) {}

    /**
    *  Fired when a target has finished.
    *
    *  @param  object  The BuildEvent
    *  @access public
     *  @see    BuildEvent#getException()
    */
    function targetFinished(&$event) {}

    /**
    *  Fired when a task is started.
    *
    *  @param  object  The BuildEvent
    *  @access public
     *  @see    BuildEvent#getTask()
    */
    function taskStarted(&$event) {}

    /**
    *  Fired when a task has finished.
    *
    *  @param  object  The BuildEvent
    *  @access public
     *  @see    BuildEvent#getException()
    */
    function taskFinished(&$event) {}

    /**
    *  Fired whenever a message is logged.
    *
    *  @param  object  The BuildEvent
    *  @access public
     *  @see    BuildEvent#getMessage()
    */
    function messageLogged(&$event) {}
}
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

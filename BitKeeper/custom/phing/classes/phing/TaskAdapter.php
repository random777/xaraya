<?php
/*
 * $Id: TaskAdapter.php,v 1.6 2003/04/09 15:58:09 thyrell Exp $
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
 *  Use introspection to "adapt" an arbitrary ( not extending Task, but with
 *  similar patterns).
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.6 $ $Date: 2003/04/09 15:58:09 $
 *  @access    public
 *  @package   phing
 */

import("phing.Task");
import("phing.BuildException");

class TaskAdapter extends Task {

    var $proxy;

    function execute() {

        // try to set project
        if (method_exists($this->proxy, "setProject")) {
            $this->proxy->setProject($this->project);
        } else {
            throw( new Exception("Error setting project in class " . get_class($this->proxy)));
        }

        if (catch("Exception", $ex)) {
            $this->log("Error setting project in " . get_class($this->proxy) . PROJECT_MSG_ERR);
            throw(new BuildException($ex->getMessage()), __FILE__, __LINE__);
            return;
        }

        //try to call main

        if (method_exists($this->proxy, "main")) {
            $this->proxy->main($this->project);
        } else {
            throw( new Exception("Your task-like class '" . get_class($this->proxy) ."' does not have a main() method"));
        }

        if (catch("Exception", $ex)) {
            $this->log("Error in " . get_class($proxy), PROJECT_MSG_ERR);
            throw( new BuildException($ex->getMessage()));
            return;
        }

    }

    /**
     * Set the target object class
     */
    function setProxy(&$o) {
        $this->proxy =& $o;
    }

    function &getProxy() {
        return $this->proxy;
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

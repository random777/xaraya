<?php
/*
 * $Id: BuildException.php,v 1.6 2003/04/09 15:58:09 thyrell Exp $
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

import("phing.system.lang.RuntimeException");

/**
 * FIXME, add cause
 * Description: BuildException is for when things go wrong in a build execution.
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.6 $ $Date: 2003/04/09 15:58:09 $
 * @package  phing
 */

class BuildException extends RuntimeException {

    var $location = null;  // location in the xml file

    function BuildException($message, $location = null) {
        if ($message === null) {
            $message = "Unspecified message";
        }
        parent::RuntimeException($message);
    }

    /** The string is the name of the file, or the error? */
    function toString() {
        if ($this->location !== null) {
            return $this->location->toString() . $this->getMessage();
        }
        return $this->getMessage();
    }

    /** The location is the directory tree spot. Either where the file is being copied from or to?
    This just returns the location value. */
    function getLocation() {
        return $this->location;
    }

    function setLocation($loc) {
        $this->location = $loc;
    }
}
?>

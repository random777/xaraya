<?php
/*
 * $Id: ConditionBase.php,v 1.8 2003/04/09 15:59:23 thyrell Exp $
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

import("phing.Project");
import("phing.Task");
import("phing.tasks.system.AvailableTask");

/**
 *  Abstract vaseclass for the <condition> task as well as several
 *  conditions - ensures that the types of conditions inside the task
 *  and the "container" conditions are in sync.
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.8 $ $Date: 2003/04/09 15:59:23 $
 *  @access    public
 *  @package   phing.tasks.system.condition
 */
class ConditionBase {

    var $_conditions = array();
    var $_project = null;

    function setProject(&$p) {
        $this->_project =& $p;
    }

    function getProject() {
        return $this->_project;
    }

    function countConditions() {
        return count($this->_conditions);
    }

    function &getConditions() {
        return new ConditionEnumeration($this);
    }

    function addAvailable(&$a) {
        $this->_conditions[] =& $a;
    }

    function addNot(&$n) {
        $this->_conditions[] =& $n;
    }

    function addAnd(&$a) {
        $this->_conditions[] =& $a;
    }

    function addOr(&$o) {
        $this->_conditions[] =& $o;
    }

    function addEquals(&$e) {
        $this->_conditions[] =& $e;
    }

    function addOs(&$o) {
        $this->_conditions[] =& $o;
    }

    // add conditionenum calss
}
// dirty helper since inner classes are not allowed right now
class ConditionEnumeration {
    var $_currentElement = 0;
    var $__cbase = null;

    function ConditionEnumeration(&$cbase) {
        $this->__cbase =& $cbase;
    }

    function hasMoreElements() {
        return $this->__cbase->countConditions() > $this->_currentElement;
    }

    function &nextElement() {
        $o = null;
        $o =& $this->__cbase->_conditions[$this->_currentElement++];

        if (isInstanceOf($o, "Task")) {
            $o->setProject($this->__cbase->getProject());
        } else if (instanceof($o, "ConditionBase")) {
            $o->setProject($this->__cbase->getProject());
        }
        return $o;
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

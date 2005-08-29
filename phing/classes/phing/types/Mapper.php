<?php
/*
 * $Id: Mapper.php,v 1.16 2003/02/24 18:22:16 openface Exp $
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

import('phing.types.DataType');

/**
 * @package   phing.types
 */
class Mapper extends DataType {

    var $__p    = null;
    var $__type = null;

    function Mapper(&$p) {
        $this->__p =& $p;
    }

    /** Set the type of FileNameMapper to use. */
    function setType($type) {
        if ($this->isReference()) {
            throw (DataType::tooManyAttributes());
            return;
        }
        $this->__type = (string) $type;
    }

    var $__classname = null; // protected

    /** Set the class name of the FileNameMapper to use. */
    function setClassname($classname) {
        if ($this->isReference()) {
            throw (DataType::tooManyAttributes());
            return;
        }
        $this->__classname = (string) $classname;
    }


    var $__from = null;

    /**
     * Set the argument to FileNameMapper.setFrom
     */
    function setFrom($from) {
        if ($this->isReference()) {
            throw (DataType::tooManyAttributes());
            return;
        }
        $this->__from = (string) $from;
    }

    var $__to = null;

    /**
     * Set the argument to FileNameMapper.setTo
     */
    function setTo($to) {
        if ($this->isReference()) {
            throw (DataType::tooManyAttributes());
            return;
        }
        $this->__to = (string) $to;
    }

    /**
     * Make this Mapper instance a reference to another Mapper.
     *
     * You must not set any other attribute if you make it a reference.
     */
    function setRefid($r) {
        if ($this->__type !== null || $this->__from !== null || $this->__to !== null) {
            throw (DataType::tooManyAttributes());
            return;
        }
        parent::setRefid($r);
    }

    /** Factory, returns inmplementation of file name mapper as new instance */
    function getImplementation() {
        if ($this->isReference()) {
            $tmp =& $this->_getRef();
            return $tmp->getImplementation();
        }

        if ($this->__type === null && $this->__classname == null) {
            throw (new BuildException("one of the attributes type or classname is required"));
            return;
        }

        if ($this->__type !== null && $this->__classname !== null) {
            throw (new BuildException("must not specify both type and classname attribute"));
            return;
        }

        if ($this->__type !== null) {
            switch($this->__type) {
            case 'identity':
                    $this->__classname = 'phing.mappers.IdentityMapper';
                $c = 'IdentityMapper';
                break;
            case 'flatten':
                $this->__classname = 'phing.mappers.FlattenMapper';
                $c = "FlattenMapper";
                break;
            case 'glob':
                $this->__classname = 'phing.mappers.GlobMapper';
                $c = "GlobMapper";
                break;
            case 'regex':
                $this->__classname = 'phing.mappers.RegexMapper';
                $c = "RegexMapper";
                break;
            case 'merge':
                $this->__classname = 'phing.mappers.MergeMapper';
                $c = "MergeMapper";
                break;
            default:
                throw(new BuildException("Mapper type {$this->__type} not known"));
                return;
                break;
            }
        }

        // get the implementing class
        import($this->__classname);

        // instantite and return the class

        $m = new $c;
        $m->setFrom($this->__from);
        $m->setTo($this->__to);
        return $m;
    }

    /** Performs the check for circular references and returns the referenced Mapper. */
    function &_getRef() {
        if (!$this->checked) {
            $stk = array();
            $stk[] =& $this;
            $this->dieOnCircularReference($stk, $this->__p);
        }

        $o = $this->__ref->getReferencedObject($this->__p);
        if (!(isInstanceOf($o, 'Mapper'))) {
            $msg = $this->__ref->getRefId()." doesn't denote a mapper";
            throw (new BuildException($msg));
            return;
        } else {
            return $o;
        }
    }
}

?>

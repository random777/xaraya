<?php
/*
 * $Id: PhingFilterReader.php,v 1.4 2003/03/26 21:53:11 purestorm Exp $
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
import('phing.types.Parameter');

/*
 * A PhingFilterReader is a wrapper class that encloses the classname
 * and configuration of a Configurable FilterReader.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @version   $Revision: 1.4 $ $Date: 2003/03/26 21:53:11 $
 * @access    public
 * @see       FilterReader
 * @package   phing.types
*/
class PhingFilterReader extends DataType {

    var $_className  =	null;
    var $_parameters =	array();
    var $_classpath;

    function setClassName($className) {
        $this->className = (string) $className;
    }

    function getClassName() {
        return $this->className;
    }

    function &createParam() {
        $num = array_push($this->_parameters, new Parameter());
        return $this->_parameters[$num-1];
    }

    /*
     * Set the classpath to load the FilterReader through (attribute).
    */
    function setClasspath(&$classPath) {
        if ( $this->isReference() ) {
            throw  ($this->tooManyAttributes());
            return;
        }
        if ( $this->_classPath === null ) {
            $this->_classPath = &$classPath;
        } else {
            $this->_classPath->append($classPath);
        }
    }

    /*
     * Set the classpath to load the FilterReader through (nested element).
    */
    function &createClasspath() {
        if ( $this->isReference() ) {
            throw ( $this->noChildrenAllowed() );
            return;
        }

        // TODO:
        //	if ( $this->_classPath === null ) {
        //		$this->_classPath = new Path($this->getProject());
        //	return $this->_classPath->createPath();
    }

    function &getClasspath() {
        return $this->_classPath;
    }

    function setClasspathRef(&$r) {
        if ( $this->isReference() ) {
            throw ( $this->tooManyAttributes() );
            return;
        }

        $o = &$this->createClasspath();
        $o->setRefid($r);
    }

    function getParams() {
        // No, no. This isn't an error : we return a COPY :)
        return $this->_parameters;
    }

    /*
     * Makes this instance in effect a reference to another AntFilterReader 
     * instance.
     *
     * <p>You must not set another attribute or nest elements inside
     * this element if you make it a reference.</p>
     *
     * @param r the reference to which this instance is associated
     * @exception BuildException if this instance already has been configured.
    */
    function setRefid(&$r) {
        if ( (count($this->_parameters) !== 0) || ($this->_className !== null) ) {
            throw ( $this->tooManyAttributes() );
            return;
        }

        $o = &$r->getReferencedObject($this->getProject());
        if ( is_a($o, "PhingFilterReader") ) {
            $this->setClassName(get_class($o));
            $this->setClassPath($o->getClassPath());
            $p = $o->getParams();
            if ( count($p) > 0 ) {
                for($i = 0 ; $i<count($p) ; $i++) {
                    $this->addParam($p[$i]);
                }
            }
        } else {
            $msg = $r->getRefId()." doesn\'t refer to a FilterReader";
            throw ( new BuildException($msg) );
            return;
        }

        parent::setRefid($r);
    }
}

?>

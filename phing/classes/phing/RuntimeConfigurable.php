<?php
/*
 * $Id: RuntimeConfigurable.php,v 1.9 2003/04/09 15:58:09 thyrell Exp $
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
 *  Wrapper class that holds the attributes of a Task (or elements
 *  nested below that level) and takes care of configuring that element
 *  at runtime.
 *
 *  <strong>SMART-UP INLINE DOCS</strong>
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.9 $ $Date: 2003/04/09 15:58:09 $
 *  @access    public
 *  @package   phing
 */

class RuntimeConfigurable {

    var $_elementTag = null;
    var $_children = array();
    var $_wrappedObject = null;
    var $_attributes = array();
    var $_characters = "";


    /** @param proxy The element to wrap. */
    function RuntimeConfigurable(&$proxy, $elementTag) {
        $this->_wrappedObject =& $proxy;
        $this->_elementTag = $elementTag;
    }

    function setProxy(&$proxy) {
        $this->_wrappedObject =& $proxy;
    }

    /** Set's the attributes for the wrapped element. */
    function setAttributes($attributes) {
        $this->_attributes = $attributes;
    }

    /** Returns the AttributeList of the wrapped element. */
    function getAttributes() {
        return $this->_attributes;
    }

    /** Adds child elements to the wrapped element. */
    function addChild(&$child) {
        if (!is_a($child, "RuntimeConfigurable")) {
            throw (new RuntimeException("Unexpected type"), __FILE__, __LINE__);
            return;
        }
        $this->_children[] =& $child;
    }

    /** Returns the child with index */
    function &getChild($index) {
        return $this->_children[(int)$index];
    }

    /** Add characters from #PCDATA areas to the wrapped element. */
    function addText($data) {
        $this->_characters .= (string) $data;
    }

    function getElementTag() {
        return $this->_elementTag;
    }


    /** Configure the wrapped element and all children. */
    function maybeConfigure(&$project) {
        $id = null;

        // DataType configured in ProjectConfigurator
        //		if ( is_a($this->_wrappedObject, "DataType") )
        //			return;

        if ($this->_attributes !== null && !empty($this->_attributes)) {
            ProjectConfigurator::configure($this->_wrappedObject, $this->_attributes, $project);

            if (isset($this->_attributes["id"])) {
                $id = $this->_attributes["id"];
            }

            $this->_attributes = null;

            if (strlen($this->_characters) !== 0) {
                ProjectConfigurator::addText($project, $this->_wrappedObject, (string) $this->_characters);
                $this->_characters="";
            }
            if ($id !== null) {
                $project->addReference($id, $this->_wrappedObject);
            }
        }

        if ( is_array($this->_children) && count($this->_children) > 0 ) {
            // Configure all child of this object ...

            for ($i=0; $i<count($this->_children); $i++) {
                $child =&  $this->_children[$i];
                $child->maybeConfigure($project);
                ProjectConfigurator::storeChild($project, $this->_wrappedObject, $child->_wrappedObject, strtolower($child->getElementTag()));
            }
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

<?php
/*
 * $Id: IntrospectionHelper.php,v 1.7 2003/04/09 15:58:09 thyrell Exp $
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

import("phing.types.Reference");

/**
 *  Helper class that collects the methods that a task or nested element
 *  holds to set attributes, create nested elements or hold PCDATA
 *  elements.
 *
 *	<ul>
 *  <li><strong>SMART-UP INLINE DOCS</strong></li>
 *  <li><strong>POLISH-UP THIS CLASS</strong></li>
 *	</ul>
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.7 $ $Date: 2003/04/09 15:58:09 $
 *  @access    public
 *  @package   phing
 */


class IntrospectionHelper {

    /** 
     * Holds the attribute setter methods.
     * 
     * @var     array
     * @access  private
     */
    var $attributeSetters = array();

    /**  
     * Holds methods to create nested elements. 
     *
     * @var     array
     * @access  private
     */
    var $nestedCreators = array();

    /**
     * Holds methods to store configured nested elements. 
     *
     * @var     array
     * @access  private
     */
    var $nestedStorers = array();

    /** 
     * The method to add PCDATA stuff. 
     *
     * @var     function
     * @access  private
     */
    var $methodAddText = null;

    /**
     * The Class that's been introspected.
     *
     * @var     object
     * @access  private
     */
    var $bean;

    /** 
     * Factory method for helper objects. 
     *
     * @param   object  The class to create a Helper for
     * @access  public
     */
    function &getHelper($class) {
        static $ih = null;
        if ($ih === null) {
            $ih = array();
        }
        if (!isset($ih[$class])) {
            $ih[$class] = new IntrospectionHelper($class);
        }
        return $ih[$class];
    }

    /** This function  is in desparate need of a comment!
    Does it somehow figure out whats happening in the phing process? */

    function IntrospectionHelper(&$bean) {
        $this->bean =&$bean;

        $methods = get_class_methods($bean);

        for ($i=0; $i<count($methods); ++$i) {
            $name = (string) $methods[$i];
            if ($name === "setLocation" || $name === "setTaskType" || $name === "addTask") {
                continue;
            }
            if ($name === "addText") {
                $this->methodAddText = $name;
            } else if (strStartsWith("set", $name)) {
                $this->attributeSetters[] = $name;
            } else if (strStartsWith("create", $name)) {
                $this->nestedCreators[] = $name;
            } else if (strStartsWith("addConfigured", $name)) {
                $this->nestedStorers[] = $name;
            } else if (strStartsWith("add", $name)) {
                $this->nestedCreators[] = $name;
            }
        }
        //		print_r($bean);
        //		print_r($this->nestedCreators);
    }


    /** Sets the named attribute. */
    function setAttribute(&$project, &$element, $attributeName, &$value) {
        $as = (string) "set".strtolower($attributeName);
        if (!in_array($as, $this->attributeSetters, true)) {
            $msg = $this->getElementName($project, $element)." doesn't support the '$attributeName' attribute.";
            throw (new BuildException($msg));
        }

        // value is a string representation of a boolean type,
        // convert it to primitive
        if ($project->isBoolean($value)) {
            $value = $project->toBoolean($value);
        }

        { // try to set
            if ($as == "setrefid") {
                $value = new Reference($value);
            }
            $element->$as($value);
        }
        if (catch ("Exception", $exc)) {
            throw (new BuildException($exc->getMessage()));
            return;
        }
    }

    /** Adds PCDATA areas.*/
    function addText(&$project, &$element, $text) {
        if ($this->addText === null) {
            $msg = getElementName($project, $element)." doesn't support nested text data.";
            throw (new BuildException($msg));
            return;
        }
        {// try
            $this->methodAddText($element, $text);
        }
        if (catch ("Exception", $exc)) {
            throw (new BuildException($exc->getMessage()));
            return;
        }
    }

    /** Creates a named nested element. */
    function &createElement(&$project, &$element, $elementName) {
        $creator = null;
        /*
        		for($i=0;count($this->nestedCreators); ++$i) {
        			if (strEndsWith($elementName, $this->nestedCreators[$i])) {
        				$creator = $this->nestedCreators[$i];
        				break;
        			}
        		}
        */
        $creator = (string) "create".strtolower($elementName);

        if (!in_array($creator, $this->nestedCreators, true)) {
            //		if ($creator=== null) {
            $msg = get_class($element) . " doesn't support the '$elementName' creator.";
            throw (new BuildException($msg));
            return;
        }

        { // try to invoke the crator method on object
            $project->log("Calling creator '$creator' in ".get_class($element), PROJECT_MSG_DEBUG);
            $nestedElement =& $element->$creator();

            if (is_a($nestedElement, "DataType")) {
                $nestedElement->setProject($project);
            }

        }
        if (catch ("Exception", $exc)) {
            throw (new BuildException($exc->getMessage()));
            return;
        }
        return $nestedElement;
    }

    /**
     * Creates a named nested element.
     */
    function storeElement(&$project, &$element, &$child, $elementName = null) {
        if ($elementName === null) {
            return;
        }
        $storer = (string) "add".strtolower($elementName);

        if (!in_array($storer, $this->nestedStorers, true)) {
            $project->log("No storer '$storer' in ".get_class($element)."...skipped", PROJECT_MSG_DEBUG);
            return;
        }

        { // try
            $project->log("Storer '$storer' found in ".get_class($element)."...calling", PROJECT_MSG_DEBUG);
            $element->$storer($child);
        }
        if (catch ("Exception", $exc)) {
            throw (new BuildException($exc->getMessage()));
            return;
        }
    }

    /** Does the introspected class support PCDATA? */
    function supportsCharacters() {
        return ($this->methodAddText !== null);
    }

    /** Return all attribues supported by the introspected class. */
    function getAttributes() {
        return $this->attributeSetters;
    }

    /** Return all nested elements supported by the introspected class. */
    function getNestedElements() {
        return $this->nestedTypes;
    }

    function getElementName(&$project, &$element) {
        // FIXME
        // check if class of element is registered with project (tasks & types)
    }

    /** extract the name of a property from a method name - subtracting  a given prefix. */
    function getPropertyName($methodName, $prefix) {
        $start = strlen($prefix);
        return strtolower(substring($methodName, $start));
    }

}
?>

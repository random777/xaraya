<?php
/*
 * $Id: NestedElementFilter.php,v 1.5 2003/04/09 15:58:10 thyrell Exp $
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

import("phing.IntrospectionHelper");

/**
 * The nested element handler class.
 *
 * This class handles the occurance of runtime registered tags like
 * datatypes (fileset, patternset, etc) and it's possible nested tags. It
 * introspects the implementation of the class and sets up the data structures.
 *
 * @author	  Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.5 $ $Date: 2003/04/09 15:58:10 $
 * @access    public
 * @package   phing.parser
 */

class NestedElementFilter extends AbstractFilter {

    /**
     * Reference to the parent object that represents the parent tag
     * of this nested element
     * @var object
     */
    var $parent;

    /**
     * Reference to the child object that represents the child tag
     * of this nested element
     * @var object
     */
    var $child;

    /**
     *  Reference to the parent wrapper object
     *  @var object
     */
    var $parentWrapper;

    /**
     *  Reference to the child wrapper object
     *  @var object
     */
    var $childWrapper = null;

    /**
     *  Reference to the related target object
     *  @var object the target instance
     */
    var $target;

    /**
     *  Constructs a new NestedElement handler and sets up everything.
     *
     *  @param  object  the ExpatParser object
     *  @param  object  the parent handler that invoked this handler
     *  @param  object  the ProjectConfigurator object
     *  @param  object  the parent object this element is contained in
     *  @param  object  the parent wrapper object
     *  @param  object  the target object this task is contained in
     *  @access public
     */
    function NestedElementFilter(&$parser, &$parentHandler, &$configurator, &$parent, &$parentWrapper, &$target) {
        parent::AbstractFilter($parser, $parentHandler);
        $this->configurator =& $configurator;
        $this->parent =& $parent;
        $this->parentWrapper =& $parentWrapper;
        $this->target =& $target;
        /*
        //	  print("Proptype: $propType\n");
        		print("NEF: constructor\n");
        		print("Parent..: ".get_class($this->parent)."\n");
        		print("ParentWrapper: ".get_class($this->parentWrapper)."\n");
        		print("Target: ".get_class($this->target)."\n");
        */
    }

    /**
     * Executes initialization actions required to setup the data structures
     * related to the tag.
     * <p>
     * This includes:
     * <ul>
     * <li>creation of the nested element</li>
     * <li>calling the setters for attributes</li>
     * <li>adding the element to the container object</li>
     * <li>adding a reference to the element (if id attribute is given)</li>
    	 * </ul>
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @throws ExpatParseException if the setup process fails
     * @access public
     */
    function init($propType, $attrs) {
        $configurator =& $this->configurator;
        $project =& $this->configurator->project;

        // introspect the parent class that is custom
        $parentClass = get_class($this->parent);
        $ih =& IntrospectionHelper::getHelper($parentClass);
        { // try
            if (is_a($this->parent, "UnknownElement")) {
                $this->child =& new UnknownElement(strtolower($propType));
                $this->parent->addChild($this->child);
            } else {
                $this->child =& $ih->createElement($project, $this->parent, strtolower($propType));
            }

            $configurator->configureId($this->child, $attrs);

            if ($this->parentWrapper !== null) {
                $this->childWrapper =& new RuntimeConfigurable($this->child, $propType);
                $this->childWrapper->setAttributes($attrs);
                $this->parentWrapper->addChild($this->childWrapper);
            } else {
                $configurator->configure($this->child, $attrs, $project);
                $ih->storeElement($project, $this->parent, $this->child, strtolower($propType));
            }
        }
        if (catch("BuildException", $exc)) {
            throw (new ExpatParseException($exc->getMessage(), $this->parser->getLocation()), __FILE__, __LINE__ );
            return;
        }
    }

    /**
     * Handles character data.
     *
     * @param  string  the CDATA that comes in
     * @throws ExpatParseException if the CDATA could not be set-up properly
     * @access public
     */
    function characters($data) {
        $configurator =& $this->configurator;
        $project =& $this->configurator->project;

        if ($this->parentWrapper === null) {
            { // try
                $configurator->addText($project, $this->child, $data);
            }
            if (catch("BuildException", $exc)) {
                throw ( new ExpatParseException($exc->getMessage(), $this->parser->getLocation()), __FILE__, __LINE__ );
                return;
            }
        } else {
            $this->childWrapper->addText($data);
        }
    }

    /**
     * Checks for nested tags within the current one. Creates and calls
     * handlers respectively.
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @access public
     */
    function startElement($name, $attrs) {
        //var_dump($this->child);
        $neh =& new NestedElementFilter($this->parser, $this, $this->configurator, $this->child, $this->childWrapper, $this->target);
        $neh->init($name, $attrs);
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

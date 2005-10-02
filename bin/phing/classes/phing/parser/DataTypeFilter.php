<?php
/*
 * $Id: DataTypeFilter.php,v 1.5 2003/04/09 15:58:10 thyrell Exp $
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

import('phing.RuntimeConfigurable');

/**
 * Configures a Project (complete with Targets and Tasks) based on
 * a XML build file.
 * <p>
 * Design/ZE2 migration note:
 * If PHP would support nested classes. All the phing.parser.*Filter
 * classes would be nested within this class
 *
 * @author	  Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.5 $ $Date: 2003/04/09 15:58:10 $
 * @access    public
 * @package   phing.parser
 */

class DataTypeFilter extends AbstractFilter {

    var $target;
    var $element;
    var $wrapper = null;

    /**
     * Constructs a new DatatypeFilter and sets up everything.
     *
     * @param  object  the ExpatParser object
     * @param  object  the parent handler that invoked this handler
     * @param  object  the ProjectConfigurator object
     * @param  object  the target object this task is contained in
     * @access public
     */
    function DataTypeFilter(&$parser, &$parentHandler, &$configurator, &$target) {
        parent::AbstractFilter($parser, $parentHandler);
        $this->target =& $target;
        $this->configurator =& $configurator;
    }

    /**
     * Executes initialization actions required to setup the data structures
     * related to the tag.
     * <p>
     * This includes:
     * <ul>
     * <li>creation of the datatype object</li>
     * <li>calling the setters for attributes</li>
     * <li>adding the type to the target object if any</li>
     * <li>adding a reference to the task (if id attribute is given)</li>
    	 * </ul>
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @throws ExpatParseException if attributes are incomplete or invalid
     * @access public
     */
    function init($propType, $attrs) {
        // shorthands
        $project =& $this->configurator->project;
        $configurator =& $this->configurator;

        {//try
            $this->element =& $project->createDataType($propType);

            if ($this->element === null) {
                throw (new BuildException("Unknown data type $propType"));
            }

            if ($this->target !== null) {
                $this->wrapper =& new RuntimeConfigurable($this->element, $propType);
                $this->wrapper->setAttributes($attrs);
                $this->target->addDataType($this->wrapper);
            } else {
                $configurator->configure($this->element, $attrs, $project);
                $configurator->configureId($this->element, $attrs);
            }

        }
        if(catch ("BuildException", $exc)) {
            throw (new ExpatParseException($exc->getMessage(), $this->parser->getLocation()));
            return;
        }
    }

    /**
     * Handles character data.
     *
     * @param  string  the CDATA that comes in
     * @access public
     */
    function characters($data) {
        $project =& $this->configurator->project;
        {//try
            $this->configurator->addText($project, $this->element, $data);
        }
        if (catch("BuildException", $exc)) {
            throw (new ExpatParseException($exc->getMessage(), $this->parser->getLocation()));
            return;
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
        $nef =& new NestedElementFilter($this->parser, $this, $this->configurator, $this->element, $this->wrapper, $this->target);
        $nef->init($name, $attrs);
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

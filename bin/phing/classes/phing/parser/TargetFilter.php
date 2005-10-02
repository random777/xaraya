<?php
/*
 * $Id: TargetFilter.php,v 1.5 2003/04/09 15:58:10 thyrell Exp $
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
 * The target handler class.
 *
 * This class handles the occurance of a <target> tag and it's possible
 * nested tags (datatypes and tasks).
 *
 * @author	  Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.5 $ $Date: 2003/04/09 15:58:10 $
 * @access    public
 * @package   phing.parser
 */

class TargetFilter extends AbstractFilter {

    /**
     * Reference to the target object that represents the currently parsed
     * target.
     * @var object the target instance
     */
    var $target;

    /**
     * The phing project configurator object
     * @var object the ProjectConfigurator
     */
    var $configurator;

    /**
     * Constructs a new TargetFilter
     *
     * @param  object  the ExpatParser object
     * @param  object  the parent handler that invoked this handler
     * @param  object  the ProjectConfigurator object
     * @access public
     */
    function TargetFilter(&$parser, &$parentHandler, &$configurator) {
        $this->configurator =& $configurator;
        parent::AbstractFilter($parser, $parentHandler);
    }

    /**
     * Executes initialization actions required to setup the data structures
     * related to the tag.
     * <p>
     * This includes:
     * <ul>
     * <li>creation of the target object</li>
     * <li>calling the setters for attributes</li>
     * <li>adding the target to the project</li>
     * <li>adding a reference to the target (if id attribute is given)</li>
     * </ul>
     *
     * @param  string  the tag that comes in
     * @param  array   attributes the tag carries
     * @throws ExpatParseException if attributes are incomplete or invalid
     * @access public
     */
    function init($tag, $attrs) {
        $name = null;
        $depends = "";
        $ifCond = null;
        $unlessCond = null;
        $id = null;
        $description = null;

        foreach($attrs as $key => $value) {
            if ($key==="name") {
                $name = (string) $value;
            } else if ($key==="depends") {
                $depends = (string) $value;
            } else if ($key==="if") {
                $ifCond = (string) $value;
            } else if ($key==="unless") {
                $unlessCond = (string) $value;
            } else if ($key==="id") {
                $id = (string) $value;
            } else if ($key==="description") {
                $description = (string)$value;
            } else {
                throw (new ExpatParseException("Unexpected attribute '$key'", $this->parser->location));
                ;
            }
        }

        if ($name === null) {
            throw (new ExpatParseException("target element appears without a name attribute",  $this->parser->location));
        }

        // shorthand
        $project =& $this->configurator->project;

        $this->target =& new Target();
        $this->target->setName($name);
        $this->target->setIf($ifCond);
        $this->target->setUnless($unlessCond);
        $this->target->setDescription($description);

        $project->addTarget($name, $this->target);

        if ($id !== null && $id !== "") {
            $project->addReference($id, $this->target);
        }
        // take care of dependencies
        if (strlen($depends) > 0) {
            $this->target->setDepends($depends);
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
        // shorthands
        $project =& $this->configurator->project;
        $types = $project->getDataTypeDefinitions();

        if (isset($types[$name]) && ($types[$name] !== null)) {
            $th =& new DataTypeFilter($this->parser, $this, $this->configurator, $this->target);
            $th->init($name, $attrs);
        } else {
            $tmp =& new TaskFilter($this->parser, $this, $this->configurator, $this->target, $this->target);
            $tmp->init($name, $attrs);
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

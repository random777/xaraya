<?php
/*
 * $Id: ProjectConfigurator.php,v 1.11 2003/06/04 12:22:36 purestorm Exp $
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

import("phing.system.io.BufferedReader");
import("phing.system.io.FileReader");
import("phing.BuildException");
import("phing.parser.*");

/**
 * The datatype handler class.
 *
 * This class handles the occurance of registered datatype tags like
 * Fileset
 *
 * @author	  Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.11 $ $Date: 2003/06/04 12:22:36 $
 * @access    public
 * @package   phing.parser
 */
class ProjectConfigurator {

    var $project;
    var $buildFile;
    var $buildFileParent;
    var $locator;

    /**
     * Static call to ProjectConfigurator. Use this to configure a
     * project. Do not use the new operator.
     *
     * @param  object  the Project instance this configurator should use
     * @param  object  the buildfile object the parser should use
     * @access public
     */
    function configureProject(&$project, &$buildFile) {
        $pc = new ProjectConfigurator($project, $buildFile);
        $pc->_parse();
    }

    /**
     * Constructs a new ProjectConfigurator object
     * This constructor is private. Use a static call to
     * <code>configureProject</code> to configure a project.
     *
     * @param  object  the Project instance this configurator should use
     * @param  object  the buildfile object the parser should use
     * @access private
     */
    function ProjectConfigurator(&$project, &$buildFile) {
        $this->project =& $project;
        $this->buildFile = new File($buildFile->getAbsolutePath());
        $this->buildFileParent = new File($this->buildFile->getParent());
    }

    /**
     * Creates the ExpatParser, sets root handler and kick off parsing
     * process.
     *
     * @throws BuildException if there is any kind of execption during
     *         the parsing process
     * @access private
     */
    function _parse() {
        { // try
            $reader = new BufferedReader(new FileReader($this->buildFile));
            $reader->open();
            $parser = new ExpatParser($reader);
            $parser->parserSetOption(XML_OPTION_CASE_FOLDING,0);
            $parser->setHandler(new RootFilter($parser, $this));
            $this->project->log("parsing buildfile ".$this->buildFile->getName(), PROJECT_MSG_VERBOSE);
            $parser->parse();
            $reader->close();
        }

        if (catch('ExpatParseException', $exc)) {
            throw (new BuildException($exc->getMessage()));
            return;
        }

        if (catch('FileNotFoundException', $exc)) {
            throw (new BuildException($exc->getMessage()));
            return;
        }

        if (catch('IOException', $exc)) {
            throw (new BuildException("Error reading project file", $exc->getMessage()));
            return;
        }
    }

    /**
     * Configures an element and resolves eventually given properties.
     *
     * @param  object  the element to configure
     * @param  array   the element's attributes
     * @param  object  the project this element belongs to
     * @throws RuntimeException if arguments are not valid
     * @throws BuildException if attributes can not be configured
     * @access public
     */
    function configure(&$target, &$attrs, &$project) {
        if (!is_object($target)) {
            throw (new RuntimeException("Unsupported argument type, needs to be an object"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $bean = get_class($target);
        $ih =& IntrospectionHelper::getHelper($bean);

        foreach ($attrs as $key => $value) {
            if ($key == 'id') {
                continue;
                //throw (new BuildException("Id must be set Extermnally")); return;
            }
            $setter = "set".ucfirst($key);
            $value = ProjectConfigurator::replaceProperties($project, $value, $project->getProperties());
            { // try to set the attribute
                $ih->setAttribute($project, $target, strtolower($key), $value);
            }
            if (catch ("BuildException", $be)) {
                // id attribute must be set externally
                if ($key !== "id") {
                    throw (new BuildException($be->getMessage()));
                    return;
                }
            }
        }
    }

    /**
     * Configures the #CDATA of an element.
     *
     * @param  object  the project this element belongs to
     * @param  object  the element to configure
     * @param  string  the element's #CDATA
     * @access public
     */
    function addText(&$project, &$target, $text = null) {
        if ($text === null || strlen(trim($text)) === 0) {
            return;
        }
        $ih =& IntrospectionHelper::getHelper(get_class($target));
        $ih->addText($project, $target, $text);
    }

    /**
     * Stores a configured child element into its parent object
     *
     * @param  object  the project this element belongs to
     * @param  object  the parent element
     * @param  object  the child element
     * @param  string  the XML tagname
     * @access public
     */
    function storeChild(&$project, &$parent, &$child, $tag) {
        $ih =& IntrospectionHelper::getHelper(get_class($parent));
        $ih->storeElement($project, $parent, $child, $tag);
    }

    /**
     * Replace ${} style constructions in the given value with the
     * string value of the corresponding data types. This method is
     * static.
     *
     * @param  object  the project that should be used for property look-ups
     * @param  string  the string to be scanned for property references
     * @param  array   proeprty keys
     * @return string  the replaced string or <code>null</code> if the string
     *                 itself was null
     */
    function replaceProperties(&$project, $value, $keys) {
        if ($value === null) {
            return null;
        }

        $fragments	= array();
        $propertyRefs = array();

        // parse string into frags and refs
        ProjectConfigurator::parsePropertyString($value, $fragments, $propertyRefs);
        $sb = "";

        $i = $fragments;
        $j = $propertyRefs;
        while (count($i)) {
            $fragment = array_shift($i);
            if ($fragment === null) {
                $propertyName = array_shift($j);
                if (!isset($keys[$propertyName])) {
                    $project->log("Property \${$propertyName} has not been set", PROJECT_MSG_VERBOSE);
                }
                $fragment = isset($keys[$propertyName]) ? (string) $keys[$propertyName] : "\${$propertyName}";
            }
            $sb .= $fragment;
        }
        return (string) $sb;
    }

    /**
     * This method will parse a string containing ${value} style
     * property values into two lists. The first list is a collection
     * of text fragments, while the other is a set of string property names
     * null entries in the first list indicate a property reference from the
     * second list.
     *
     * @param  string  the string to be scanned for property references
     * @param  array   the found fragments
     * @param  array   the found refs
     */
    function parsePropertyString($value, &$fragments, &$propertyRefs) {
        $prev = 0;
        $pos  = 0;
        while (($pos = strIndexOf('$', $value, $prev)) >= 0) {
            if ($pos > $prev) {
                array_push($fragments, substring($value, $prev, $pos-1));
            }
            if ($pos === (strlen($value) - 1)) {
                array_push($fragments, '$');
                $prev = $pos + 1;
            }
            elseif ($value{$pos+1} !== '{' ) {

                // the string positions were changed to value-1 to correct
                // a fatal error coming from function substring()
                array_push($fragments, substring($value, $pos, $pos + 1));
                $prev = $pos + 2;
            }
            else {
                $endName = strIndexOf('}', $value, $pos);
                if ($endName < 0) {
                    throw (new BuildException("Syntax error in property: $value"));
                    return;
                }
                $propertyName = substring($value, $pos + 2, $endName-1);
                array_push($fragments, null);
                array_push($propertyRefs, $propertyName);
                $prev = $endName + 1;
            }
        }

        if ($prev < strlen($value)) {
            array_push($fragments, substring($value, $prev));
        }
    }


    /**
     * Scan Attributes for the id attribute and maybe add a reference to
     * project.
     *
     * @param object the element's object
     * @param array  the element's attributes
     */
    function configureId(&$target, $attr) {
        if (isset($attr['id']) && $attr['id'] !== null) {
            $this->project->addReference($attr['id'], $target);
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

<?php
// {{{ Header
/*
 * -File       $Id: PropertyTask.php,v 1.35 2003/05/19 02:55:50 openface Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2002, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.Task');
import('phing.system.util.Properties');

/**
 *  PropertyTask setting properties in buildfiles
 *
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *  @version  $Revision: 1.35 $ $Date: 2003/05/19 02:55:50 $
 *  @package  phing.tasks.system
 */

class PropertyTask extends Task {

    var $name       = null;  // name of the property
    var $value      = null;  // value of the property
    var $reference  = null;  // Reference
    var	$env		= null;	 // Environment
    var $file = null;
    var $ref  = null;

    var $override = false;   // allow to override properties

    /**
     * Accessor. Sets a the name of current property component
     *
     * @param    string     Name, of property
     * @returns  boolean    Always true
     * @access   public
     * @author   Andreas Aderhold, andi@binarycloud.com
     */
    function setName($_name) {
        $this->name = (string) $_name;
    }

    function getName() {
        return $this->name;
    }


    /**
     * Accessor. Sets a the value of current property component
     *
     * @param    mixed      Value of name, all scalars allowed
     * @returns  boolean    Always true
     * @access   public
     * @author   Andreas Aderhold, andi@binarycloud.com
     */
    function setValue($_value) {
        $this->value = (string) $_value;
    }


    function getValue() {
        return $this->value;
    }


    function setFile($file) {
        if (isInstanceOf($file, "File")) {
            $file = $file->getPath();
        }
        $this->file = new File((string)$file);
    }


    function getFile() {
        return (object) $this->file;
    }


    function setRefid(&$ref) {
        if (!isInstanceOf($ref, "Reference")) {
            throw (new RuntimeException("Illegal argument to function, expected Reference object"), __FILE__, __LINE__);
            return;
        }
        $this->reference =& $ref;
    }

    function getRefid() {
        return $this->reference;
    }

    function setEnvironment($env) {
        $this->env = (string) $env;
    }

    function getEnvironment() {
        return $this->env;
    }

    /**
     * Accessors for the attribute "override"
     *
     * @author      Manuel Holtgrewe
     */
    function setOverride($bool) {
        $this->override = $bool;
    }

    function getOverride() {
        return (boolean) $this->override;
    }



    function toString() {
        return $this->value === null ? "" : $this->value;
    }


    function main() {
        if ($this->name !== null) {
            if ($this->value === null && $this->ref === null) {
                // FIXME ADD locator
                throw (new BuildException("You must specify value or refid with the name attribute"));
                return;
            }
        } else {
            if ($this->file === null && $this->env === null ) {
                throw (new BuildException("You must specify file or environment when not using the name attribute"));
                return;
            }
        }

        if (($this->name !== null) && ($this->value !== null)) {
            $this->_addProperty($this->name, $this->value);
        }

        if ($this->file !== null) {
            $this->_loadFile($this->file);
        }

        if ( $this->env !== null ) {
            $this->_loadEnvironment($this->env);
        }

        if (($this->name !== null) && ($this->ref !== null)) {
            // get the refereced property
            $obj =& $this->reference->getReferencedObject($this->getProject());
            if ($obj !== null) {
                $this->_addProperty($this->name, $obj->toString());
            }
        }
    }

    function _loadEnvironment($prefix) {
        global	$HTTP_ENV_VARS;

        $props = new Properties();
        if ( substr($prefix, strlen($prefix)-1) === "." ) {
            $prefix .= ".";
        }
        $this->log("Loading Environment $prefix", PROJECT_MSG_VERBOSE);
        $osEnv = $HTTP_ENV_VARS;
        foreach($osEnv as $key => $value)
        $props->setProperty("$prefix.$key", $value);
        $this->_addProperties($props);
    }

    function _addProperties(&$props) {
        $this->_resolveAllProperties($props);
        $k = $props->keys();
        while(count($k)) {
            $name = array_shift($k);
            $value = $props->getProperty($name);
            $v = (string) ProjectConfigurator::replaceProperties($this->project, $value, $this->project->getProperties());
            $this->_addProperty($name, $v);
        }
    }

    function _addProperty($name, $value) {
        $this->log("Adding property \"$name\" with value \"$value\"", PROJECT_MSG_DEBUG);
        if ($this->project->getProperty($name) === null) {
            $this->project->setProperty($name, $value);
        } else if ($this->override === true) {
            $this->project->setProperty($name, $value);
            $this->log("Property \"$name\" override with value \"$value\"", PROJECT_MSG_VERBOSE);
        } else {
            $this->log("Override ignored for $name", PROJECT_MSG_VERBOSE);
        }
    }

    function _loadFile(&$file) {
        $props = new Properties();
        $this->log("Loading ". $file->getAbsolutePath(), PROJECT_MSG_VERBOSE);
        { // try to load file
            if ($file->exists()) {
                $props->load($file);
                $this->_addProperties($props);
            } else {
                $this->log("Unable to find property file: ". $file->getAbsolutePath() ."... skipped", PROJECT_MSG_VERBOSE);
            }
        }
        if (catch("IOException",  $ioe)) {
            throw (new BuildException($ioe->getMessage), __FILE__, __LINE__);
            return;
        }
    }


    function _resolveAllProperties(&$props) {
        if (!isInstanceOf($props, "Properties")) {
            throw (new RuntimeException("Expected Properties object in argument, got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $keys = $props->keys();

        while(count($keys)) {

            $name     = array_shift($keys);
            $value    = $props->getProperty($name);
            $resolved = false;

            while(!$resolved) {
                $fragments = array();
                $propertyRefs = array();
                ProjectConfigurator::parsePropertyString($this->value, $fragments, $propertyRefs);
                $resolved = true;
                if (count($propertyRefs) !== 0) {
                    $sb = "";
                    $i = $fragments;
                    $j = $propertyRefs;

                    while(count($i)) {
                        $fragment = (string) array_shift($i);
                        if ($fragment === null) {
                            $propertyName = (String) array_shift($j);
                            if ($propertyName === $this->name) {
                                throw (new BuildException("Property {$this->name} was circularly defined."));
                                return;
                            }
                            $fragment =& $this->getProject();
                            $fragment = $fragment->getProperty($propertyName);
                            if ($fragment === null) {
                                if ($props->containsKey($propertyName)) {
                                    $fragment = $props->getProperty($propertyName);
                                    $resolved = false;
                                } else {
                                    $fragment = "\${".$propertyName."}";
                                }
                            }
                        }
                        $sb .= $fragment;
                    }
                    $value = (string) $sb;
                    $props->setProperty($name, $value);
                    $this->log("Resolved Property \"$name\" to \"$value\"", PROJECT_MSG_DEBUG);
                }
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

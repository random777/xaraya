<?php
// {{{ Header
/*
 * -File       $Id: AvailableTask.php,v 1.11 2003/04/09 15:58:12 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell  
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.BuildException");
import("phing.tasks.system.condition.ConditionBase");

/**
 *  <available> task.
 *
 *  Note: implements condition interface (see condition/Condition.php)
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.11 $ $Date: 2003/04/09 15:58:12 $
 *  @access    public
 *  @package   phing.tasks.system
 */
class AvailableTask extends Task {

    var $property = null;
    var $value = "true";
    var $resource = null;
    var $type = null;
    var $filepath = null;

    function setProperty($property) {
        $this->property = (string) $property;
    }

    function setValue($value) {
        $this->value = (string) $value;
    }

    function setFile($file) {
        if (is_a($file, "File")) {
            $file = $file->getPath();
        }
        $this->file = new File((string)$file);
    }

    function setResource($resource) {
        $this->resource = (string) $resource;
    }

    function setType($type) {
        $this->type = (string) strtolower($type);
    }

    function main() {
        if ($this->property === null) {
            throw (new BuildException("property attribute is required", $this->location), __FILE__, __LINE__);
        }
        if ($this->evaluate()) {
            $this->project->setProperty($this->property, $this->value);
        }
    }

    function evaluate() {
        if ($this->file === null && $this->resource === null) {
            throw (new BuildException("At least one of (file|resource) is required", $this->location), __FILE__, __LINE__);
            return;
        }

        if ($this->type !== null) {
            if ($this->type !== "file" && $this->type !== "dir") {
                throw (new BuildException("Type must be one of either dir or file"), __FILE__, __LINE__);
                return;
            }
        }
        if (($this->file !== null) && !$this->_checkFile()) {
            $this->log("Unable to find " . $this->file->toString() . " to set property " . $this->property, PROJECT_MSG_VERBOSE);
            return false;
        }

        if (($this->resource !== null) && !$this->_checkResource($this->resource)) {
            $this->log("Unable to load resource " . $this->resource . " to set property " . $this->property, PROJECT_MSG_VERBOSE);
            return false;
        }

        return true;
    }

    // this is prepared for the path type
    function _checkFile() {
        if ($this->filepath === null) {
            return $this->_checkFile1($this->file);
        } else {
            $paths = $this->filepath->listDir();
            for($i = 0; $i < count($paths); ++$i) {
                $this->log("Searching " . $paths[$i], PROJECT_MSG_VERBOSE);
                $tmp = new File($paths[$i], $this->file->getName());
                if($tmp->isFile()) {
                    return true;
                }
            }
        }
        return false;
    }

    function _checkFile1(&$file) {
        if ($this->type !== null) {
            if ($this->type === "dir") {
                return $file->isDirectory();
            } else if ($this->type === "file") {
                return $file->isFile();
            }
        }
        return $file->exists();
    }

    function _checkResource($resource) {
        return $this->_checkFile1(new File(getResourcePath((string) $resource)));
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

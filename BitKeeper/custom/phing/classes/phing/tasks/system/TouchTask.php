<?php
// {{{ Header
/*
 * -File       $Id: TouchTask.php,v 1.16 2003/04/09 15:59:23 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.Task');
import("phing.BuildException");
import("phing.Project");
import("phing.util.DirectoryScanner");
import("phing.types.Fileset");
import("phing.util.FileUtils");
import("phing.system.io.File");
import("phing.system.io.IOException");

/**
 * Touch a file and/or fileset(s); corresponds to the Unix touch command.
 *
 * If the file to touch doesn't exist, an empty one is created.
 *
 * @version $Revision: 1.16 $ $Date: 2003/04/09 15:59:23 $
 * @package phing.tasks.system
 */
class TouchTask extends Task {

    var $file	  = null;
    var $millis	= -1;
    var $dateTime  = null;
    var $filesets  = array();
    var $fileUtils = null;

    function TouchTask() {
        $this->fileUtils = FileUtils::newFileUtils();
    }

    /**
     * Sets a single source file to touch.  If the file does not exist
     * an empty file will be created.
     */
    function setFile($file) {
        if (is_a($file, "File")) {
            $file = $file->getPath();
        }
        $this->file = new File((string) $file);
    }

    /**
     * the new modification time of the file
     * in milliseconds since midnight Jan 1 1970.
     * Optional, default=now
     */
    function setMillis($millis) {
        $this->millis = (int) $millis;
    }

    /**
     * the new modification time of the file
     * in the format MM/DD/YYYY HH:MM AM or PM;
     * Optional, default=now
     */
    function setDatetime($dateTime) {
        $this->dateTime = (string) $dateTime;
    }

    /**
     * Nested creator, adds a set of files (nested fileset attribute).
     */
    function &createFileset() {
        $num = array_push($this->filesets, new Fileset());
        return $this->filesets[$num-1];
    }

    /**
     * Execute the touch operation.
     */
    function main() {
        $savedMillis = $this->millis;

        if ($this->file === null && count($this->filesets) === 0) {
            throw (new BuildException("Specify at least one source - a file or a fileset."), __FILE__, __LINE__);
            return;
        }

        if ($this->file !== null && $this->file->exists() && $this->file->isDirectory()) {
            throw (new BuildException("Use a fileset to touch directories."), __FILE__, __LINE__);
            return;
        }

        { // try to touch file
            if ($this->dateTime !== null) {
                $this->setMillis(strtotime($this->dateTime));
                if ($this->millis < 0) {
                    throw (new BuildException("Date of {$this->dateTime} results in negative milliseconds value relative to epoch (January 1, 1970, 00:00:00 GMT)."));
                    return;
                }
            }
            $this->_touch();
        }
        if (catch ("Exception", $ex)) {
            throw (new BuildException($ex->getMessage(), $this->location));
            return;
        }
        else {
            $this->millis = $savedMillis;
        }
    }

    /**
     * Does the actual work.
     */
    function _touch() {
        if ($this->file !== null) {
            if (!$this->file->exists()) {
                $this->log("Creating " . $this->file->toString(), PROJECT_MSG_INFO);
                { // try to create file
                    $this->file->createNewFile();
                }
                if (catch ("IOException", $ioe)) {
                    throw (new BuildException("Could not create " . $this->file, $this->location), __FILE__, __LINE__);
                    return;
                }
            }
        }

        $resetMillis = false;
        if ($this->millis < 0) {
            $resetMillis = true;
            $this->millis = System::currentTimeMillis();
        }

        if ($this->file !== null) {
            $this->_touchFile($this->file);
        }

        // deal with the filesets
        for ($i = 0; $i < count($this->filesets); ++$i) {
            $fs =& $this->filesets[$i];
            $ds =& $fs->getDirectoryScanner($this->getProject());
            $fromDir =& $fs->getDir($this->getProject());

            $srcFiles = $ds->getIncludedFiles();
            $srcDirs = $ds->getIncludedDirectories();

            for ($j = 0; $j < count($srcFiles); ++$j) {
                $this->_touchFile(new File($fromDir, (string) $srcFiles[$j]));
            }

            for ($j = 0; $j < count($srcDirs) ; ++$j) {
                $this->_touchFile(new File($fromDir, (string) $srcDirs[$j]));
            }
        }

        if ($resetMillis) {
            $this->millis = -1;
        }
    }

    function _touchFile(&$file) {
        if ( !$file->canWrite() ) {
            throw (new BuildException("Can not change modification date of read-only file " . $file->toString()), __FILE__, __LINE__);
            return;
        }
        $file->setLastModified($this->millis);
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

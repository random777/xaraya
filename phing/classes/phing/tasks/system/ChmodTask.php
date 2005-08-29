<?php
// {{{ Header
/*
 * -File       $Id: ChmodTask.php,v 1.4 2003/02/25 17:38:31 openface Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2003, Manuel Holtgrewe
 * -Author     Manuel Holtgrewe, grin@gmx.net
 */
// }}}

import("phing.Task");
import("phing.BuildException");
import("phing.Project");
import("phing.util.DirectoryScanner");
import("phing.types.Fileset");
import("phing.system.io.FileSystem");
import("phing.system.io.File");
import("phing.system.io.IOException");

/**
 * Task that changes the permissions on a file/directory.
 *
 * @author Manuel Holtgrewe, grin@gmx.net
 */
class ChmodTask extends Task {

    // {{{ properties
    /* all private */
    var $file	  = null;
    var $mode     = null;

    var $filesets   = array();

    var $filesystem = null;
    // }}}

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

    function setMode($str) {
        $this->mode = $str;
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
        // Check Parameters
        if (!$this->_checkParams()) {
            return false;
        }

        $this->_chmod();
    }

    function _checkParams() {
        $result = true;

        if ($this->file === null and count($this->filesets) === 0) {
            $result = false;
            throw (new BuildException("Specify at least one source - a file or a fileset."), __FILE__, __LINE__);
        }

        if ($this->mode === null) {
            $result = false;
            throw (new BuildException("You have to specify a mode to change to."), __FILE__, __LINE__);
        }

        // check for mode to be in the correct format

        if (!preg_match("/([0-7]){3}/", $this->mode)) {
            $result = false;
            throw (new BuildException("You have specified an invalid mode"), __FILE__, __LINE__);
        }

        return $result;
    }

    /**
     * Does the actual work.
     * 
     * TODO: do some more catching
     */
    function _chmod() {
        $mode = octdec("0". $this->mode);

        // one file
        if ($this->file !== null) {
            $this->_chmodFile($this->file, $mode);
        }

        // filesets
        for ($i = 0; $i < count($this->filesets); ++$i) {
            $fs =& $this->filesets[$i];
            $ds =& $fs->getDirectoryScanner($this->getProject());

            if (catch("BuildException", $e)) {
                throw(new BuildException($e->getMessage()), __LINE__, __FILE__);
                return false;
            }

            $fromDir =& $fs->getDir($this->getProject());

            $srcFiles = $ds->getIncludedFiles();
            $srcDirs = $ds->getIncludedDirectories();

            for ($j = 0; $j < count($srcFiles); ++$j) {
                $this->_chmodFile(new File($fromDir, (string) $srcFiles[$j]), $mode);
            }

            for ($j = 0; $j < count($srcDirs) ; ++$j) {
                $this->_chmodFile(new File($fromDir, (string) $srcDirs[$j]), $mode);
            }
        }

    }

    function _chmodFile($file, $mode) {
        if ( !$file->exists() ) {
            throw (new BuildException("The file " . $file->toString() . " does not exist"), __FILE__, __LINE__);
            return;
        }
        
        $this->log("Changing file mode on '" . $file->getPath() ."' to " . vsprintf("%o", $mode));
        $file->SetMode($mode);
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

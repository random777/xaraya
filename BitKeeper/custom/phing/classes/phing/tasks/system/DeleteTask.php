<?php
// {{{ Header
/*
 * -File       $Id: DeleteTask.php,v 1.15 2003/04/09 15:58:12 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.Task');

/**
 *  Deletes a file or directory, or set of files defined by a fileset.
 *  @package   phing.tasks.system
 */

class DeleteTask extends Task {

    var $file         = null;
    var $dir          = null;
    var $filesets     = array();
    var $includeEmpty = false;

    var $quiet        = false;
    var $failonerror  = true;
    var $verbosity    = PROJECT_MSG_INFO;

    /** Set the name of a single file to be removed. */
    function setFile($file) {
        if (is_a($file, "File")) {
            $file = $file->getPath();
        }
        $this->file = new File((string)$file);
    }

    /** Set the directory from which files are to be deleted */
    function setDir($dir) {
        if (is_a($dir, "File")) {
            $dir = $dir->getPath();
        }
        $this->dir = new File((string)$dir);
    }

    /** Used to force listing of all names of deleted files. */
    function setVerbose($verbosity) {
        if ($verbosity) {
            $this->verbosity = PROJECT_MSG_INFO;
        } else {
            $this->verbosity = PROJECT_MSG_VERBOSE;
        }
    }

    /**
     * If the file does not exist, do not display a diagnostic
     * message or modify the exit status to reflect an error.
     * This means that if a file or directory cannot be deleted,
     * then no error is reported. This setting emulates the
     * -f option to the Unix rm command. Default is false
    * meaning things are verbose
     */
    function setQuiet($bool) {
        $this->quiet = $bool;
        if ($this->quiet) {
            $this->failonerror = false;
        }
    }

    /** this flag means 'note errors to the output, but keep going' */
    function setFailOnError($bool) {
        $this->failonerror = $bool;
    }


    /** Used to delete empty directories.*/
    function setIncludeEmptyDirs($includeEmpty) {
        $this->includeEmpty = (boolean) $includeEmpty;
    }

    /** Nested creator, adds a set of files (nested fileset attribute). */
    function &createFileset() {
        $num = array_push($this->filesets, new Fileset());
        return $this->filesets[$num-1];
    }


    /** Delete the file(s). */
    function main() {
        if ($this->file === null && $this->dir === null && count($this->filesets) === 0) {
            throw (new BuildException("At least one of the file or dir attributes, or a fileset element, must be set."));
            return;
        }

        if ($this->quiet && $this->failonerror) {
            throw (new BuildException("quiet and failonerror cannot both be set to true", $this->location));
            return;
        }

        // delete a single file
        if ($this->file !== null) {
            if ($this->file->exists()) {
                if ($this->file->isDirectory()) {
                    $this->log("Directory " . $this->file->getAbsolutePath() . " cannot be removed using the file attribute. Use dir instead.");
                } else {
                    $this->log("Deleting: " . $this->file->getAbsolutePath());

                    if (!$this->file->delete()) {
                        $message = "Unable to delete file " . $this->file->getAbsolutePath();
                        if($this->failonerror) {
                            throw (new BuildException($message));
                            return;
                        } else {
                            $this->log($message, $this->quiet ? PROJECT_MSG_VERBOSE : PROJECT_MSG_WARN);
                        }
                    }
                }
            } else {
                $this->log("Could not find file " . $this->file->getAbsolutePath() . " to delete.",PROJECT_MSG_VERBOSE);
            }
        }

        // delete the directory
        if ($this->dir !== null && $this->dir->exists() && $this->dir->isDirectory()) {
            if ($this->verbosity === PROJECT_MSG_VERBOSE) {
                $this->log("Deleting directory " . $this->dir->getAbsolutePath());
            }
            $this->_removeDir($this->dir);
        }

        // delete the files in the filesets
        for ($i=0; $i<count($this->filesets); ++$i) {
            $fs =& $this->filesets[$i];
            {
                $ds =& $fs->getDirectoryScanner($this->project);
                $files = $ds->getIncludedFiles();
                $dirs  = $ds->getIncludedDirectories();
                $this->_removeFiles($fs->getDir($this->project), $files, $dirs);
            }
            if ( catch("BuildException", $be)) {
                    // directory doesn't exist or is not readable
                    if ($this->failonerror) {
                        throw($be);
                    } else {
                        $this->log($be->getMessage(), $this->quiet ? PROJECT_MSG_VERBOSE : PROJECT_MSG_WARN);
                    }
                }
        }
    }

    function _removeDir(&$d) {
        $list = $d->listDir();
        if ($list === null) {
            $list = array();
        }

        for ($i = 0; $i < count($list); ++$i) {
            $s = $list[$i];
            $f = new File($d, $s);
            if ($f->isDirectory()) {
                $this->_removeDir($f);
            } else {
                $this->log("Deleting " . $f->getAbsolutePath(), $this->verbosity);
                if (!$f->delete()) {
                    $message = "Unable to delete file " . $f->getAbsolutePath();
                    if($this->failonerror) {
                        throw(new BuildException($message));
                    } else {
                        $this->log($message, $this->quiet ? PROJECT_MSG_VERBOSE : PROJECT_MSG_WARN);
                    }
                }
            }
        }
        $this->log("Deleting directory " . $d->getAbsolutePath(), $this->verbosity);
        if (!$d->delete()) {
            $message = "Unable to delete directory " . $this->dir->getAbsolutePath();
            if($this->failonerror) {
                throw( new BuildException($message) );
            } else {
                $this->log($message, $this->quiet ? PROJECT_MSG_VERBOSE : PROJECT_MSG_WARN);
            }
        }
    }

    /**
     * remove an array of files in a directory, and a list of subdirectories
     * which will only be deleted if 'includeEmpty' is true
     * @param d directory to work from
     * @param files array of files to delete; can be of zero length
     * @param dirs array of directories to delete; can of zero length
     */
    function _removeFiles(&$d, &$files, &$dirs) {
        if (count($files) > 0) {
            $this->log("Deleting " . count($files) . " files from " . $d->getAbsolutePath());
            for ($j=0; $j<count($files); ++$j) {
                $f = new File($d, $files[$j]);
                $this->log("Deleting " . $f->getAbsolutePath(), $this->verbosity);
                if (!$f->delete()) {
                    $message="Unable to delete file " . $f->getAbsolutePath();
                    if($this->failonerror) {
                        throw( new BuildException($message) );
                    } else {
                        $this->log($message, $this->quiet ? PROJECT_MSG_VERBOSE : PROJECT_MSG_WARN);
                    }
                }
            }
        }

        if (count($dirs) > 0 && $this->includeEmpty) {
            $dirCount = 0;
            for ($j=count($dirs)-1; $j>=0; --$j) {
                $dir = new File($d, $dirs[$j]);
                $dirFiles = $dir->listDir();
                if ($dirFiles === null || count($dirFiles) === 0) {
                    $this->log("Deleting " . $dir->getAbsolutePath(), $this->verbosity);
                    if (!$dir->delete()) {
                        $message="Unable to delete directory " + $dir->getAbsolutePath();
                        if($this->failonerror) {
                            throw(new BuildException($message));
                        } else {
                            $this->log($message, $this->quiet ? PROJECT_MSG_VERBOSE : PROJECT_MSG_WARN);
                        }
                    } else {
                        $dirCount++;
                    }
                }
            }
            if ($dirCount > 0) {
                $this->log("Deleted $dirCount director" . ($dirCount==1 ? "y" : "ies") . " from " . $d->getAbsolutePath());
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

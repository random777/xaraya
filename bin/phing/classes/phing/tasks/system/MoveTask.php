<?php
// {{{ Header
/*
 * -File       $Id: MoveTask.php,v 1.7 2003/04/09 15:59:23 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.BuildException");
import("phing.Project");
import("phing.system.io.File");
import("phing.system.io.IOException");
import("phing.tasks.system.CopyTask");

/**
 * Moves a file or directory to a new file or directory.
 * By default, the destination file is overwritten if it
 * already exists.  When overwrite is turned off, then files
 * are only moved if the source file is newer than the
 * destination file, or when the destination file does not
 * exist.
 *
 * source files and directories are only deleted when the file or
 * directory has been copied to the destination successfully.
 *
 * TODO:
 *  .comments
 *  .needs testing
 *
 * @version $Revision: 1.7 $ $Date: 2003/04/09 15:59:23 $
 * @package phing.tasks.system
 */

class MoveTask extends CopyTask {

    function MoveTask() {
        parent::CopyTask();
        $this->forceOverwrite = true;
    }

    /* - private - */
    function _doWork() {
        $copyMapSize = count($this->fileCopyMap);
        if ($copyMapSize > 0) {
            // files to move
            $this->log("Moving $copyMapSize files to " . $this->destDir->getAbsolutePath());

            foreach($this->fileCopyMap as $from => $to) {
                if ($from == $to) {
                    $this->log("Skipping self-move of $from", $this->verbosity);
                    continue;
                }

                $moved = false;
                $f = new File($from);
                $d = new File($to);

                { // try to rename
                    $this->log("Attempting to rename: $from to $toFile", $this->verbosity);
                    $moved = $this->_renameFile($f, $d, $this->filtering, $this->forceOverwrite);
                }
                if (catch("IOException", $ioe)) {
                    $msg = "Failed to rename $from to $to due to " . $ioe->getMessage();
                    throw (new BuildException($msg, $this->location));
                }

                if (!$moved) {
                    { // try to move
                        $this->log("Moving $from to $to", $this->verbosity);

                        $fu =& $this->getFileUtils();
                        $fu->copyFile($f, $d, $executionFilters = null, $this->forceOverwrite);

                        $f = new File($fromFile);
                        if (!$f->delete()) {
                            throw (new BuildException("Unable to delete file " . $f->getAbsolutePath()));
                        }
                    }
                    if (catch("IOException", $ioe)) {
                        $msg = "Failed to copy $from to $to due to " . $ioe->getMessage();
                        throw (new BuildException($msg, $this->location));
                        return;
                    }
                }
            }
        }

        // handle empty dirs if appropriate
        if ($this->includeEmpty) {
            $e = array_keys($this->dirCopyMap);
            $count = 0;
            foreach ($e as $dir) {
                $d = new File((string) $dir);
                if (!$d->exists()) {
                    if (!$d->mkdirs()) {
                        $this->log("Unable to create directory " . $d->getAbsolutePath(), PROJECT_MSG_ERR);
                    } else {
                        $count++;
                    }
                }
            }
            if ($count > 0) {
                $this->log("moved $count empty director" . ($count == 1 ? "y" : "ies") . " to " . $this->destDir->getAbsolutePath());
            }
        }

        if (count($this->filesets) > 0) {
            // process filesets
            for ($i=0; $i<count($this->filesets); ++$i) {
                $fs =& $this->filesets[$i];
                $dir = $fs->getDir($this->project);
                if ($this->_okToDelete($dir)) {
                    $this->_deleteDir($dir);
                }
            }
        }
    }

    /** Its only ok to delete a dir tree if there are no files in it. */
    function _okToDelete(&$d) {
        $list = $d->listDir();
        if ($list === null) {
            return false;     // maybe io error?
        }

        for ($i = 0; $i < count($list); ++$i) {
            $s = $list[$i];
            $f = new File($d, $s);
            if ($f->isDirectory()) {
                if (!$this->_okToDelete($f)) {
                    return false;
                }
            } else {
                // found a file
                return false;
            }
        }
        return true;
    }

    /** Go and delete the directory tree. */
    function _deleteDir(&$d) {
        $list = $d->listDir();
        if ($list === null) {
            return;      // on an io error list() can return null
        }

        for ($i = 0; $i < count($list); ++$i) {
            $s = $list[$i];
            $f = new File($d, $s);
            if ($f->isDirectory()) {
                $this->_deleteDir($f);
            } else {
                throw (new BuildException("UNEXPECTED ERROR - The file " . $f->getAbsolutePath() . " should not exist!"));
                return;
            }
        }

        $this->log("Deleting directory " . $d->getAbsolutePath(), $this->verbosity);

        if (!$d->delete()) {
            throw (new BuildException("Unable to delete directory " . $d->getAbsolutePath()));
        }
    }

    /**
     * Attempts to rename a file from a source to a destination.
     * If overwrite is set to true, this method overwrites existing file
     * even if the destination file is newer.
    * Otherwise, the source file is renamed only if the destination file #
    * is older than it.
     */
    function _renameFile(&$sourceFile, &$destFile, $filtering, $overwrite) {
        $renamed = true;
        if (!$filtering) {
            // ensure that parent dir of dest file exists!
            $parent = $destFile->getParentFile();
            if ($parent !== null) {
                if (!$parent->exists()) {
                    $parent->mkdirs();
                }
            }
            if ($destFile->exists()) {
                if (!$destFile->delete()) {
                    throw (new BuildException("Unable to remove existing file " . $destFile->toString()));
                    return;
                }
            }
            $renamed = $sourceFile->renameTo($destFile);
        } else {
            $renamed = false;
        }
        return $renamed;
    }
}
?>

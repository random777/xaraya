<?php
// {{{ Header
/*
 * -File       $Id: CopyTask.php,v 1.55 2003/07/09 06:06:39 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell  
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.system.io.File');
import('phing.BuildException');
import('phing.util.FileUtils');
import('phing.util.SourceFileScanner');
import('phing.mappers.IdentityMapper');
import('phing.mappers.FlattenMapper');

/**
 * A phing copy task.  Copies a file or directory to a new file
 * or directory.  Files are only copied if the source file is newer
 * than the destination file, or when the destination file does not
 * exist. It is possible to explictly overwrite existing files.
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.55 $ $Date: 2003/07/09 06:06:39 $
 * @package  phing.tasks.system
 */

class CopyTask extends Task {

    /*- all private -*/
    var $file          = null;   // the source file (from xml attribute)
    var $destFile      = null;   // the destiantion file (from xml attribute)
    var $destDir       = null;   // the destination dir (from xml attribute)
    var $overwrite     = false;  // overwrite destination (from xml attribute)
    var $preserveLMT   = true;   // sync timestamps (from xml attribute)
    var $includeEmpty  = true;   // include empty dirs? (from XML)
    var $flatten       = false;  // apply the FlattenMapper right way (from XML)
    var $mapperElement = null;

    var $fileCopyMap   = array(); // asoc array containing mapped file names
    var $dirCopyMap    = array(); // asoc array containing mapped file names
    var $fileUtils     = null;    // a instance of fileutils
    var $filesets      = array(); // all fileset objects assigned to this task
    var $filterChains  = array(); // all filterchains objects assigned to this task

    var $verbosity     = PROJECT_MSG_VERBOSE; // helper var

    /**
     * Sets up this object internal stuff. i.e. the Fileutils instance
     *
     * @return object   The CopyTask instnace
     * @access public
     */
    function CopyTask() {
        $this->fileUtils = FileUtils::newFileUtils();
    }

    /**
     * Set the overwrite flag. IntrospectionHelper takes care of
     * booleans in set* methods so we can assume that the right
     * value (boolean primitive) is coming in here.
     *
     * @param  boolean  Overwrite the destination file(s) if it/they already exist
     * @return void
     * @access public
     */
    function setOverwrite($bool) {
        $this->overwrite = (boolean) $bool;
    }

    /**
     * Set the preserve timestmap flag. IntrospectionHelper takes care of
     * booleans in set* methods so we can assume that the right
     * value (boolean primitive) is coming in here.
     *
     * @param  boolean  Preserve the timestamp on the destination file
     * @return void
     * @access public
     */
    function setTstamp($bool) {
        $this->preserveLMT = (boolean) $bool;
    }


    /**
     * Set the include empty dirs flag. IntrospectionHelper takes care of
     * booleans in set* methods so we can assume that the right
     * value (boolean primitive) is coming in here.
     *
     * @param  boolean  Flag if empty dirs should be cpoied too
     * @return void
     * @access public
     */
    function setIncludeEmptyDirs($bool) {
        $this->includeEmpty = (boolean) $bool;
    }


    /**
     * Set the file. We have to manually take care of the
     * type that is coming due to limited type support in php
     * in and convert it manually if neccessary.
     *
     * @param  string/object  The source file. Either a string or an File object
     * @return void
     * @access public
     */
    function setFile($file) {
        if (is_a($file, "File")) {
            $file = $file->getPath();
        }
        $this->file = new File((string)$file);
    }


    /**
     * Set the toFile. We have to manually take care of the
     * type that is coming due to limited type support in php
     * in and convert it manually if neccessary.
     *
     * @param  string/object  The dest file. Either a string or an File object
     * @return void
     * @access public
     */
    function setTofile($file) {
        if (is_a($file, "File")) {
            $file = $file->getPath();
        }
        $this->destFile = new File((string)$file);
    }


    /**
     * Set the toDir. We have to manually take care of the
     * type that is coming due to limited type support in php
     * in and convert it manually if neccessary.
     *
     * @param  string/object  The directory, either a string or an File object
     * @return void
     * @access public
     */
    function setTodir($dir) {
        if (is_a($dir, "File")) {
            $dir = $dir->getPath();
        }
        $this->destDir = new File((string)$dir);
    }

    /**
     * Nested creator, creates a Fileset for this task
     *
     * @access  public
     * @return  object  The created fileset object
     */
    function &createFileset() {
        $num = array_push($this->filesets, new Fileset());
        return $this->filesets[$num-1];
    }

    /**
     * Creates a filterchain
     *
     * @access public
     * @return  object  The created filterchain object
     */
    function &createFilterchain() {
        $num = array_push($this->filterChains, new FilterChain($this->project));
        return $this->filterChains[$num-1];
    }

    /**
     * Nested creator, creates one Mapper for this task
     *
     * @access  public
     * @return  object  The created Mapper type object
     * @throws  BuildException
     */
    function &createMapper() {
        if ($this->mapperElement !== null) {
            throw (new BuildException("Cannot define more than one mapper",$this->location), __FILE__, __LINE__);
            return;
        }
        $this->mapperElement =& new Mapper($this->project);
        return $this->mapperElement;
    }

    /**
     * The main entry point where everything gets in motion.
     *
     * @access  public
     * @return  true on success
     * @throws  BuildException
     */
    function main() {
        $this->_validateAttributes();

        if ($this->file !== null) {
            if ($this->file->exists()) {
                if ($this->destFile === null) {
                    $this->destFile = new File($this->destDir, (string) $this->file->getName());
                }
                if ($this->overwrite === true || ($this->file->lastModified() > $this->destFile->lastModified())) {
                    $this->fileCopyMap[$this->file->getAbsolutePath()] = $this->destFile->getAbsolutePath();
                } else {
                    $this->log($this->file->getName()." omitted, is up to date");
                }
            } else {
                // terminate build
                $message = "Could not find file ".$this->file->getAbsolutePath() ." to copy.";
                throw (new BuildException($message), __FILE__, __LINE__);
                return;
            }
        }

        $project =& $this->getProject();

        // process filesets
        $count = count($this->filesets);
        for ($i=0; $i<$count; ++$i) {
            $fs =& $this->filesets[$i];
            $ds =& $fs->getDirectoryScanner($project);

            $fromDir  = $fs->getDir($project);
            $srcFiles = $ds->getIncludedFiles();
            $srcDirs  = $ds->getIncludedDirectories();
            $this->_scan($fromDir, $this->destDir, $srcFiles, $srcDirs);
        }

        // go and copy the stuff
        $this->_doWork();

        if ($this->destFile !== null) {
            $this->destDir = null;
        }
    }

    /**
     * Validates attributes coming in from XML
     *
     * @access  private
     * @return  void
     * @throws  BuildException
     */
    function _validateAttributes() {
        if ($this->file === null && count($this->filesets) === 0) {
            throw (new BuildException("CopyTask. Specify at least one source - a file or a fileset."));
            return;
        }

        if ($this->destFile !== null && $this->destDir !== null) {
            throw (new BuildException("Only one of destfile and destdir may be set."));
            return;
        }

        if ($this->destFile === null && $this->destDir === null) {
            throw (new BuildException("One of destfile or destdir must be set."));
            return;
        }

        if ($this->file !== null && $this->file->exists() && $this->file->isDirectory()) {
            throw (new BuildException("Use a fileset to copy directories."));
            return;
        }

        if ($this->destFile !== null && count($this->filesets) > 0) {
            throw (new BuildException("Cannot concatenate multple files into a single file."));
            return;
        }

        if ($this->destFile !== null) {
            $this->destDir = $this->destFile->getParentFile();
            if ($this->destDir === null)
                $this->destDir =& new File(".");
        }
    }

    /**
     * Compares source files to destination files to see if they
     * should be copied.
     *
     * @access  private
     * @return  void
     */
    function _scan(&$fromDir, &$toDir, &$files, &$dirs) {
        /* mappers should be generic, so we get the mappers here and
        pass them on to builMap. This method is not redundan like it seems */
        $mapper = null;
        if ($this->mapperElement !== null) {
            $mapper =& $this->mapperElement->getImplementation();
        } else if ($this->flatten) {
            $mapper =& new FlattenMapper();
        } else {
            $mapper =& new IdentityMapper();
        }
        $this->_buildMap($fromDir, $toDir, $files, $mapper, $this->fileCopyMap);
    }

    /**
     * Builds a map of filenames (from->to) that should be copied
     *
     * @access  private
     * @return  void
     */
    function _buildMap(&$fromDir, &$toDir, &$names, &$mapper, &$map) {
        $toCopy = null;
        if ($this->overwrite) {
            $v = array();
            for ($i=0; $i<count($names); ++$i) {
                $result = $mapper->Main($names[$i]);
                if ($result !== null) {
                    $v[] = $names[$i];
                }
            }
            $toCopy = $v;
        } else {
            $ds = new SourceFileScanner($this);
            $toCopy = $ds->restrict($names, $fromDir, $toDir, $mapper);
        }

        for ($i = 0; $i < count($toCopy); ++$i) {
            $src  = new File($fromDir, $toCopy[$i]);
            $mapped = $mapper->main($toCopy[$i]);
            $dest = new File($toDir, $mapped[0]);
            $map[$src->getAbsolutePath()] = $dest->getAbsolutePath();
        }
    }


    /**
     * Actually copies the files
     *
     * @access  private
     * @return  void
     * @throws  BuildException
     */
    function _doWork() {
        $mapSize = count($this->fileCopyMap);
        $total = $mapSize;
        if ($mapSize > 0) {
            $this->log("Copying $mapSize file".(($mapSize) === 1 ? '' : 's')." to ". $this->destDir->getAbsolutePath());
            // walks the map and actually copies the files
            $count=0;
            foreach($this->fileCopyMap as $from => $to) {
                if ($from == $to) {
                    $this->log("Skipping self-copy of $from", PROJECT_MSG_VERBOSE);
                    $total--;
                    continue;
                }
                $this->log("From $from to $to", PROJECT_MSG_VERBOSE);
                { // try to copy file
                    $this->fileUtils->copyFile(new File($from), new File($to), $this->overwrite, $this->preserveLMT, null, $this->filterChains, $this->getProject());
                    $count++;
                }
                if ( catch("IOException", $ioe)) {
                        $msg = "Failed to copy $from to $to due to " . $ioe->getMessage();
                        // fixme: add cause
                        throw (new BuildException($msg, $this->location));
                        return;
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
                $this->log("Copied {$count} empty director" . ($count == 1 ? "y" : "ies") . " to " . $this->destDir->getAbsolutePath());
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

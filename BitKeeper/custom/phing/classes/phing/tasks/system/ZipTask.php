<?php
// {{{ Header
/*
 * -File       $Id: ZipTask.php,v 1.7 2003/03/09 12:01:43 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2003, Marcel van der Boom
 * -Author     marcel van der boom, marcel@hsdev.com
 * -Based on   TarTask.php
 */
// }}}

import('phing.Task');
import("phing.lib.Zip");

/**
 * Create a zipball and add all files from a given fileset.
 *
 * If the given zip file already exists, overwrite it.
 *
 * @version $Revision$
 * @package phing.tasks.system
 */
class ZipTask extends Task {
    // {{{ properties
    /** @var    array   Array for nested elements "fileset" */
    var $filesets  = array();

    /** @var    string  Name of the zipball file to write */
    var $outfile = null;

    /** @var    object  Reference to the Zip class that actually
     *  reads/writes zipball files */
    var $zipclass = null;
    // }}}


    // {{{ method main()
    /**
     * Main entry point for the task
     *
     * @access      public
     */
    function main() {
        $this->zip = new Zip();


        $this->processFilesets();
    }
    // }}}
    // {{{ method processFilesets()
    /**
     * This method processes all filesets and adds the files to the
     * zipball
     *
     * @access      protected
     */
    function processFilesets() {
        // Directories in zipball are relative, we have to chdir() later
        // save old pwd
        $old_dir = getcwd();

        // process filesets
        $count = count($this->filesets);
        for ($i=0; $i<$count; $i++) {
            $fs =& $this->filesets[$i];
            $ds =& $fs->getDirectoryScanner($this->getProject());

            // chdir() to the base directory of the fileset
            $dir =& $fs->getDir($this->project);
            chdir($dir->getAbsolutePath());

            $srcFiles = $ds->getIncludedFiles();
            $srcDirs  = $ds->getIncludedDirectories();

            foreach ($srcFiles as $file) {
                $this->log("Adding file $file ... \n", PROJECT_MSG_VERBOSE);
                if (!$this->zip->addFile($file)) {
                    $this->log("Error: Could not add \"$file\" to the zipball");
                }
            }
        }

        // restore old pwd
        chdir($old_dir);

        // save zipball
        $this->zip->toZip($this->outfile);

        return true;
    }
    // }}}
    // {{{ Accessors
    /**
     * Sets the filename to write to.
     */
    function setOutfile($file) {
        $this->outfile = $file;
    }

    // }}}
    // {{{ Creators for nested elements
    /**
     * Nested creator, adds a set of files (nested fileset attribute).
     */
    function &createFileset() {
        $num = array_push($this->filesets, new Fileset());
        return $this->filesets[$num-1];
    }
    // }}}
}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

<?php
// {{{ Header
/*
 * -File       $Id: TarTask.php,v 1.7 2003/03/09 12:01:43 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2003, Eye Integrated Communications
 * -Author     jason hines, jason@greenhell.com
 */
// }}}

import('phing.Task');
import("phing.lib.Tar");

/**
 * Create a tarball and add all files from a given fileset.
 * TarTask can also do Gzip compression on the tarball file.
 *
 * If the given tar file already exists, overwrite it.
 *
 * @version $Revision: 1.7 $ $Date: 2003/03/09 12:01:43 $
 * @package phing.tasks.system
 */
class TarTask extends Task {
    // {{{ properties
    /** @var    array   Array for nested elements "fileset" */
    var $filesets  = array();

    /** @var    string  Name of the tarball file to write */
    var $outfile = null;

    /** @var    bool    Boolean that determines if the tarball
     *  is to be compressed. */
    var $usegzip = FALSE;
   
    /** @var    object  Reference to the Tar class that actually
     *  reads/writes tarball files */
    var $tarclass = null;
    // }}}


    // {{{ method main()
    /**
     * Main entry point for the task
     *
     * @access      public
     */
    function main() {
        $this->tar = new Tar();


        $this->processFilesets();
    }
    // }}}
    // {{{ method processFilesets()
    /**
     * This method processes all filesets and adds the files to the
     * tarball
     *
     * @access      protected
     */
    function processFilesets() {
        // Directories in tarball are relative, we have to chdir() later
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
                if (!$this->tar->addFile($file)) {
                    $this->log("Error: Could not add \"$file\" to the tarball");
                }
            }
        }

        // restore old pwd
        chdir($old_dir);

        // save tarball
        $this->tar->toTar($this->outfile,$this->usegzip);

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

    /**
     * Sets a boolean for using Gzip compression.
     */
    function setUseGzip($usegzip) {
        $this->usegzip = (bool) $usegzip;
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

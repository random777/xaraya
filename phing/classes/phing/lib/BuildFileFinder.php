<?php
// {{{ Header
/*
 * -File       $Id: BuildFileFinder.php,v 1.3 2003/02/01 19:55:58 openface Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2002, Plateau Innovation
 * -Author     Albert Lash, alash@plateauinnovation.com
 */
// }}}

import("phing.util.DirectoryScanner");

// {{{ buildfilelookup

/**
 * Looks up and down the file hierarchy for the buildfile passed: $_filename
 *
 * @author   Albert Lash, alash@plateauinnovation.com
 * @version  $Revision: 1.3 $
 *  @package   phing.util
 */

class BuildFileFinder {

    // public


    /**
     * Description.
     *
     * @author  Albert Lash, alash@plateauinnovation.com
     */

    function buildfilefinder() {
        return true;
    }


    function buildfilelookupwards($directory_search_root, $_filename) {
        // Look up four directories

        for($i=0; $i<5; $i++) {

            $_path = $directory_search_root . DIRECTORY_SEPARATOR . $_filename;

            if(is_file("$_path") && $i!="5") {
                return $_path;
            }
            elseif($i==4) {
                return FALSE;
            }

            $directory_search_root = dirname($directory_search_root);
        }
    }

    function buildfilelookdownwards($top_buildfile_path, $_filename) {
        // Look down from the top-most directory exhaustively
        $ds = new DirectoryScanner();
        $ds->SetIncludes(array("**/$_filename"));
        $ds->AddDefaultExcludes();
        $ds->SetBasedir($top_buildfile_path);
        $ds->Scan();
        $filelist = $ds->GetIncludedFiles();
        unset($ds);

        /*
        // Move this to core/Phing.php
        for($i=0; $i<count($filelist); ++$i) {
        	$path = dirname($filelist[$i]);    // in phing $this->buildfile
        	chdir($path);
        	$Phing->SetBuildfile(basename($filelist[$i]));
        	// replace with Main() !!
        	$Phing->Main();
        }
        */

        // return an array of paths
        return $filelist;
    }
}

// }}}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

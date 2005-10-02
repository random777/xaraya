<?php
// {{{ Header
/*
 * -File       $Id: DumpTask.php,v 1.15 2003/07/09 14:46:51 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}
// {{{ imports

import('phing.Task');


/**
 *  This tasks works on the phing object itself may change
 *
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *  @version  $Revision: 1.15 $
 *  @package  phing.tasks.system
 */

class DumpTask extends Task {

    // {{{ properties
    var $filesets = array();

    var $mode = "long";
    // }}}

    function main() {
        if (count($this->filesets))
            $this->_dumpFileSets();
    }

    function _dumpPatternset() {
        // FIXME proper ref handling/introspection helper  needs update
        $pset =& $this->project->references[$this->reference];
        $this->log("Patternset '{$this->reference}':");
        $this->log("-------------------------------------------------");
        $this->log("  ".$pset->toString());
        $this->project->log("");
    }

    function _dumpFileSets() {
        // set shortcut
        $project =& $this->getProject();
        $count = count($this->filesets);

        for ($i = 0; $i < $count; $i++) {
            $fset =& $this->filesets[$i];

            // Echo
            if ($this->mode === "long") {
                $this->log("Fileset");
                $this->log("-------------------------------------------------");

                $dir = $fset->getDir($this->project);
                $this->log("Dir: " . $dir->toString());
            }

            $ds =& $fset->getDirectoryScanner($project);

            $fromDir  = $fset->getDir($project);
            $srcFiles = $ds->getIncludedFiles();

            $count2 = count($srcFiles);
            for ($i = 0; $i < $count2; $i++) {
                $f =& new File($srcFiles[$i]);
                $this->Log($f->GetPath());
            }
        }
    }

    // {{{ Accessors
    function setMode($mode) {
        $this->mode = $mode;
    }

    function &createFileset() {
        $int = array_push($this->filesets, new Fileset());
        return $this->filesets[$int-1];
    }
    // }}}
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

<?php
// {{{ Header
/*
 * -File       $Id: MkdirTask.php,v 1.14 2003/07/08 19:56:05 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, 2002 Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.Task');
import('phing.system.io.File');

/**
 * Make direcotories
 *
 * TODO
 *  .comments
 *  .testing
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.14 $ $Date: 2003/07/08 19:56:05 $
 * @package  phing.tasks.system
 */

class MkdirTask extends Task {

    /** our directory */
    var $dir = null;

    /**
     * create the directory and all parents
     *
     * @throws BuildException if dir is somehow invalid, or creation failed.
     */
    function main() {
        if ($this->dir === null) {
            throw (new BuildException("dir attribute is required", $this->location), __FILE__, __LINE__);
            return;
        }

        if ($this->dir->isFile()) {
            throw (new BuildException("Unable to create directory as a file already exists with that name: " . $this->dir->getAbsolutePath()));
            return;
        }
        if (!$this->dir->exists()) {
            $result = $this->dir->mkdirs();
            if (!$result) {
                $msg = "Directory " . $this->dir->getAbsolutePath() . " creation was not successful for an unknown reason";
                throw (new BuildException($msg, $this->location));
                return;
            }
            $this->log("Created dir: " . $this->dir->getAbsolutePath());
        } else {
            $this->log("Directory '" . $this->dir->getAbsolutePath() . "' does already exist",
                    PROJECT_MSG_VERBOSE);
        }
    }

    /** the directory to create; required. */
    function setDir($dir) {
        if (is_a($dir, "File")) {
            $dir = $dir->getPath();
        }
        $this->dir = new File((string) $dir);
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

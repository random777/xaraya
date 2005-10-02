<?php
// {{{ Header
/*
 * -File       $Id: ExecTask.php,v 1.14 2003/06/16 17:43:04 openface Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.Task');

/**
 *  Echos a message to all output devices
 *
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *  @version  $Revision: 1.14 $
 *  @package  phing.tasks.system
 */

class ExecTask extends Task {

    var $command = null;
    var $dir     = null;
    var $escape  = TRUE;

    function main() {
        $this->_run($this->command);
    }

    function setCommand($command) {
        $this->command = $command;
    }

    function setEscape($escape) {
        $this->escape = (bool) $escape;
    }

    function setDir($dir) {
        if (is_a($dir, "File")) {
            $this->dir = new File($dir->getPath());
        }
        $this->dir = new File((string) $dir);
    }

    // FIXME multiple OSs
    function setOs($os) {
        $this->os = (string) $os;
    }

    function _run($command) {
        if ($this->dir !== null) {
            if ($this->dir->isDirectory()) {
                $currdir = getcwd();
                @chdir($this->dir->getPath());
            } else {
                throw ( new BuildException("Can't chdir to:" . $this->dir->toString()), __FILE__, __LINE__);
                return;
            }
        }

        if ($this->escape == TRUE) {
            $command = escapeshellcmd($command);
        }

        $this->log("Executing command: $command", PROJECT_MSG_VERBOSE);

        $resMessages = array();
        exec($command, $resMessages);

        if ($this->dir !== null) {
            @chdir($currdir);
        }

        while($resMessages) {
            $line = array_shift($resMessages);
            $this->log($line);
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

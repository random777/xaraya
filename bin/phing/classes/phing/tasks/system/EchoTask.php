<?php
// {{{ Header
/*
 * -File       $Id: EchoTask.php,v 1.11 2003/04/09 15:58:12 thyrell Exp $
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
 *  @version  $Revision: 1.11 $ $Date: 2003/04/09 15:58:12 $
 *  @package  phing.tasks.system
 */

class EchoTask extends Task {

    var $msg = null;

    function main() {
        $this->log($this->msg);
    }

    /** setter for message */
    function setMsg($_msg) {
        $this->setMessage($_msg);
    }

    /** alias setter */
    function setMessage($_msg) {
        $this->msg = (string) $_msg;
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

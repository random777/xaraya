<?php
// {{{ Header
/*
 * -File     $Id: NullPointerException.php,v 1.6 2003/04/09 15:58:11 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.system.lang.RuntimeException");

/**
 *  @package   phing.system.lang
 */

class NullPointerException extends RuntimeException {
    function NullPointerException($msg = null, $file = null, $line = null) {
        if ($file === null) {
            $file = "unknown file";
        } else {
            $file = basename($file);
        }

        if ($line === null) {
            $line = "unknown line";
        }

        if ($msg === null) {
            $msg = "No details provided";
        }

        $msg = "NullPointerException in $file at line $line: $msg";
        parent::RuntimeException($msg);
    }
}
?>

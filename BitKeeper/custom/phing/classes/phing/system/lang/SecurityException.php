<?php
// {{{ Header
/*
 * -File     $Id: SecurityException.php,v 1.5 2003/04/09 15:58:11 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.system.lang.SecurityException");

/**
 * Thrown to indicate a security violation.
 *  @package   phing.system.lang
 */

class SecurityException extends RuntimeException {
    function SecurityException($message = null, $file = null, $line = null) {
        if ($file === null) {
            $file = "unknown";
        }
        if ($line === null) {
            $line = "unknown";
        }
        if ($message === null) {
            $message = "Unspecified message";
        }
        $file = basename($file);
        $message = "in $file at line $line: $message";
        parent::RunteimException($message, $file, $line);
    }
}
?>

<?php
// {{{ Header
/*
 * -File     $Id: RuntimeException.php,v 1.8 2003/04/09 15:58:11 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.system.lang.System");
import("phing.system.lang.Exception");

/**
 * The class Exception and its subclasses are used in conjunction with
 * the throw() method to throw errors.
 *  @package   phing.system.lang
 */

class RuntimeException extends Exception {
    function RuntimeException($message = null) {
        if ($message === null) {
            $message = "Unspecified message";
        }
        parent::Exception($message);
    }
}
?>

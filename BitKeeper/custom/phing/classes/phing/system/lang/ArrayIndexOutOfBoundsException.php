<?php
// {{{ Header
/*
 * -File     $Id: ArrayIndexOutOfBoundsException.php,v 1.6 2003/04/09 15:58:11 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Andrzej Nowodworski, a.nowodworski@learn.pl
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import("phing.system.lang.RuntimeException");

/**
 *  @package   phing.system.lang
 */
class ArrayIndexOutOfBoundsException extends RuntimeException {

    function ArrayIndexOutOfBoundsException($i, $file=null, $line = null) {
        parent::RuntimeException("Array does not have a index $i", $file, $line);
    }
}
?>

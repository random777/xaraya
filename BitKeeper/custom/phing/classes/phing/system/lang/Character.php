<?php
// {{{ Header
/*
 * -File     $Id: Character.php,v 1.5 2003/04/09 15:58:11 thyrell Exp $
 * -License LGPL (http://www.gnu.org/copyleft/lesser.html)   
 * -Copyright  2001, Thyrell
 * -Author   Anderas Aderhold, andi@binarycloud.com
 */
// }}}

/**
 *  @package   phing.system.lang
 */

class Character {

    // this class might be extended with plenty of ordinal char constants
    // and the like to support the multibyte aware datatype (char) in php
    // in form of an object.
    // anyway just a thought

    function isLetter($char) {

        if (strlen($char) !== 1)
            $char = 0;

        $char = (int) ord($char);

        if ($char >= ord('A') && $char <= ord('Z'))
            return true;

        if ($char >= ord('a') && $char <= ord('z'))
            return true;

        return false;
    }

}
?>

<?php
/**
 * validate a parameter as a United States ZIP code
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
*/
/**
 * Validate a United States ZIP code
 * @return bool true if email, false if not
 */
function variable_validations_uszip (&$subject, $parameters=null, $supress_soft_exc, &$name)
{
    // accepts nnnnn or nnnnn-nnnn (hyphen required)
    if (!eregi('^\d{5}$|^\d{5}-\d{4}$', $subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) does not match ZIP code type: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not an ZIP code type: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));

        return false;
    }

    return true;
}

?>

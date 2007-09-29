<?php
/**
 * validate a parameter as a Canadian Postal Code
 *
 * @package validation
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
*/
/**
 * Validate a Canadian Postal Code
 * @return bool true if email, false if not
 */
function variable_validations_capostalcode (&$subject, $parameters=null, $supress_soft_exc, &$name)
{
    if (!eregi('^([ABCEGHJKLMNPRSTVXY]\d[ABCEGHJKLMNPRSTVWXYZ])\ {0,1}(\d[ABCEGHJKLMNPRSTVWXYZ]\d)$', $subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) does not match postal code type: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not a postal code type: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));

        return false;
    }

    return true;
}

?>

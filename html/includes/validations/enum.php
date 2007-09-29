<?php
/**
 * Validate a file as enum
 *
 * @package validation
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */

/**
 * Enum Validation Function
 *
 * The function checks the entered value to be one of the possible options.
 * @param array $parameters Array of parameters entered.
 * @param $subject
 * @return bool true on successfull validation
                false on unsuccessfull validation
 */
function variable_validations_enum (&$subject, $parameters, $supress_soft_exc, &$name)
{

    $found = false;

    foreach ($parameters as $param) {
        if ($subject == $param) {
            $found = true;
        }
    }

    if ($found) {
        return true;
    } else {
        if ($name != '')
            $msg = xarML('Input "#(1)" was not one of the possibilities for #(2): "', $subject, $name);
        else
            $msg = xarML('Input "#(1)" was not one of the possibilities.', $subject);
        $first = true;
        foreach ($parameters as $param) {
            if ($first) $first = false;
            else $msg .= ' or ';

            $msg .= $param;
        }
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }
}

?>
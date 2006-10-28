<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * Strings Validation Class
 * @return bool true if the value of $subject is a string
 */
function variable_validations_str (&$subject, $parameters, $supress_soft_exc, &$name)
{

    if (!is_string($subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) is not a string: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not a string: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    $length = strlen($subject);

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        if (!is_numeric($parameters[0])) {
            $msg = 'Parameter "'.$parameters[0].'" is not a Numeric Type';
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return;
        } elseif ($length < (int) $parameters[0]) {
            $msg = xarML('Size of the string "#(1)" is smaller than the specified minimum "#(2)"', $subject, $parameters[0]);
            if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return false;
        }
    }

    if (isset($parameters[1]) && trim($parameters[1]) != '') {
        if (!is_numeric($parameters[1])) {
            $msg = 'Parameter "'.$parameters[1].'" is not a Numeric Type';
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return;
        } elseif ($length > (int) $parameters[1]) {
            $msg = xarML('Size of the string "#(1)" is bigger than the specified maximum "#(2)"', $subject, $parameters[1]);
            if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            return false;
        }
    }

    $subject = (string) $subject; //Is this useless?
    return true;
}

?>
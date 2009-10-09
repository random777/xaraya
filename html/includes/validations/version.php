<?php
/**
 * Short description of purpose of file
 *
 * @package validation
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
*/
/**
 * Version Number Validation Function... see RFC44, section 6
 * @return bool true if valid, false if not
 */
function variable_validations_version (&$subject, $parameters, $supress_soft_exc, &$name)
{
    if (!is_string($subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) is not a string: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not a string: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    // We only have one universal rule now
    // [major].[minor].[micro]
    // with -[suffix][rev] being optional
    $regex = '/^([1-9]\d*|0)\.([1-9]\d*|0)\.([1-9]\d*|0)(-(a|b|rc)([1-9]\d*))?$/';

    if (preg_match($regex, $subject)) {
            return true;
        } else {
          return false;
    }

    return;
}

?>

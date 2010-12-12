<?php
/**
 * Validate subject as a bool value
 *
 * @package validation
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
*/

/**
 * Boolean Validation Function
 * @return bool true if bool, false if not
 */
function variable_validations_bool (&$subject, $parameters=null, $supress_soft_exc, &$name)
{
    //Added the '1' because that is what true is translated for afaik
    if ($subject === true || $subject === 'true' || $subject == '1') {
        $subject = true;
    //Added '' becayse that is what false get translated for...
    } elseif ($subject === false || $subject === 'false' || $subject == '0' || $subject == '') {
        $subject = false;
    } else {
        if ($name != '')
            $msg = xarML('Variable #(1) is not a boolean: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not a boolean: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    return true;
}

?>
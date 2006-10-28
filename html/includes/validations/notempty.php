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
 * notempty Validation Function
 * @param subject
 * @param parameters
 */
function variable_validations_notempty (&$subject, $parameters, $supress_soft_exc, &$name)
{

    if (empty($subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) should not be empty: "#(2)"', $name, '$subject');
        else
            $msg = xarML('Should not be empty: "#(1)"', '$subject');
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    return true;
}

?>
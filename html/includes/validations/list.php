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
 * Lists Validation Function
 * @param array subject. If this is not an array, the return is false
 * @return bool true if a list is found, false if not
 */
function variable_validations_list (&$subject, $parameters, $supress_soft_exc, &$name)
{
    if (!is_array($subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) is not an array: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not an array: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        return false;
    }

    if (isset($parameters[0]) && trim($parameters[0]) != '') {
        $validation = implode(':', $parameters);
        foreach  ($subject as $key => $value) {
            $return = xarVarValidate($validation, $subject[$key], $supress_soft_exc);
            //$return === NULL or $return === FALSE => return
            if (!$return) {
                return $return;
            }
        }
    }

    return true;
}

?>

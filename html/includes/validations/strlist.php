<?php
/**
 * String List Validation function
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 */
/**
 * list:{sep}:...
 * Split the string into separate items using 'sep' as an item
 * separator, then validate each individually.
 * Any separator characters can be used except for ':'.
 * Multiple separator characters can be used, and any will be
 * recognised, but all will be converted into the first character
 * on return. So a separator string of ';,' when applied to a
 * subject string 'hello,there;word' will return 'hello;there;word'.
 * Validation of each item in the list will be further passed on to
 * any required validation type.
 *
 * @return mixed
 */
function variable_validations_strlist (&$subject, $parameters, $supress_soft_exc, &$name)
{
    $return = true;

    if (!is_string($subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) is not a string: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not a string: "#(1)"', $subject);
        if (!$supress_soft_exc) {
            xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
        }
        return false;
    }

    if (!empty($parameters)) {
        // Get the separator characters.
        $sep = array_shift($parameters);

        // TODO: error if no separator?
        if (empty($sep)) {
            $msg = xarML('No separator character(s) provided for validation type "strlist"');
            if (!$supress_soft_exc) {
                xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
            }
            return false;
        }

        // Roll up the remaining validation parameters (noting there
        // may not be any - $parameters could be empty).
        $validation = implode(':', $parameters);

        // Split up the string into elements.
        $elements = preg_split('/[' . preg_quote($sep) . ']/', $subject);

        // Get count of elements.
        $count = count($elements);

        // Loop through each element if there are any elements, and if
        // there is further validation to apply.
        if ($count > 0 && !empty($validation)) {
            for($i = 0; $i < $count; $i++) {
                // Validate each element in turn.
                $return = $return & xarVarValidate($validation, $elements[$i], $supress_soft_exc);
                if (!$return) {
                    // This one failed validation - don't try and validate any more.
                    break;
                }
            }
        }

        // Roll up the validated values. Use the first character
        // from the separator character list.
        // TODO: only roll up if validation was a success?
        $subject = implode(substr($sep, 0, 1), $elements);
    }

    return $return;
}

?>
<?php
/**
 * validate a parameter as a north american phone number
 *
 * @package validation
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
*/
/**
 * Validate a north american phone number
 * @return bool true if valid, false if not
 * @see http://en.wikipedia.org/wiki/North_American_Numbering_Plan
 */
function variable_validations_nanpphone (&$subject, $parameters=null, $supress_soft_exc, &$name)
{
	// accepts '(nnn) nnn-nnnn' (space optional) or 'nnn-nnn-nnnn' formats
    if (!eregi('^((\([2-9][0-8]\d\) ?)|([2-9][0-8]\d-))?[2-9]\d{2}-\d{4}$', $subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) does not match a phone number type: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not a phone number type: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));

        return false;
    }

    return true;
}

?>

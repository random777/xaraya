<?php
/**
 * validate a parameter as an email address
 *
 * @package validation
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
*/
/**
 * Validate an email address
 * @return bool true if email, false if not
 */
function variable_validations_email (&$subject, $parameters=null, $supress_soft_exc, &$name)
{
    if (!preg_match('/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})$/i', $subject)) {
        if ($name != '')
            $msg = xarML('Variable #(1) does not match an e-mail type: "#(2)"', $name, $subject);
        else
            $msg = xarML('Not an e-mail type: "#(1)"', $subject);
        if (!$supress_soft_exc) xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));

        return false;
    }

    return true;
}

?>
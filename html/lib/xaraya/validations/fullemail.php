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
 * Full Email Check -- Checks first thru the regexp and then by mx records
 * @return bool true if fullemail, false if not
 */
function variable_validations_fullemail (&$subject, $parameters=null, $supress_soft_exc)
{
    if (xarVarValidate ('email', $subject, $supress_soft_exc) &&
        xarVarValidate ('mxcheck', $subject, $supress_soft_exc)) {
        return true;
    }

    return false;
}

?>
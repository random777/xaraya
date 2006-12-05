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
 * Id Validation Class
 * That validation redirects to int:1 validation
 * @return bool
 */
function variable_validations_id (&$subject, $parameters, $supress_soft_exc)
{
    return xarVarValidate ('int:1', $subject, $supress_soft_exc);
}

?>
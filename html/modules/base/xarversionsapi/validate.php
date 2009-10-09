<?php
/**
 * Base User Version management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Validate the format of a version number.
 *
 * @author Jason Judge
 * @param $args['ver'] string version number to validate
 * @return boolean indicating whether the validation was passed (NULL for parameter error)
            result of validation: true or false
 */
function base_versionsapi_validate($args)
{
    extract($args, EXTR_PREFIX_INVALID, 'p');

     if (!isset($ver)) {
        if (isset($p_0)) {
            $ver = $p_0;
        } else {
            // The given verison number is missing
            $msg = xarML('The application version number was not provided');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return false;
        }
     }

    // We only have one universal rule now
    // [major].[minor].[micro]
    // with -[suffix][rev] being optional
    $regex = '/^([1-9]\d*|0)\.([1-9]\d*|0)\.([1-9]\d*|0)(-(a|b|rc)([1-9]\d*))?$/';

    if (preg_match($regex, $ver)) {
        return true;
    } else {
        return false;
    }

    return;
}

?>

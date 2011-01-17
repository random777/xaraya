<?php
/**
 * Base User Version management functions
 *
 * @package modules
 * @copyright (C) 2002-2010 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Asserts that the Xaraya application version has reached a certain level.
 *
 * @author Jason Judge
 * @param $args['ver'] string version number to compare
 * @return boolean result of test: true or false
           indicating whether the application is at least version $ver
 */
function base_versionsapi_assert_application($args)
{
    // Extract the arguments. Prefix unnamed parameters with 'p_'.
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

    $ok = xarMod::apiFunc('base','versions','validate',array($ver));

    if (!$ok) { return false; }

    $result = xarMod::apiFunc('base', 'versions', 'compare',
        array(
            'ver1' => $ver,
            'ver2' => xarConfigGetVar('System.Core.VersionNum')
        )
    );

    if ($result < 0) {
        // The supplied version is greater than the system version.
        $msg = xarML('The application version is too low; version #(1) or later is required.', $ver);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'WRONG_VERSION', new SystemException($msg));
        return false;
    }

    return true;
}

?>

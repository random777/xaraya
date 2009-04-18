<?php
/**
 * Base User Version management functions
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
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
 * @returns result of test: true or false
 * @return boolean indicating whether the application is at least version $ver
 */
function base_versionsapi_assert_application($args)
{
    extract($args, EXTR_PREFIX_INVALID, 'p');

    if (!isset($ver)) {
        if (isset($p_0)) {
            $ver = $p_0;
        } else {
            return;
        }
    }

    $result = xarModAPIfunc('base', 'versions', 'compare',
        array(
            'ver1' => $ver,
            'ver2' => xarConfigVars::get(null, 'System.Core.VersionNum'),
            'normalize' => 'numeric'
        )
    );

    if ($result < 0) {
        // The supplied version is greater than the system version.
        throw new ConfigurationException($ver,'The application version is too low; version #(1) or later is required.');
    }

    return true;
}

?>

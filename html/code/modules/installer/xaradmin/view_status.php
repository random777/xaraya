<?php
/**
 * @package modules
 * @subpackage installer module
 * @category Xaraya Web Applications Framework
 * @version 2.3.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/200.html
 */

/**
 * @return array data for the template display
 */
function installer_admin_view_status()
{    
    sys::import('modules.installer.class.upgrade');
    // Version information
    $fileversion = xarCore::VERSION_NUM;
    $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
    
    // Get the list of version checks
    Upgrader::loadFile('checks/check_list.php');
    $check_list = installer_adminapi_get_check_list();

    // Run the checks
    $checks = array();
    foreach ($check_list as $abbr_version => $check_version) {
        // @checkme <chris/> only run checks for current version ?
        // if (xarVersion::compare($check_version, $dbversion) != 0) continue;
        if (!Upgrader::loadFile('checks/' . $abbr_version .'/main.php')) {
            $checks[$check_version]['message'] = xarML('There are no checks for version #(1)', $check_version);
            $checks[$check_version]['tasks'] = array();
            //return $data;
        } else {
            $check_function = 'main_check_' . $abbr_version;
            $result = $check_function();
            $checks[$check_version] = $result['check'];
        }
    }
    $data['checks'] =& $checks;

    return $data;
}

?>

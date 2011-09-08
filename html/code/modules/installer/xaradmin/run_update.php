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
function installer_admin_run_update()
{    
    $versions = array(
                    '2.1.0',
                    '2.1.1',
                    '2.1.2',
                    '2.1.3',
                    '2.2.0',
                    '2.3.0',
                    );
    sys::import('modules.installer.class.version_upgrade');
    foreach ($versions as $version) {
        $version_upgrader = new RevisionUpgrade($version);
        $data['version_data'][$version] = $version_upgrader->run_upgrade();
    }
//    var_dump($data['upgrades']);exit;
    
    
    
    sys::import('modules.installer.class.upgrade');
    // Version information
    $fileversion = xarCore::VERSION_NUM;
    $dbversion = xarConfigVars::get(null, 'System.Core.VersionNum');
    
    // Get the list of version checks
    $items = Upgrader::getComponents();
    $data['items'] = array();
    $data['items'] = $items;
    return $data;
    foreach ($items as $item) {
        if ($item->getFileName() == 'database') continue;
        if ($item->getFileName() == 'check_list.php') continue;
        $data['items'][] = $item;
    }
    return $data;
}

?>

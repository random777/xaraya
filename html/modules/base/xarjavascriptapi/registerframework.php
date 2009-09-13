<?php
/**
 * Base JavaScript management functions
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
 * Get JS framework info
 * @author Marty Vance
 * @param $args['name'] name of the framework
 * @param $args['displayname'] pretty framework name, for display
 * @param $args['version'] framework version
 * @param $args['module'] framework host module name
 * @param $args['all'] return all frameworks (optional)
 * @return array
 */
function base_javascriptapi_getframeworkinfo($args)
{
    extract($args);

    if(!isset($upgrade)) {
        $upgrade = false;
    } else {
        $upgrade = (bool) $upgrade;
    }

    if (!isset($name)) {
        $msg = xarML('Missing framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    if (!isset($displayname)) {
        $msg = xarML('Missing framework display name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    if (!isset($module)) {
        $msg = xarML('Missing framework host module name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    if (!isset($version)) {
        $msg = xarML('Missing framework version');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    $module = strtolower($module);
    $name = strtolower($name);

    $modid = xarModGetIDFromName($module);

    if (!is_numeric($modid)) {
        $msg = xarML('Invalid framework host module name: #(1)', $module);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    $fwinfo = xarModGetVar('base','RegisteredFrameworks');
    $fwinfo = @unserialize($fwinfo);

    if (isset($fwinfo[$name]) && !$upgrade) {
        $msg = xarML('Cannot overwrite framework #(1) without force', $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    $fwinfo[$name] = array('displayname' => $displayname, 'version' => $version, 'module' => $module);
    ksort($fwinfo);
    xarModSetVar('base','RegisteredFrameworks');

    xarModSetVar($module, $name . '.plugins', unserialize(array()));
}

?>
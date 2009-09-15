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
 * Register a JS framework plugin
 * @author Marty Vance
 * @param string $args['name']          Name of the plugin
 * @param string $args['framework']     Name of the framework
 * @param string $args['displayname']   Pretty plugin name, for display
 * @param string $args['version']       Plugin version, default 'unknown'
 * @param bool $args['upgrade']         Force plugin update, default false
 * @return array
 */
function base_javascriptapi_registerplugin($args)
{
    extract($args);

    if(!isset($upgrade)) {
        $upgrade = false;
    } else {
        $upgrade = (bool) $upgrade;
    }

    if (!isset($name)) {
        $msg = xarML('Missing framework plugin name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    if (!isset($framework)) {
        $msg = xarML('Missing framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    if (!isset($displayname)) {
        $msg = xarML('Missing framework plugin display name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    if (!isset($version)) {
        $version = 'unknown';
    }

    $name = strtolower($name);
    $framework = strtolower($framework);

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $framework));

    if (!is_array($fwinfo)) {
        $msg = xarML('Bad framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    $plugins = xarModGetVar($fwinfo['module'], $framework . ".plugins");
    $plugins = @unserialize($plugins);

    if (!is_array($plugins)) {
        $plugins = array();
    }

    $plugin = array('version' => $version, 'displayname' => $displayname);

    if (isset($plugins[$name]) && !$upgrade) {
        $msg = xarML('Cannot overwrite #(1) plugin #(2) without force', $framework, $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    $plugins[$name] = $plugin;
    ksort($plugins);
    xarModSetVar($fwinfo['module'], $framework . ".plugins");

    return true;
}

?>

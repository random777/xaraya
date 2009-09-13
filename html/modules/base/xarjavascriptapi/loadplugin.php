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
 * Load a JS framework plugin
 * @author Marty Vance
 * @param $args['framework'] name of the framework.  Default: xarModGetVar('base','DefaultFramework');
 * @param $args['modName'] name of the framework's host module.  Default: derived from $args['name']
 * @param $args['name'] name of the plugin
 * @return bool
 */
function base_javascriptapi_loadplugin($args)
{
    extract($args);

    if (isset($name)) { $name = strtolower($name); }
    if (isset($framework)) { $framework = strtolower($framework); }
    if (isset($modName)) { $modName = strtolower($modName); }

    if (!isset($framework)) {
        $msg = xarML('Missing framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    if (!isset($modName) || !xarModIsAvailable($modName)) {
        $msg = xarML('Missing or bad framework host module');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }


    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $name));

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

    if (!isset($plugins[$name])) {
        $msg = xarML('Unknown plugin #(1) for framework #(2) without force', $name, $framework);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    // pass to plugin init function
    $args['name'] = $name;
    $args['modName'] = $modName;
    $args['framework'] = $framework;

    $init = xarModAPIFunc($modName, $name, 'loadplugin', $args);

    return $init;
}

?>
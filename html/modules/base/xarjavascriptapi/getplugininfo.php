<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Get JS framework plugin info
 * @author Marty Vance
 * @param string $args['name']          Name of the plugin
 * @param string $args['framework']     Name of the framework
 * @param bool $args['all'] return all plugins for a framework (optional)
 * @return array
 */
function base_javascriptapi_getplugininfo($args)
{
    extract($args);

    if(!isset($all)) {
        $all = false;
    } else {
        $all = (bool) $all;
    }

    // name and framework are required
    if (!isset($name) && !$all) {
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

    if (isset($name)) {
        $name = strtolower($name);
    }
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

    if (isset($all) && $all) {
        return $plugins;
    }

    if (isset($plugins[$name])) {
        return $plugins[$name];
    } else {
        return;
    }
}

?>
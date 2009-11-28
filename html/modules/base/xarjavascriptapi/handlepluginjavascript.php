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
 * Handle render javascript framework plugin tags
 * Handle <xar:base-js-plugin .../> tags
 * Format : <xar:base-js-plugin name="thickbox" framework="jquery" file="thickbox-compressed.js" />
 *
 * @author Marty Vance
 * @param string $args['framework']     Framework name (default from base modvar: DefaultFramework)
 * @param string $args['name']          Name of the plugin (required)
 * @param string $args['module']        Name of module plugin belongs to (optional)
 * @param string $args['file']          File name (optional)
 * @param string $args['style']         File name(s) of associated CSS (array, or semicolon delimited list)
 * @return string empty string
 */
function base_javascriptapi_handlepluginjavascript($args)
{
    extract($args);

    // no name, bail
    if (!isset($name)) return '';
    $name = strtolower($name);
    // no framework, get default
    if (!isset($framework)) {
        $framework = xarModGetVar('base','DefaultFramework');
    }
    $framework = strtolower($framework);
    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $framework));
    // no framework info, bail
    if (!is_array($fwinfo)) return '';
    // framework disabled, bail
    if ($fwinfo['status'] != 1) {
        return '';
    }
    // unknown plugin, bail
    if (!isset($fwinfo['plugins'][$name])) return '';

    if (!isset($module)) {
        // get default module for plugin
        if (isset($fwinfo['plugins'][$name]['defaultmod'])) {
            $module = $fwinfo['plugins'][$name]['defaultmod'];
            // check module has plugins
            if (!isset($fwinfo['plugins'][$name]['modules'][$module])) {
                unset($module);
            }
        }
        // fall back to framework module
        if (!isset($module) && isset($fwinfo['module'])) {
            $module = $fwinfo['module'];
        }
    }
    // no module name, bail
    if (!isset($module) || !xarModIsAvailable($module)) return '';
    $module = strtolower($module);

    if (empty($file)) {
        // get default module plugin file
        if (isset($fwinfo['plugins'][$name]['modules'][$module]['defaultfile'])) {
            $file = $fwinfo['plugins'][$name]['modules'][$module]['defaultfile'];
        }
        // fall back to plugin default
        if (!isset($file) && isset($fwinfo['plugins'][$name]['defaultfile'])) {
            $file = $fwinfo['plugins'][$name]['defaultfile'];
        }
    }
    // check file exists
    if (empty($file) || !isset($fwinfo['plugins'][$name]['modules'][$module]['files'][$file])) {
        return '';
    }

    if (!isset($style)) {
        $style = '';
    }
    if (is_array($style)) {
        implode(';', $style);
    }

    return "
        xarModAPIFunc('base','javascript','loadplugin', array('name' => '$name', 'modName' => '$module', 'file' => '$file', 'style' => '$style'));
    ";
}

?>

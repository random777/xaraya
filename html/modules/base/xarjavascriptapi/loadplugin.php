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
 * Load a JS framework plugin
 * @author Marty Vance
 * @param string $args['framework']    Name of the framework.  Default: xarModGetVar('base','DefaultFramework')
 * @param string $args['modName']      Name of the framework's host module.
 * @param string $args['name']         Name of the plugin (required)
 * @param string $args['file']         File name of the plugin (required)
 * @param string $args['style']        File name(s) of associated CSS (array, or semicolon delimited list)
 * @return bool
 */
function base_javascriptapi_loadplugin($args)
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

    if (!isset($modName)) {
        // get default module for plugin
        if (isset($fwinfo['plugins'][$name]['defaultmod'])) {
            $modName = $fwinfo['plugins'][$name]['defaultmod'];
            // check module has plugins
            if (!isset($fwinfo['plugins'][$name]['modules'][$modName])) {
                unset($modName);
            }
        }
        // fall back to framework module
        if (!isset($modName) && isset($fwinfo['module'])) {
            $modName = $fwinfo['module'];
        }
    }
    // no module name, bail
    if (!isset($modName) || !xarModIsAvailable($modName)) return '';
    $modName = strtolower($modName);

    if (empty($file)) {
        // get default module plugin file
        if (isset($fwinfo['plugins'][$name]['modules'][$modName]['defaultfile'])) {
            $file = $fwinfo['plugins'][$name]['modules'][$modName]['defaultfile'];
        }
        // fall back to plugin default
        if (!isset($file) && isset($fwinfo['plugins'][$name]['defaultfile'])) {
            $file = $fwinfo['plugins'][$name]['defaultfile'];
        }
    }
    // check file exists
    if (empty($file) || !isset($fwinfo['plugins'][$name]['modules'][$modName]['files'][$file])) {
        return '';
    }

    if (!xarModAPIFunc($modName, $framework, 'init', array())) return '';

    $filepath = xarModAPIfunc('base', 'javascript', '_findfile', array('module' => $modName, 'filename' => "$framework/plugins/$name/$file"));

    if (empty($filepath)) {
        return '';
    }

    $GLOBALS['xarTpl_JavaScript'][$framework . '_plugins'][$file] = array(
            'type' => 'src',
            'data' => xarServerGetBaseURL() . $filepath
        );

    // pass to framework's loadplugin function
    $args['name'] = $name;
    $args['modName'] = $modName;
    $args['framework'] = $framework;
    $args['file'] = $file;
    $args['filepath'] = $filepath;

    if (isset($style) && !empty($style)) {
        if(is_string($style)) {
            $style = explode(';', $style);
        }
        foreach ($style as $stylesheet) {
            //themes_userapi_register
            $styleload = xarModAPIFunc('themes','user','register', array(
                'scope' => 'module',
                'module' => $modName,
                'file' => $framework . '/plugins/' . $name . '/' . preg_replace('/\.css$/', '', $stylesheet)
            ));
        }

    }

    $load = xarModAPIFunc($modName, $framework, 'loadplugin', $args);

    return $load;
}

?>

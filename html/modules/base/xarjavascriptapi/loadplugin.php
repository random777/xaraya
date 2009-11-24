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
 * @param string $args['modName']      Name of the framework's host module.  Default: derived from $args['framework']
 * @param string $args['name']         Name of the plugin (required)
 * @param string $args['file']         File name of the plugin (required)
 * @param string $args['style']        File name(s) of associated CSS (array, or semicolon delimited list)
 * @return bool
 */
function base_javascriptapi_loadplugin($args)
{
    extract($args);

    if (isset($name)) { $name = strtolower($name); }
    if (isset($framework)) { $framework = strtolower($framework); }
    if (isset($modName)) { $modName = strtolower($modName); }

    if (!isset($framework)) {
        $framework = xarModGetVar('base','DefaultFramework');
    }

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $framework));

    if (!is_array($fwinfo)) {
        $msg = xarML('Bad framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    if ($fwinfo['status'] != 1) {
        return '';
    }

    if (!isset($modName) || !xarModIsAvailable($modName)) {
        $modName = $fwinfo['module'];
    }
    if (!isset($file) || $file == '') {
        $msg = xarML('Missing plugin file name');
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

    // ensure framework init has happened
    if (!isset($GLOBALS['xarTpl_JavaScript'][$framework])) {
        $fwinit = xarModAPIFunc($modName, $framework, 'init', array());
        if (!$fwinit) {
            $msg = xarML('Framework #(1) init failed', $framework);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }
    }

    $filepath = xarModAPIfunc('base', 'javascript', '_findfile', array('module' => $modName, 'filename' => "$framework/plugins/$name/$file"));

    if (empty($filepath)) {
        $msg = xarML('Plugin file \'#(1)\' (#(2) in #(3)) could not be found', $file, $name, $framework);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
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

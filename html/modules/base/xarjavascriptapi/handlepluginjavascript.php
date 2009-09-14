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
 * Handle render javascript framework plugin tags
 * Handle <xar:base-js-plugin .../> tags
 * Format : <xar:base-js-plugin name="thickbox" framework="jquery" file="thickbox-compressed.js" />
 *
 * @author Marty Vance
 * @param string $args['framework']     framework name (default from base modvar: DefaultFramework)
 * @param string $args['name']          name of the plugin (required)
 * @param string $args['file']          file name (required)
 * @return string empty string
 */
function base_javascriptapi_handlepluginjavascript($args)
{
    extract($args);

    $name = strtolower($name);
    $framework = strtolower($framework);

    if (!isset($framework)) {
        $framework = xarModGetVar('base','DefaultFramework');
    }
    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $framework));
    if (!is_array($fwinfo)) {
        $msg = xarML('Could not retreive info for framework #(1)', $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    extract($fwinfo);

    $module = $fwinfo['module'];

    $plinfo = xarModAPIFunc('base','javascript','getplugininfo', array('name' => $name, 'framework' => $framework));
    if (!is_array($plinfo)) {
        $msg = xarML('Could not retreive info for plugin #(1) in framework #(2)', $name, $framework);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    $name = addslashes($name);
    $framework = addslashes($framework);

    if (empty($file)) {
        $msg = xarML('Missing JS framework plugin file name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    return "
        xarModAPIFunc('base','javascript','loadplugin', array('name' => '$name', 'modName' => '$module', 'file' => '$file'));
    ";


    // Ensure framework is initialized
    if (!is_array($GLOBALS['xarTpl_JavaScript']['frameworks'][$name])) {
        $init = xarModAPIFunc('base','javascript','init', array('name' => $framework, 'modName' => $module, 'file' => ''));
        if (!$init) {
            $msg = xarML('#(1) initialization falied', $name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
        }
    }

    // Don't try to put a plugin in the JS queue more than once
    if (!in_array($file, $GLOBALS['xarTpl_JavaScript']['frameworks'][$framework]['plugins'])) {
        $GLOBALS['xarTpl_JavaScript']['frameworks'][$framework]['plugins'][] = $file;
    }

    // Call xarTplPlugin
    return "
        echo xarTplPlugin($module, $framework, $name, $tpldata, $template);
    ";
}

?>

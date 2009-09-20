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
 * @param string $args['framework']     Framework name (default from base modvar: DefaultFramework)
 * @param string $args['name']          Name of the plugin (required)
 * @param string $args['file']          File name (required)
 * @param string $args['style']         File name(s) of associated CSS (array, or semicolon delimited list)
 * @return string empty string
 */
function base_javascriptapi_handlepluginjavascript($args)
{
    extract($args);

    $name = strtolower($name);
    
    if (!isset($framework)) {
        $framework = xarModGetVar('base','DefaultFramework');
    }
    $framework = strtolower($framework);

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $framework));
    if (!is_array($fwinfo)) {
        $msg = xarML('Could not retreive info for framework #(1)', $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

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

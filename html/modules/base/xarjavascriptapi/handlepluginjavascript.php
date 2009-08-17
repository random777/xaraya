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
 * Format : <xar:base-js-plugin name="whatever" framework="jquery" />
 * Typical use in the head section is: <xar:base-render-javascript position="head"/>
 *
 * @author Marty Vance
 * @param $args['framework']    framework name (default from base modvar: DefaultFramework)
 * @param string $name          name of the plugin (required)
 * @param string $tpldata       template data (default empty array)
 * @param string $template      template name (required)
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
    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', $framework);
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

    if (empty($template)) {
        $msg = xarML('Missing JS framework template name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    if (empty($tpldata) || !is_array($tpldata)) {
        $tpldata = array();
    }

    // Call xarTplPlugin
    return "
        echo htmlspecialchars(xarTplPlugin($module, $framework, $name, $tpldata, $template));
    ";
}

?>
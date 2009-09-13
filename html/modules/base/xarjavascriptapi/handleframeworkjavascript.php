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
 * Handle render javascript framework tags
 * Handle <xar:base-js-framework .../> tags
 * Format: <xar:base-js-framework template="init" />
 *      or <xar:base-js-framework module="base" name="jquery" template="init" tpldata="$somedata" />
 * @param string $name          framework name (default from base modvar: DefaultFramework)
 * @param string $module        host module of the framework (default from framework info)
 * @param string $tpldata       template data (default empty array)
 * @param string $template      template name (required)
 * Typical use in the head section is: <xar:base-js-framework />
 *
 * @author Marty Vance
 * @param $args array containing the form field definition or the type, position, ...
 * @return string empty string
 */
function base_javascriptapi_handleframeworkjavascript($args)
{
    extract($args);

    if (isset($name)) { $name = strtolower($name); }
    if (isset($module)) { $module = strtolower($module); }
    if (isset($template)) { $template = strtolower($template); }

    if (!isset($name)) {
        $name = xarModGetVar('base','DefaultFramework');
    }
    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $name));
    
    if (!is_array($fwinfo)) {
        $msg = xarML('Could not retreive info for framework #(1)', $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }
    extract($fwinfo);

    if (empty($module)) {
        $msg = xarML('Missing JS framework module name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
    } else {
        $module = addslashes($module);
    }
    if (empty($tpldata) || !is_array($tpldata)) {
        $tpldata = array();
    }
    if (empty($template)) {
        $msg = xarML('Missing framework template name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
    }

    // Ensure framework is initialized
    if ($template == 'init' && !is_array($GLOBALS['xarTpl_JavaScript']['frameworks'][$name])) {
        $init = xarModAPIFunc('base','javascript','init', array('name' => $name, 'modName' => $module));
        if (!$init) {
            $msg = xarML('#(1) initialization falied', $name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
        }
    }

    // Call xarTplFramework
    return "
        echo xarTplFramework('$module', '$name', array(), '$template');
    ";
}

?>

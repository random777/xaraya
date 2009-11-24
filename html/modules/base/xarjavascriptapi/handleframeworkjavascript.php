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
 * Handle render javascript framework tags
 * Handle <xar:base-js-framework .../> tags
 * Format: <xar:base-js-framework file="jquery-1.3.2.min.js" />
 *      or <xar:base-js-framework module="base" name="jquery" file="jquery-1.3.2.min.js" />
 * Typical use in the head section is: <xar:base-js-framework />
 *
 * @author Marty Vance
 * @param string $args['name']      Framework name (default from base modvar: DefaultFramework)
 * @param string $args['module']    Host module of the framework (default from framework info)
 * @param string $args['file']      File name (required)
 * @return string empty string
 */
function base_javascriptapi_handleframeworkjavascript($args)
{
    extract($args);

    if (isset($name)) { $name = strtolower($name); }
    if (isset($module)) { $module = strtolower($module); }

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
    if (!isset($file)) {
        $msg = xarML('Missing framework file name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
    }

    return "
        xarModAPIFunc('base','javascript','init', array('name' => '$name', 'modName' => '$module', 'file' => '$file'));
    ";

}

?>

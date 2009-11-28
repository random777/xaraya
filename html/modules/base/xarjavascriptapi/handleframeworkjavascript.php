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
 * @param string $args['module']    Name of the framework's host module. (Deprecated) Default: module fw belongs to
 * @param string $args['file']      File name (optional)
 * @return string empty string
 */
function base_javascriptapi_handleframeworkjavascript($args)
{
    extract($args);

    if (!isset($name)) $name = xarModGetVar('base','DefaultFramework');
    if (empty($name)) return '';
    $name = strtolower($name);

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $name));
    if (!is_array($fwinfo)) return '';

    if ($fwinfo['status'] != 1) {
        return '';
    }

    if (empty($fwinfo['module'])) return '';
    $module = $fwinfo['module'];
    if (!xarModIsAvailable($module)) return '';

    if (!isset($file) && isset($fwinfo['file'])) {
        $file = $fwinfo['file'];
    }

    return "
        xarModAPIFunc('base','javascript','init', array('name' => '$name', 'modName' => '$module', 'file' => '$file'));
    ";

}

?>

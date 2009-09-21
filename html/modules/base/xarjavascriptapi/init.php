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
 * Inititalize a JS framework
 * @author Marty Vance
 * @param string $args['name']      Name of the framework.  Default: xarModGetVar('base','DefaultFramework');
 * @param string $args['modName']   Name of the framework's host module.  Default: derived from $args['name']
 * @param string $args['file']      Base file name of the framework (required)
 * @return bool
 */
function base_javascriptapi_init($args)
{
    extract($args);

    if (isset($name)) {
        $name = strtolower($name);
    } else {
        $name = xarModGetVar('base','DefaultFramework');
    }
    if (isset($modName)) {
        $modName = strtolower($modName);
    } else {
        $modName = '';
    }

    if (!isset($name)) {
        $msg = xarML('Missing framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    if (!isset($file)) {
        // pass thru for more specific handling
        $file = '';
    }

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $name));

    if (!is_array($fwinfo)) {
        $msg = xarML('Bad framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    if ($fwinfo['status'] != 1) {
        return '';
    }

    if($modName != $fwinfo['module']) {
        $modName = $fwinfo['module'];
    }

    if (!isset($modName) || !xarModIsAvailable($modName)) {
        $msg = xarML('Missing or bad framework host module');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    // Set up $GLOBALS['xarTpl_JavaScript'] indexes for the framework
    if (!isset($GLOBALS['xarTpl_JavaScript'][$name])) {
        $GLOBALS['xarTpl_JavaScript'][$name] = array();
    }
    if (!isset($GLOBALS['xarTpl_JavaScript'][$name . '_plugins'])) {
        $GLOBALS['xarTpl_JavaScript'][$name . '_plugins'] = array();
    }
    // Events will be set up as needed

    $filepath = xarModAPIfunc('base', 'javascript', '_findfile', array('module' => $modName, 'filename' => "$name/$file"));

    if (empty($filepath)) {
        $msg = xarML('File \'#(1)\' in #(2) could not be found', $file, $name);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    $GLOBALS['xarTpl_JavaScript'][$name][$file] = array(
            'type' => 'src',
            'data' => xarServerGetBaseURL() . $filepath
        );

    // pass to framework init function for any extra processing
    $args['name'] = $name;
    $args['modName'] = $modName;
    $args['file'] = $file;
    $args['filepath'] = $filepath;

    $init = xarModAPIFunc($modName, $name, 'init', $args);

    return $init;
}

?>

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
 * @param $args['name'] name of the framework.  Default: xarModGetVar('base','DefaultFramework');
 * @param $args['modName'] name of the framework's host module.  Default: derived from $args['name']
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

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $name));

    if (!is_array($fwinfo)) {
        $msg = xarML('Bad framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
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

    // pass to framework init function
    $args['name'] = $name;
    $args['modName'] = $modName;

    $init = xarModAPIFunc($modName, $name, 'init', $args);

    return $init;
}

?>

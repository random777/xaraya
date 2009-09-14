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
 * Load a JS framework plugin
 * @author Marty Vance
 * @param $args['name']         name of the plugin
 * @param $args['file']         file name to load
 * @param $args['filepath']     path to the file
 * @return bool
 */
function base_jqueryapi_loadplugin($args)
{
    extract($args);

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => 'jquery'));

    if (!is_array($fwinfo)) {
        $msg = xarML('Bad framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    $framework = 'jquery';

    $plugins = xarModGetVar('base', 'jquery' . ".plugins");
    $plugins = @unserialize($plugins);

    if (!is_array($plugins)) {
        $plugins = array();
    }          

    if (!isset($plugins[$name])) {           
        $msg = xarML('Unknown plugin #(1) for framework #(2) without force', $name, 'jquery');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    // ensure framework init has happened
    if (!isset($GLOBALS['xarTpl_JavaScript']['frameworks']['jquery'])) {
        $fwinit = xarModAPIFunc('base', 'jquery', 'init', array());
        if (!$fwinit) {
            $msg = xarML('Framework #(1) init failed', 'jquery');
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }
    }

    $GLOBALS['xarTpl_JavaScript']['frameworks']['jquery']['plugins'][] = array(
            'type' => 'src',
            'data' => xarServerGetBaseURL() . $filepath
        );

    return true;
}

?>

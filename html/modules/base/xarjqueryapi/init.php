<?php
/**
 * Base jQuery management functions
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
 * Inititalize jQuery framework
 * @author Marty Vance
 * @param $args['file'] string filename for the framework JS
 * @param $args['filepath'] path to the file
 * @return bool
 */
function base_jqueryapi_init($args)
{
    extract($args);

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => 'jquery'));

    if (!is_array($fwinfo)) {
        $msg = xarML('Bad framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    // all frameworks should assume a default file name
    if (!isset($file) || $file == '') {
        $file = 'jquery-' . $fwinfo['version'] . '.min.js';
    }

    // Set up $GLOBALS['xarTpl_JavaScript']['frameworks'] indices for the framework
    // The array for each framework must contains these indices: 
    // array files, string module, array plugins, array events
    if (!isset($GLOBALS['xarTpl_JavaScript']['frameworks']['jquery'])) {
        $GLOBALS['xarTpl_JavaScript']['frameworks']['jquery'] = array(
            'files' => array(),
            'module' => 'base',
            'plugins' => array(),
            'events' => array()
        );
    }

    $GLOBALS['xarTpl_JavaScript']['frameworks']['jquery']['files'][] = array(
            'type' => 'src',
            'data' => xarServerGetBaseURL() . $filepath
        );

    // perform other init tasks here

    return true;
}

?>

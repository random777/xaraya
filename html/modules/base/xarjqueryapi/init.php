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

    // Set up $GLOBALS['xarTpl_JavaScript']['frameworks'] indices for the framework
    // The array for each framework must contains these arrays: file, plugins, events
    if (!isset($GLOBALS['xarTpl_JavaScript']['frameworks']['jquery'])) {
        $GLOBALS['xarTpl_JavaScript']['frameworks']['jquery'] = array(
            'file' => '',
            'plugins' => array();
            'events' => array(
                'abort' => array(),
                'click' => array(),
                'dblclick' => array(),
                'keydown' => array(),
                'keypress' => array(),
                'keyup' => array(),
                'load' => array(),
                'mousedown' => array(),
                'mousemove' => array(),
                'mouseout' => array(),
                'mouseover' => array(),
                'mouseup' => array(),
                'ready' => array(),
                'resize' => array(),
                'unload' => array()
            )
        );
    }

    return true;
}

?>

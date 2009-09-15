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
 * Add code to a framework event handler
 * @author Marty Vance
 * @param string $args['framework']    Name of the framework.  Default: xarModGetVar('base','DefaultFramework')
 * @param string $args['modName']      Name of the framework's host module.  Default: derived from $args['framework']
 * @param string $args['name']         Name of the event (required)
 * @param string $args['file']         Filename containing code (required), with
 * @param string $args['filepath']     Path to the given file; or
 * @param string $args['code']         String containing code (required)
 * @return bool
 */
function base_javascriptapi_appendframeworkevent($args)
{
    extract($args);

    if (isset($name)) { $name = strtolower($name); }
    if (isset($framework)) { $framework = strtolower($framework); }
    if (isset($modName)) { $modName = strtolower($modName); }
    if (isset($file)) { $file = strtolower($file); }

    if (!isset($framework)) {
        $framework = 'jquery';
    }

    $fwinfo = xarModAPIFunc('base','javascript','getframeworkinfo', array('name' => $framework));

    if (!is_array($fwinfo)) {
        $msg = xarML('Bad framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    if (!isset($modName) || !xarModIsAvailable($modName)) {
        $modName = 'base';
    }
    if ((!isset($file) || $file == '') || (!isset($code) || $code == '')) {
        $msg = xarML('File or code required to append event');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }

    // ensure framework init has happened
    if (!isset($GLOBALS['xarTpl_JavaScript']['frameworks'][$framework])) {
        $fwinit = xarModAPIFunc($modName, $framework, 'init', array());
        if (!$fwinit) {
            $msg = xarML('Framework #(1) init failed', $framework);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                            new SystemException($msg));
            return;
        }
    }

    if (!isset($GLOBALS['xarTpl_JavaScript']['frameworks'][$framework]['events'][$name])) {
        $GLOBALS['xarTpl_JavaScript']['frameworks'][$framework]['events'][$name] = array();
    }

    if (isset($file) && !empty($file) && isset($filepath) && !empty($filepath)) {
        // load the file contents as a string
        if (file_exists($filepath)) {
            $code = @file_get_contents($filepath);
            if (!$code) {
                $msg = xarML('Could not append #(1) to event #(2) in #(3)', $file, $name, $framework);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                                new SystemException($msg));
                return;
            }
        }
    } else {
        $file = md5($code);
    }

    $GLOBALS['xarTpl_JavaScript']['frameworks'][$framework]['events'][$name][$file] = array(
            'type' => 'code',
            'data' => $code            
        );

    return true;
}

?>

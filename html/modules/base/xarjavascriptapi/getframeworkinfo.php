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
 * Get JS framework info
 * @author Marty Vance
 * @param string $args['name']  Name of the framework
 * @param bool $args['all']     Return all frameworks (optional)
 * @return array
 */
function base_javascriptapi_getframeworkinfo($args)
{
    extract($args);

    if(!isset($all)) {
        $all = false;
    } else {
        $all = (bool) $all;
    }

    // name is required unless $all is true
    if (!isset($name) && !$all) {
        $msg = xarML('Missing framework name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                        new SystemException($msg));
        return;
    }


    $fwinfo = xarModGetVar('base','RegisteredFrameworks');
    $fwinfo = @unserialize($fwinfo);

    if (isset($all) && $all) {
        return $fwinfo;
    }

    if(isset($name)) {
        $name = strtolower($name);
    }

    if (isset($name) && isset($fwinfo[$name])) {
        return $fwinfo[$name];
    } else {
        return;
    }
}

?>
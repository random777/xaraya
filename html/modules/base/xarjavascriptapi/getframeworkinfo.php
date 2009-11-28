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
 * Get JS framework info
 * @author Marty Vance
 * @param string $args['name']  Name of the framework (optional, default all)
 * @return array
 */
function base_javascriptapi_getframeworkinfo($args)
{
    extract($args);

    $fwinfo = xarModGetVar('base','RegisteredFrameworks');
    $fwinfo = @unserialize($fwinfo);

    if(isset($name) && is_string($name)) {
        $name = strtolower($name);
        if (isset($fwinfo[$name])) {
            return $fwinfo[$name];
        } else {
            return;
        }
    } else {
        return $fwinfo;
    }
}
?>
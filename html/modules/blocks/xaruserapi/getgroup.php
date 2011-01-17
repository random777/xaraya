<?php
/**
 * Retrieve a group raw data.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * Retrieve a group raw data.
 * @author Jim McDonald, Paul Rosania
*/

function blocks_userapi_getgroup($args)
{
    extract($args);

    if (!xarVarValidate('int:1', $gid, true)) {$gid = 0;}
    if (!xarVarValidate('str:1', $name, true)) {$name = '';}

    if (empty($gid) && empty($name)) {
        // No identifier provided.
        $msg = xarML('Invalid parameter: missing gid and name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // The getall function does the main work.
    if (!empty($gid)) {
        $group = xarMod::apiFunc('blocks', 'user', 'getallgroups', array('gid' => $gid));
    } else {
        $group = xarMod::apiFunc('blocks', 'user', 'getallgroups', array('name' => $name));
    }

    // If exactly one row was found then return it.
    if (count($group) == 1) {
        return array_pop($group);
    } else {
        return;
    }
}

?>

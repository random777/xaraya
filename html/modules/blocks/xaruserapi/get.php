<?php
/**
 * Retrieve a block instance raw data.
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 * @author Jim McDonald, Paul Rosania
*/

function blocks_userapi_get($args)
{
    extract($args);

    if (!xarVarValidate('int:1', $bid, true)) {$bid = 0;}
    if (!xarVarValidate('str:1', $name, true)) {$name = '';}

    if (empty($bid) && empty($name)) {
        // No identifier provided.
        $msg = xarML('Invalid parameter: missing bid or name');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // The getall function does the main work.
    if (!empty($bid)) {
      // CHECKME: & removed below
        $instances = xarMod::apiFunc('blocks', 'user', 'getall', array('bid' => $bid));
    } else {
        // CHECKME: & removed below
        $instances = xarMod::apiFunc('blocks', 'user', 'getall', array('name' => $name));
    }

    // If exactly one row was found then return it.
    if (count($instances) == 1) {
        $instance = array_pop($instances);
        return $instance;
    } else {
        return;
    }
}

?>

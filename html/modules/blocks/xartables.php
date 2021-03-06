<?php
/**
 * Blocks table management and initialization
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 */

function blocks_xartables()
{
    // Initialise table array
    $xartable = array();

    // Get the name for the example item table.  This is not necessary
    // but helps in the following statements and keeps them readable
    $userblocks = xarDBGetSiteTablePrefix() . '_userblocks';
    $blocktypes = xarDBGetSiteTablePrefix() . '_block_types';
    $cacheblocks = xarDBGetSiteTablePrefix() . '_cache_blocks';

    // Set the table name
    $xartable['userblocks'] = $userblocks;
    $xartable['block_types'] = $blocktypes;
    $xartable['cache_blocks'] = $cacheblocks;

    // Return the table information
    return $xartable;
}

?>
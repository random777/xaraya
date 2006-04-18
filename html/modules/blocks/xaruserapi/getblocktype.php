<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/*
 * Get a single block type.
 * @param args['tid'] block type ID (optional)
 * @param args['module'] module name (optional, but requires 'type')
 * @param args['type'] block type name (optional, but requires 'module')
 * @returns array of block types, keyed on block type ID
 * @author Jason Judge
*/

function blocks_userapi_getblocktype($args)
{
    // Minimum parameters allowed, to fetch a single block type: tid or type.
    if (empty($args['tid']) && (empty($args['module']) || empty($args['type']))) {
        throw new BadParameterException(array('tid','module','type'),'The parameters #(1) and #(2)/#(3) have not been set');
    }

    $types = xarModAPIfunc('blocks', 'user', 'getallblocktypes', $args);

    // We should have exactly one block type: throw back if not.
    if (count($types) <> 1) {return;}

    return(array_pop($types));
}

?>
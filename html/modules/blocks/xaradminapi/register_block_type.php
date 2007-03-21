<?php
/**
 * Register a block type
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Register block type
 *
 * @author Jim McDonald, Paul Rosania
 * @access public
 * @param array $args
 * @param modName the module name (deprecated)
 * @param blockType the block type (deprecated)
 * @param args['module'] the module name
 * @param args['type'] the block type
 * @return array template info with $args
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function blocks_adminapi_register_block_type($args)
{
    return xarModAPIfunc('blocks', 'admin', 'create_type', $args);
}

?>
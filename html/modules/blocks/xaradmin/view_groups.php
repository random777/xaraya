<?php
/**
 * View block groups
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
 * view block groups
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_view_groups()
{
    // Security Check
    if (!xarSecurityCheck('AdminBlock', 0, 'Instance')) {return;}
    $authid = xarSecGenAuthKey();

    $block_groups = xarMod::apiFunc(
        'blocks', 'user', 'getallgroups', array('order' => 'name')
    );

    // Load up groups array
    foreach($block_groups as $index => $block_group) {
        $block_groups[$index]['id'] = $block_group['gid']; // Legacy

        // Get details on current group
        $block_groups[$index] = xarMod::apiFunc(
            'blocks', 'admin', 'groupgetinfo',
            array('blockGroupId' => $block_groups[$index]['gid'])
        );
        $block_groups[$index]['membercount'] = count($block_groups[$index]['instances']);
        $block_groups[$index]['deleteconfirm'] = xarML('Delete group "#(1)"?', addslashes($block_group['name']));
        $block_groups[$index]['deleteurl'] = xarModUrl('blocks', 'admin', 'delete_group', array('gid' => $block_group['gid'], 'authid' => $authid));
    }

    return array('block_groups' => $block_groups);
}

?>

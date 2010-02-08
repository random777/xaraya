<?php
/**
 * @package modules
 * @copyright see the html/credits.html file in this release
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
        // Get details on current group
        $block_groups[$index] = xarMod::apiFunc(
            'blocks', 'admin', 'groupgetinfo',
            array('blockGroupId' => $block_groups[$index]['id'])
        );
        $block_groups[$index]['name'] = $block_group['name'];
        $block_groups[$index]['id'] = $block_group['id']; // Legacy
        $block_groups[$index]['membercount'] = count($block_groups[$index]['instances']);
        $block_groups[$index]['deleteconfirm'] = xarML('Delete group #(1)?', $block_group['name']);
        $block_groups[$index]['deleteurl'] = xarModUrl('blocks', 'admin', 'delete_group', array('id' => $block_group['id'], 'authid' => $authid));
    }
    return array('block_groups' => $block_groups);
}

?>

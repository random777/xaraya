<?php
/**
 * Update the group details for a block instance
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * update the group details for a block instance
 *
 * @author Jim McDonald, Paul Rosania
 * @param $args['bid'] the ID of the block to update
 * @param $args['title'] the new title of the block
 * @param $args['group_id'] the new position of the block (deprecated)
 * @param $args['groups'] optional array of group memberships
 * @param $args['template'] the template of the block instance
 * @param $args['content'] the new content of the block
 * @param $args['refresh'] the new refresh rate of the block
 * @returns bool
 * @return true on success, false on failure
 */
function blocks_adminapi_update_instance_groups($args)
{
    $template = null;
    extract($args);

    // The group instances are updated according to the
    // $groups parameter.
    // $groups is an array of _current_ group memberships.
    // Each group membership is an array:
    // 'id' - group ID
    // 'template' - the over-ride template for this block group instance
    // This function will add, update or delete group memberships for
    // the block instance to leave the group membership state as defined
    // by the $groups array.

    if (!isset($groups) || !is_array($groups) || !isset($bid) || !is_numeric($bid)) {
        return;
    }

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $block_groups_table = $xartable['block_instances'];
    $block_instances_table = $xartable['block_instances'];
    $block_group_instances_table = $xartable['block_group_instances'];

    // Get the current group membership for this block instance.
    $query = 'SELECT id, group_id, template'
        . ' FROM ' . $block_group_instances_table
        . ' WHERE instance_id = ?';
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array($bid));

    $current = array();
    while ($result->next()) {
        $id = $result->getInt(2);

        $current[$id] = array (
            'id'        => $result->getInt(1),
            'id'       => $id,
            'template'  => $result->getString(3)
        );
    }

    // Get all groups for the main update loop.
    $allgroups = xarMod::apiFunc('blocks', 'user', 'getallgroups');

    // Key the new groups on the id for convenience
    $newgroups = array();
    foreach($groups as $group) {
        // Set default template. This comes into play when
        // creating a new block instance, and assigning it
        // to a group at the same time.
        if (!isset($group['template'])) {
            $group['template'] = null;
        }

        $newgroups[$group['id']] = $group;
    }

    $query_arr = array();

    // Now we need to create a set of insert/update/delete commands.
    // If sessions were available, I would normally delete all the rows
    // and then insert new ones. In this case we don't want to do that
    // as an error anywhere in this code or data could result in all existing
    // block group associations being lost.

    // Prepare the queries we need in the loop
    $delQuery = "DELETE FROM $block_group_instances_table WHERE id = ?";
    $delStmt  = $dbconn->prepareStatement($delQuery);
    $insQuery = "INSERT INTO $block_group_instances_table
                (group_id, instance_id, position, template)
                VALUES (?,?,?,?)";
    $insStmt  = $dbconn->prepareStatement($insQuery);
    $updQuery = "UPDATE $block_group_instances_table
                 SET template = ?
                 WHERE id = ?";
    $updStmt  = $dbconn->prepareStatement($updQuery);

    // Loop for each group.
    foreach ($allgroups as $group) {
        $id = $group['id'];
        // If the group is not in the $groups array, and is in the
        // current instance groups, then it should be deleted.
        if (!isset($newgroups[$id]) && isset($current[$id])) {
            $delStmt->executeUpdate(array((int) $current[$id]['id']));
        }
        // If the new group does not exist, then create it.
        elseif (isset($newgroups[$id]) && !isset($current[$id])) {
            $insStmt->executeUpdate(array($id, $bid, 0,$newgroups[$id]['template']));
        }

        // If the new group already exists, then update it.
        elseif (isset($newgroups[$id]) && isset($current[$id])
            && $newgroups[$id]['template'] != $current[$id]['template'])
        {
            $updStmt->executeUpdate(array($newgroups[$id]['template'],$current[$id]['id']));
        }
    }
    // Resequence the position values, since we may have changed the existing values.
    // Span the resequence across all groups, since any number of groups could have
    // been affected.
    xarMod::apiFunc('blocks', 'admin', 'resequence');

    return true;
}

?>

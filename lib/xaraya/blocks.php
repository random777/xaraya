<?php
/**
 * Display Blocks
 * *
 * @package blocks
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Paul Rosania
 */


/**
 * Initialize blocks subsystem
 *
 * @author Paul Rosania
 * @access protected
 * @param  array args
 * @return bool
 */
function xarBlock_init(&$args)
{
    // Blocks Support Tables
    $prefix = xarDB::getPrefix();

    $tables = array(
        'block_instances'       => $prefix . '_block_instances',
        'block_groups'          => $prefix . '_block_groups',
        'block_group_instances' => $prefix . '_block_group_instances',
        'block_types'           => $prefix . '_block_types'
    );

    xarDB::importTables($tables);

    return true;
}

/**
 * Renders a block
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @param  array blockinfo block information parameters
 * @return string output the block to show
 * @throws  BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 * @todo   this function calls a module function, keep an eye on it
 */
function xarBlock_render($blockinfo)
{
    // Get a cache key for this block if it's suitable for block caching
    $cacheKey = xarCache::getBlockKey($blockinfo);

    // Check if the block is cached
    if (!empty($cacheKey) && xarBlockCache::isCached($cacheKey)) {
        // Return the cached block output
        return xarBlockCache::getCached($cacheKey);
    }

    $modName = $blockinfo['module'];
    $blockType = $blockinfo['type'];
    $blockName = $blockinfo['name'];

    xarLogMessage('xarBlock_render: begin '.$modName.':'.$blockType.':'.$blockName);

    // This lets the security system know what module we're in
    // no need to update / select in database for each block here
    // TODO: this looks weird
    xarCoreCache::setCached('Security.Variables', 'currentmodule', $modName);

    // Load the block.
    if (!xarMod::apiFunc(
        'blocks', 'admin', 'load',
        array('modName' => $modName, 'blockType' => $blockType, 'blockFunc' => 'display') )
    ) {return;}

    // Get the block display function name.
    $displayFuncName = "{$modName}_{$blockType}block_display";
    $classpath = sys::code() . 'modules/' . $modName . '/xarblocks/' . $blockType . '.php';

    // Fetch complete blockinfo array.
    if (function_exists($displayFuncName)) {
        // Allow the block to modify the content before rendering.
        // In fact, the block can access and alter any aspect of the block info.
        $blockinfo = $displayFuncName($blockinfo);

        if (!isset($blockinfo)) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            return '';
        }

        // FIXME: <mrb>
        // We somehow need to be able to raise exceptions here. We can't
        //       just ignore things which are wrong.
        // This would happen if a block does not return the blockinfo array correctly.
        if (!is_array($blockinfo)) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            return '';
        }

        // Handle the new block templating style.
        // If the block has not done the rendering already, then render now.
        if (is_array($blockinfo['content'])) {
            // Here $blockinfo['content'] is template data.

            // Set some additional details that the could be useful in the block.
            // TODO: prefix these extra variables (_bl_) to indicate they are supplied by the core.
            $blockinfo['content']['blockid'] = $blockinfo['bid'];
            $blockinfo['content']['blockname'] = $blockinfo['name'];
            $blockinfo['content']['blocktypename'] = $blockinfo['type'];
            if (isset($blockinfo['bgid'])) {
                // The block may not be rendered as part of a group.
                $blockinfo['content']['blockgid'] = $blockinfo['bgid'];
                $blockinfo['content']['blockgroupname'] = $blockinfo['group_name'];
            }

            // Render this block template data.
            $blockinfo['content'] = xarTplBlock(
                $modName, $blockType, $blockinfo['content'],
                $blockinfo['_bl_block_template'],
                !empty($blockinfo['_bl_template_base']) ? $blockinfo['_bl_template_base'] : NULL
            );
        }
    } elseif (file_exists($classpath)) {
        sys::import('modules.' . $modName . '.xarblocks.' . $blockType);
        sys::import('xaraya.structures.descriptor');
        $name = ucfirst($blockType) . "Block";
        $descriptor = new ObjectDescriptor(array());
        $block = new $name($descriptor);

        $blockinfo = $block->display($blockinfo);
        if (!is_array($blockinfo)) {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            return '';
        }
        if (is_array($blockinfo['content'])) {
            // Here $blockinfo['content'] is template data.

            // Set some additional details that the could be useful in the block.
            // TODO: prefix these extra variables (_bl_) to indicate they are supplied by the core.
            $blockinfo['content']['blockid'] = $blockinfo['bid'];
            $blockinfo['content']['blockname'] = $blockinfo['name'];
            $blockinfo['content']['blocktypename'] = $blockinfo['type'];
            if (isset($blockinfo['bgid'])) {
                // The block may not be rendered as part of a group.
                $blockinfo['content']['blockgid'] = $blockinfo['bgid'];
                $blockinfo['content']['blockgroupname'] = $blockinfo['group_name'];
            }

            // Render this block template data.
            $blockinfo['content'] = xarTplBlock(
                $modName, $blockType, $blockinfo['content'],
                $blockinfo['_bl_block_template'],
                !empty($blockinfo['_bl_template_base']) ? $blockinfo['_bl_template_base'] : NULL
            );
        } else {
            // Set the output of the block in cache
            if (!empty($cacheKey)) {
                xarBlockCache::setCached($cacheKey, '');
            }
            return "";
        }
    }

    // Now wrap the block up in a box.
    // TODO: pass the group name into this function (param 2?) for the template path.
    $boxOutput = xarTpl_renderBlockBox($blockinfo, $blockinfo['_bl_box_template']);

    xarLogMessage('xarBlock_render: end '.$modName.':'.$blockType.':'.$blockName);

    // Set the output of the block in cache
    if (!empty($cacheKey)) {
        xarBlockCache::setCached($cacheKey, $boxOutput);
    }

    return $boxOutput;
}

/**
 * Renders a block group
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @param string groupname the name of the block group
 * @param string template optional template to apply to all blocks in the group
 * @return string
 * @throws EmptyParameterException
 */
function xarBlock_renderGroup($groupname, $template = NULL)
{
    if (empty($groupname)) throw new EmptyParameterException('groupname');

    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();

    $blockGroupInstancesTable = $tables['block_group_instances'];
    $blockInstancesTable      = $tables['block_instances'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockTypesTable          = $tables['block_types'];
    $modulesTable             = $tables['modules'];

    // Fetch details of all blocks in the group.
    // CHECKME: Does this really have to be a quadruple left join, i cant imagine
    $query = "SELECT    inst.id as bid,
                        btypes.name as type,
                        mods.name as module,
                        inst.name as name,
                        inst.title as title,
                        inst.content as content,
                        inst.last_update as last_update,
                        inst.state as state,
                        group_inst.position as position,
                        bgroups.id              AS bgid,
                        bgroups.name            AS group_name,
                        bgroups.template        AS group_bl_template,
                        inst.template           AS inst_bl_template,
                        group_inst.template     AS group_inst_bl_template
              FROM      $blockGroupInstancesTable group_inst
              LEFT JOIN $blockGroupsTable bgroups ON group_inst.group_id = bgroups.id
              LEFT JOIN $blockInstancesTable inst ON inst.id = group_inst.instance_id
              LEFT JOIN $blockTypesTable btypes   ON btypes.id = inst.type_id
              LEFT JOIN $modulesTable mods        ON btypes.module_id = mods.id
              WHERE     bgroups.name = ? AND
                        inst.state > ?
              ORDER BY  group_inst.position ASC";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array($groupname,0), ResultSet::FETCHMODE_ASSOC);

    $output = '';
    while($result->next()) {
        $blockinfo = $result->getRow();

        $blockinfo['last_update'] = $blockinfo['last_update'];

        // Get the overriding template name.
        // Levels, in order (most significant first): group instance, instance, group
        $group_inst_bl_template = explode(';', $blockinfo['group_inst_bl_template'], 3);
        $inst_bl_template = explode(';', $blockinfo['inst_bl_template'], 3);
        $group_bl_template = explode(';', $blockinfo['group_bl_template'], 3);

        if (empty($group_bl_template[0])) {
            // Default the box template to the group name.
            $group_bl_template[0] = $blockinfo['group_name'];
        }

        if (empty($group_bl_template[1])) {
            // Default the block template to the instance name.
            // TODO
            $group_bl_template[1] = $blockinfo['name'];
        }

        // Cascade level over-rides for the box template.
        $blockinfo['_bl_box_template'] = !empty($group_inst_bl_template[0]) ? $group_inst_bl_template[0]
            : (!empty($inst_bl_template[0]) ? $inst_bl_template[0] : $group_bl_template[0]);

        // Global override of box template - usually comes from the 'template'
        // attribute of the xar:blockgroup tag.
        if (!empty($template)) {
            $blockinfo['_bl_box_template'] = $template;
        }

        // Cascade level over-rides for the block template.
        $blockinfo['_bl_block_template'] = !empty($group_inst_bl_template[1]) ? $group_inst_bl_template[1]
            : (!empty($inst_bl_template[1]) ? $inst_bl_template[1] : $group_bl_template[1]);

        $blockinfo['_bl_template_base'] = $blockinfo['type'];

        // Unset a few elements that clutter up the block details.
        // They are for internal use and we don't want them used within blocks.
        unset($blockinfo['group_inst_bl_template']);
        unset($blockinfo['inst_bl_template']);
        unset($blockinfo['group_bl_template']);

        $blockoutput = xarBlock_render($blockinfo);

        $output .= $blockoutput;
    }

    $result->Close();

    return $output;
}

/**
 * Renders a single block
 *
 * @author John Cox
 * @access protected
 * @param  string args[instance] id or name of block instance to render
 * @param  string args[module] module that owns the block
 * @param  string args[type] module that owns the block
 * @return string
 * @todo   this function calls a module function, keep an eye on it.
 */
function xarBlock_renderBlock($args)
{
    // All the hard work is done in this function.
    // It keeps the core code lighter when standalone blocks are not used.
    $blockinfo = xarMod::apiFunc('blocks', 'user', 'getinfo', $args);

    if (!empty($blockinfo) && $blockinfo['state'] !== 0) {
        $blockoutput = xarBlock_render($blockinfo);

    } else {
        // TODO: return NULL to indicate no block found?
        $blockoutput = '';
    }
    return $blockoutput;
}

?>
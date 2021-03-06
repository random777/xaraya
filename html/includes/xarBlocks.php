<?php
/**
 * Display Blocks
 *
 * xarBlockType functions are now in xarLegacy,
 * they can be called through blocks module api
 *
 * @package blocks
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Paul Rosania
 */


/**
 * Initialize blocks subsystem
 *
 * @author Paul Rosania
 * @access protected
 * @param  array args
 * @param  whatElseIsGoingLoaded integer
 * @return bool
 */
function xarBlock_init($args, $whatElseIsGoingLoaded)
{
    // Blocks Support Tables
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables = array(
        'block_instances'       => $systemPrefix . '_block_instances',
        'block_groups'          => $systemPrefix . '_block_groups',
        'block_group_instances' => $systemPrefix . '_block_group_instances',
        'block_types'           => $systemPrefix . '_block_types'
    );

    xarDB_importTables($tables);

    // Decide if we will be using the output caching system
    $outputCachePath = xarCoreGetVarDirPath() . '/cache/output/';
    if (defined('XARCACHE_BLOCK_IS_ENABLED')) {
        xarCore_SetCached('xarcache', 'blockCaching', true);
    } else {
        xarCore_SetCached('xarcache', 'blockCaching', false);
    }

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarBlocks__shutdown_handler');

    return true;
}

/**
 *  Shutdown handler for the blocks subsystem
 *
 * @access private
 *
 */
function xarBlocks__shutdown_handler()
{
    //xarLogMessage("xarBlocks shutdown handler");
}

/**
 * Renders a block
 *
 * @author Paul Rosania, Marco Canini <marco@xaraya.com>
 * @access protected
 * @param  array blockinfo block information parameters
 * @return string output the block to show
 * @raise  BAD_PARAM, DATABASE_ERROR, ID_NOT_EXIST, MODULE_FILE_NOT_EXIST
 * @todo   this function calls a module function, keep an eye on it
 */
function xarBlock_render($blockinfo)
{
    $modName = $blockinfo['module'];
    $blockType = $blockinfo['type'];
    $blockName = $blockinfo['name'];

    xarLogMessage('xarBlock_render: begin '.$modName.':'.$blockType.':'.$blockName);

    // This lets the security system know what module we're in
    // no need to update / select in database for each block here
    // xarModSetVar('blocks','currentmodule',$modName);
    xarCore_SetCached('Security.Variables', 'currentmodule', $modName);

    // Load the block.
    if (!xarModAPIFunc(
        'blocks', 'admin', 'load',
        array('modName' => $modName, 'blockType' => $blockType, 'blockFunc' => 'display') )
    ) {return;}

    // Get the block display function name.
    $displayFuncName = "{$modName}_{$blockType}block_display";

    // Fetch complete blockinfo array.
    if (function_exists($displayFuncName)) {
        // Allow the block to modify the content before rendering.
        // In fact, the block can access and alter any aspect of the block info.
        $blockinfo = $displayFuncName($blockinfo);

        if (!isset($blockinfo)) {
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {return;} // throw back
            return '';
        }

        // FIXME: <mrb>
        // We somehow need to be able to raise exceptions here. We can't
        //       just ignore things which are wrong.
        // This would happen if a block does not return the blockinfo array correctly.
        if (!is_array($blockinfo)) {return '';}

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
    }

    // Now wrap the block up in a box.
    // TODO: pass the group name into this function (param 2?) for the template path.
    $boxOutput = xarTpl_renderBlockBox($blockinfo, $blockinfo['_bl_box_template']);

    xarLogMessage('xarBlock_render: end '.$modName.':'.$blockType.':'.$blockName);

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
 * @raise BAD_PARAM, DATABASE_ERROR
 */
function xarBlock_renderGroup($groupname, $template = NULL)
{
    if (empty($groupname)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'groupname');
        return;
    }
    $blockCaching = xarCore_GetCached('xarcache', 'blockCaching');

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $blockGroupInstancesTable = $tables['block_group_instances'];
    $blockInstancesTable      = $tables['block_instances'];
    $blockGroupsTable         = $tables['block_groups'];
    $blockTypesTable          = $tables['block_types'];

    // Fetch details of all blocks in the group.
    $query = "SELECT    inst.xar_id as bid,
                        btypes.xar_type as type,
                        btypes.xar_module as module,
                        inst.xar_name as name,
                        inst.xar_title as title,
                        inst.xar_content as content,
                        inst.xar_last_update as last_update,
                        inst.xar_state as state,
                        group_inst.xar_position as position,
                        bgroups.xar_id              AS bgid,
                        bgroups.xar_name            AS group_name,
                        bgroups.xar_template        AS group_bl_template,
                        inst.xar_template           AS inst_bl_template,
                        group_inst.xar_template     AS group_inst_bl_template
              FROM      $blockGroupInstancesTable group_inst
              LEFT JOIN $blockGroupsTable bgroups
              ON        group_inst.xar_group_id = bgroups.xar_id
              LEFT JOIN $blockInstancesTable inst
              ON        inst.xar_id = group_inst.xar_instance_id
              LEFT JOIN $blockTypesTable btypes
              ON        btypes.xar_id = inst.xar_type_id
              WHERE     bgroups.xar_name = ?
              AND       inst.xar_state > 0
              ORDER BY  group_inst.xar_position ASC";

    $result =& $dbconn->Execute($query, array($groupname));
    if (!$result) {return;}

    $output = '';
    while(!$result->EOF) {
        $blockinfo = $result->GetRowAssoc(false);

        if ($blockCaching) {
            $cacheKey = $blockinfo['module'] . "-blockid" . $blockinfo['bid'] . "-" . $groupname;
            $args = array('cacheKey' => $cacheKey, 'name' => 'block', 'blockid' => $blockinfo['bid']);
        }

        if ($blockCaching && xarBlockIsCached($args)) {
            // output the cached block
            $output .= xarBlockGetCached($cacheKey,'block');

        } else {
            $blockinfo['last_update'] = $result->UnixTimeStamp($blockinfo['last_update']);

            // Get the overriding template name.
            // Levels, in order (most significant first): group instance, instance, group
            $group_inst_bl_template = split(';', $blockinfo['group_inst_bl_template'], 3);
            $inst_bl_template = split(';', $blockinfo['inst_bl_template'], 3);
            $group_bl_template = split(';', $blockinfo['group_bl_template'], 3);

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

            if ($blockCaching) {
                xarBlockSetCached($cacheKey, 'block', $blockoutput);
            }
            $output .= $blockoutput;

            // don't throw back exception for broken blocks
            //if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // throw back
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                $output .= xarErrorRender('template');
                // We handled the exception(s) so we can clear it
                xarErrorFree();
            }
        }

        // Next block in the group.
        $result->MoveNext();
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
 * @raise  EMPTY_PARAM
 * @todo   this function calls a module function, keep an eye on it.
 */
function xarBlock_renderBlock($args)
{
    // All the hard work is done in this function.
    // It keeps the core code lighter when standalone blocks are not used.
    $blockinfo = xarModAPIFunc('blocks', 'user', 'getinfo', $args);
    $blockCaching = xarCore_GetCached('xarcache', 'blockCaching');

    if (!empty($blockinfo) && $blockinfo['state'] <> 0) {
        if ($blockCaching) {
            $cacheKey = $blockinfo['module'] . '-blockid' . $blockinfo['bid'] . '-noGroup';
            $args = array('cacheKey' => $cacheKey,
                          'name' => 'block',
                          'blockid' => $blockinfo['bid'],
                          'blockinfo' => $blockinfo);
        }

        if ($blockCaching && xarBlockIsCached($args)) {
            // output the cached block
            $output = xarBlockGetCached($cacheKey,'block');

        } else {
            $blockoutput = xarBlock_render($blockinfo);

            if ($blockCaching) {
                xarBlockSetCached($cacheKey, 'block', $blockoutput);
            }
            $output = $blockoutput;

            // don't throw back exception for broken blocks
            //if (xarCurrentErrorType() != XAR_NO_EXCEPTION) return; // throw back
            if (xarCurrentErrorType() != XAR_NO_EXCEPTION) {
                $output .= xarErrorRender('template');
                // We handled the exception(s) so we can clear it
                xarErrorFree();
            }
        }
    } else {
        // TODO: return NULL to indicate no block found?
        $output = '';
    }

    return $output;
}

?>

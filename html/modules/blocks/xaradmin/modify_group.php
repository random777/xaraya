<?php
/**
 * Modify Block group
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
 * modify block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_modify_group()
{
    if (!xarVarFetch('gid', 'int:1:', $gid)) {return;}

    // Security Check
    if(!xarSecurityCheck('EditBlock', 0, 'Group')) {return;}

    // Get details on current group
    $group = xarModAPIFunc(
        'blocks', 'user', 'groupgetinfo',
        array('gid' => $gid)
    );

    $up_arrow_src   = xarTplGetImage('icons/go-up.png','base');
    $down_arrow_src = xarTplGetImage('icons/go-down.png','base');

    return array(
        'group'            => $group,
        'instance_count'   => count($group['instances']),
        'up_arrow_src'     => $up_arrow_src,
        'down_arrow_src'   => $down_arrow_src,
        'authid'           => xarSecGenAuthKey(),
        'moveuplabel'      => xarML('Move selected instance up'),
        'movedownlabel'    => xarML('Move selected instance down'),
        'updatelabel'      => xarML('Update')
    );
}

?>

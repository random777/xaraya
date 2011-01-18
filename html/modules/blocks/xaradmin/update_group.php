<?php
/**
 * Update a block group
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
 * update a block group
 * @author Jim McDonald, Paul Rosania
 */
function blocks_admin_update_group()
{
    // Get parameters
    if (!xarVarFetch('gid', 'int:1:', $gid)) {return;}
    if (!xarVarFetch('authid', 'str:1:', $authid)) {return;}
    if (!xarVarFetch('group_instance_order', 'strlist:;:id', $group_instance_order, '', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('group_name', 'pre:lower:ftoken:field:Group Name:passthru:str:1:', $name)) {return;}
    if (!xarVarFetch('group_template', 'pre:trim:lower:ftoken', $template, '', XARVAR_NOT_REQUIRED)) {return;}

    if (!xarVarFetch('moveinst', 'int:1:', $moveinst, NULL, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('direction', 'pre:trim:lower', $direction, NULL, XARVAR_NOT_REQUIRED)) return;
    // Confirm Auth Key
    if (!xarSecConfirmAuthKey()) {return;}

    // Security Check
    if(!xarSecurityCheck('EditBlock', 0, 'Instance')) {return;}

    // Explode the instance order from id1;id2;etc to an array
    if (!empty($group_instance_order)) {
        $group_instance_order = explode(';', $group_instance_order);
    } else {
        $group_instance_order = array();
    }

    // Get the current group.
    $currentgroup = xarMod::apiFunc('blocks', 'user', 'groupgetinfo', array('gid' => $gid));
    if (empty($currentgroup)) {return;}

    // If the name is being changed, then check the new name has not already been used.
    if ($currentgroup['name'] != $name) {
        $checkname = xarMod::apiFunc('blocks', 'user', 'groupgetinfo', array('name' => $name));
        if (!empty($checkname)) {
            $msg = xarML('Block group name "#(1)" already exists', $name);
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return;
        }
    }
    $seeninst = array();
    if (!empty($currentgroup['instances'])) {
        $i = 0;
        foreach ($currentgroup['instances'] as $inst) {
            if ($moveinst == $inst['id']) $currentpos = $i;
            $seeninst[] = $inst['id'];
            $i++;
        }
    }

    if (!empty($seeninst) && !empty($moveinst) && in_array($moveinst, $seeninst) && !empty($direction)) {
        $i = 0;
        foreach ($currentgroup['instances'] as $inst) {
            if ($i == $currentpos && $direction == 'up' && isset($seeninst[$i-1])) {
                $temp = $seeninst[$i-1];
                $seeninst[$i-1] = $inst['id'];
                $seeninst[$i] = $temp;
                break;
            } elseif ($i == $currentpos && $direction == 'down' && isset($seeninst[$i+1])) {
                $temp = $seeninst[$i+1];
                $seeninst[$i+1] = $inst['id'];
                $seeninst[$i] = $temp;
                break;
            }
            $i++;
        }
        $group_instance_order = $seeninst;
   }

    // Pass to API
    if (!xarMod::apiFunc(
        'blocks', 'admin', 'update_group',
        array(
            'id' => $gid,
            'template' => $template,
            'name' => $name,
            'instance_order' => $group_instance_order)
        )
    ) {return;}

    xarResponse::redirect(xarModURL('blocks', 'admin', 'modify_group', array('gid' => $gid)));

    return true;
}

?>

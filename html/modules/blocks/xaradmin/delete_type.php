<?php
/**
 * Delete a block type and all its instances
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Blocks module
 * @link http://xaraya.com/index.php/release/13.html
 */
/**
 * Register Delete Block Type
 * @author Chris Powis
 * @param tid integer id of the block type to delete
 * @throws bad param
 * @return mixed array of data for form, bool true on successful delete, false on fail
 */
function blocks_admin_delete_type()
{
    // Security Check
    if (!xarSecurityCheck('DeleteBlock')) return;

    // Get parameters
    if (!xarVarFetch('tid', 'id', $tid, 0, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirm', 'checkbox', $confirm, false, XARVAR_NOT_REQUIRED)) return;

    // check we got a valid blocktype
    if (empty($tid)) {
        $msg = xarML('Missing #(1) for #(2) function #(3) in module #(4)', 'tid', 'admin', 'delete_type', 'Blocks');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    $data = array();
    // get information about the blocktype
    $blocktype = xarModAPIFunc('blocks', 'user', 'getblocktype', array('tid' => $tid));

    // check we got a valid blocktype
    if (empty($blocktype)) {
        $msg = xarML('Unkown Block Type #(1) for #(2) function #(3) in module #(4)', $tid, 'admin', 'delete_type', 'Blocks');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    // get all instances of this block type
    $instances = array();
    $allinst = xarModAPIFunc('blocks', 'user', 'getall');
    if (!empty($allinst)) {
        foreach ($allinst as $instance) {
            if ($instance['tid'] != $tid) continue;
            $instances[] = $instance;
        }
    }

    if ($confirm) {
        if (!xarSecConfirmAuthKey()) return;
        // remove block type and its instances
        if (!xarModAPIFunc('blocks',
                           'admin',
                           'unregister_block_type',
                           array('modName'  => $blocktype['module'],
                                 'blockType'=> $blocktype['type']))) return;
        return xarResponseRedirect(xarModURL('blocks', 'admin', 'view_types'));
    }

    $data['tid'] = $tid;
    $data['blocktype'] = $blocktype;
    $data['instances'] = $instances;
    $data['instcount'] = count($instances);
    $data['authid'] = xarSecGenAuthKey('blocks');

    return $data;

}

?>
<?php

function mail_admin_delete($args = array())
{
    // Are we legitimally here?
    if(!xarSecConfirmAuthKey()) return;
    // Security check
    if (!xarSecurityCheck('AdminMail')) return; 
    // Required parameters
    if(!xarVarFetch('itemid','id',$itemid)) return;
    if(!xarVarFetch('objectid','id',$objectid)) return;

    $qdefObject = DataObjectMaster::getObject(array('objectid' => $objectid));
    if(!$qdefObject) return;

    $result = $qdefObject->deleteItem(array('itemid' => $itemid));
    if(!$result) return;

    return xarResponse::Redirect(xarModUrl('mail','admin','view'));
}
?>

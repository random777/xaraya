<?php
function roles_eventapi_OnUserLogin($id)
{
    $lastLogin = xarModUserVars::get('roles', 'userlastlogin', $id);
    if (!empty($lastLogin)) {
        xarSession::setVar('roles_thislastlogin', $lastLogin);
    }
    xarModUserVars::set('roles', 'userlastlogin', time(), $id);
    return $id;
}
?>
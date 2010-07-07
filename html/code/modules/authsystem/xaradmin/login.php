<?php
function authsystem_admin_login($args = array())
{
    extract($args);
    xarVarFetch('redirecturl', 'str:1:254', $data['redirecturl'], xarServer::getBaseURL(), XARVAR_NOT_REQUIRED);

    if (!xarUserIsLoggedIn()) {
        // @TODO: add some security here,
        // eg, ip whitelist, enable only when site lock is in force, disable completely
        return $data;
    } else {
        xarController::redirect($data['redirecturl']);
        return true;
    }
}
?>
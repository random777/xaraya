<?php
/**
 * Initiate a remote authentication request/reponse transaction
 * Authentication modules should use this function if they require external authentication
 * They should supply a remote() method to handle the request, and point to a login url of
 * xarModURL('authsystem', 'user', 'remote', array('authmodule' => 'myauthmodule'));
**/
function authsystem_user_remote()
{
   if (!xarVarFetch('authmodule', 'str:1:', $authmodule, '', XARVAR_NOT_REQUIRED)) return;
   if (empty($authmodule))
       throw new EmptyParameterException('authmodule');
   sys::import('modules.authsystem.class.xarauth');
   $authobj = xarAuth::getAuthObject($authmodule);
   if (!$authobj) throw new EmptyParameterException('authmodule');
   // call the remote method if the auth module object, this should
   // redirect to the remote site, or return a url to redirect to
   $request = $authobj->remote();
   // throw an exception if the request returns false
   if (!$request)
       xarException($authmodule,'Unable to authenticate remotely using #(1) authentication module');
   // the authenticating module should supply a url to redirect to, if it didn't already redirect
   if (!empty($request['return_url']))
       $return_url = $request['return_url'];
   if (empty($return_url))
       $return_url = xarServer::getBaseURL();
   xarController::redirect($return_url);
}
?>
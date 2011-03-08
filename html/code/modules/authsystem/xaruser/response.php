<?php
/**
 * Listen for a response from a remote authentication request
 * Authentication modules should return to this function if they request credentials remotely
 * Navigate to xarModURL('authsystem', 'user', 'response', array('authmodule' => 'myauthmodule'));
**/
function authsystem_user_response()
{
   if (!xarVarFetch('authmodule', 'str:1:', $authmodule, '', XARVAR_NOT_REQUIRED)) return;
   if (empty($authmodule))
       throw new EmptyParameterException('authmodule');
   sys::import('modules.authsystem.class.xarauth');
   $authobj = xarAuth::getAuthObject($authmodule);
   if (!$authobj) throw new EmptyParameterException('authmodule');
   // call the response method of the authenticating module, this should
   // handle the response from the remote site and return one of the following...
   // 1) a xaraya username and password to pass to the login function, or...
   // 2) a return_url to redirect to, or...
   // 3) invalid response, or...
   // 4) false
   $response = $authobj->response();
   // throw an exception if the response returns false
   if (!$response)
       xarException($authmodule,'Unable to authenticate remotely using #(1) authentication module');
   // return invalid message to authsystem template
   if (!empty($response['invalid']))
       return xarTplModule('authsystem', 'user', 'errors', $response);
   // pass to login function if we have a username and password
   if (!empty($response['uname']) && !empty($response['pass']))
       return xarMod::guiFunc('authsystem', 'user', 'login', $response);
   // the authenticating module could return a url to redirect to, if it didn't already redirect
   if (!empty($response['return_url']))
       $return_url = $response['return_url'];
   if (empty($return_url))
       $return_url = xarServer::getBaseURL();
   xarController::redirect($return_url);
}
?>
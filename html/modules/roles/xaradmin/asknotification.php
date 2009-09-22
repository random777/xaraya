<?php
/**
 * Update users from roles_admin_showusers
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/*
 * Update users from roles_admin_showusers
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
function roles_admin_asknotification($args)
{
    // Security Check
    if (!xarSecurityCheck('EditRole')) return;
    sys::import('modules.roles.class.roles');
    // Get parameters
    if (!xarVarFetch('phase',    'str:0:', $data['phase'],    'display', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('mailtype', 'str:0:', $data['mailtype'], 'blank', XARVAR_NOT_REQUIRED)) return;
    if(!xarVarFetch('id',       'isset',  $id,              NULL,    XARVAR_NOT_REQUIRED)) return;
    //Maybe some kind of return url will make this function available for other modules
    if (!xarVarFetch('state',    'int:0:', $data['state'],  xarRoles::ROLES_STATE_CURRENT, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('groupid',  'int:0:', $data['groupid'], 0,    XARVAR_NOT_REQUIRED)) return;
    //optional value
    if (!xarVarFetch('pass',     'str:0:', $data['pass'],     NULL, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('ip',       'str:0:', $data['ip'],       NULL, XARVAR_NOT_REQUIRED)) return;
    switch ($data['phase']) {
        case 'display' :
                $data['pass'] = xarSession::getVar('tmppass');
                xarSessionDelVar('tmppass');
                if ($data['mailtype'] == 'blank') {
                    $data['subject'] = '';
                    $data['message'] = '';
                } else {
                    $strings = xarModAPIFunc('roles','admin','getmessagestrings', array('template' => $data['mailtype']));
                    if (!isset($strings)) return;

                    $data['subject'] = $strings['subject'];
                    $data['message'] = $strings['message'];
                }
                //Display the notification form
                if (!xarVarFetch('subject', 'str:1:', $data['subject'], $data['subject'], XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('message', 'str:1:', $data['message'], $data['message'], XARVAR_NOT_REQUIRED)) return;
                $data['authid'] = xarSecGenAuthKey();
                $data['id'] = base64_encode(serialize($id));

                // dynamic properties (if any)
                $data['properties'] = null;
                if (xarModIsAvailable('dynamicdata')) {
                    // get the DataObject defined for this module (and itemtype, if relevant)
                    // Bug 4785: removed a & on next line
                    $object = xarModAPIFunc('dynamicdata', 'user', 'getobject',
                        array('module' => 'roles'));
                    if (isset($object) && !empty($object->objectid)) {
                        // get the Dynamic Properties of this object
                        $data['properties'] = &$object->getProperties();
                    }
                }
                return $data;
            break;
        case 'notify' :
            // Confirm authorisation code
            if (!xarSecConfirmAuthKey()) {
                return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
            }        
            if (!xarVarFetch('subject', 'str:1:', $data['subject'], NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('message', 'str:1:', $data['message'], NULL, XARVAR_NOT_REQUIRED)) return;

            // Need to convert %%var%% to #$var# so that we can compile the template
            $data['message'] = preg_replace( "/%%(.+)%%/","#$\\1#", $data['message'] );
            $data['subject'] = preg_replace( "/%%(.+)%%/","#$\\1#", $data['subject'] );

            // Preserve whitespace by encoding to html (block layout compiler seems to eat whitespace for lunch)
            $data['message'] = nl2br($data['message']);
            $data['message'] = str_replace(" ","&#160;", $data['message']);

            // Get System/Site vars
            $vars  = xarModAPIFunc('roles','admin','getmessageincludestring', array('template' => 'message-vars'));

            // Compile Template before sending it to senduseremail()
            $data['message'] = xarTplCompileString($vars . $data['message']);
            $data['subject'] = xarTplCompileString($vars . $data['subject']);

            // Restore whitespace
            $data['message'] = str_replace('&#160;',' ', $data['message']);
            $data['message'] = str_replace('<br />',' ', $data['message']);

            //Send notification
            $id = unserialize(base64_decode($id));
            if (!xarModAPIFunc('roles','admin','senduseremail', array( 'id' => $id, 'mailtype' => $data['mailtype'], 'subject' => $data['subject'], 'message' => $data['message'], 'pass' => $data['pass'], 'ip' => $data['ip']))) {
                return;
            }
            xarResponse::Redirect(xarModURL('roles', 'admin', 'showusers',
                              array('id' => $data['groupid'], 'state' => $data['state'])));
           break;
    }
}
?>
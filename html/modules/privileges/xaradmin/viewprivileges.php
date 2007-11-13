<?php
/**
 * View the current privileges
 *
 * @package core modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * viewPrivileges - view the current privileges
 * Takes no parameters
 * @return array
 */
function privileges_admin_viewprivileges()
{
    // Security Check
    if(!xarSecurityCheck('EditPrivilege')) return;

    $data = array();

    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

    // call the Privileges class
    $privs = new xarPrivileges();

    //Load Template
    include_once 'modules/privileges/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

    $data['authid'] = xarSecGenAuthKey();
    $data['trees'] = $renderer->drawtrees($data['show']);
    $data['refreshlabel'] = xarML('Refresh');

    // Set page name
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('View Privileges')));
    return $data;
}
?>
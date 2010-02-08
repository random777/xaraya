<?php
/**
 * @package core modules
 * @copyright see the html/credits.html file in this release
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
 */
function privileges_admin_viewprivileges()
{
    // Security Check
    if(!xarSecurityCheck('EditPrivilege')) return;

    $data = array();

    if (!xarVarFetch('show', 'isset', $data['show'], 'assigned', XARVAR_NOT_REQUIRED)) return;

    // Clear Session Vars
    xarSessionDelVar('privileges_statusmsg');

    $data['authid'] = xarSecGenAuthKey();
    $data['refreshlabel'] = xarML('Refresh');
    return $data;
}


?>

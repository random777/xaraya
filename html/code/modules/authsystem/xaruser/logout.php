<?php
/**
 * Log a user out from the system
 *
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
/**
 * log user out of system
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * @return boolean true on success of redirect
 */
sys::import('modules.authsystem.class.auth');
function authsystem_user_logout()
{
    if (xarSecurityCheck('AdminBase',0)) {
        if (!xarVarFetch('confirm', 'checkbox',
            $confirmed, false, XARVAR_NOT_REQUIRED)) return;
        if (!$confirmed) {
            return xarTplModule('authsystem', 'admin', 'logout');
        }
    }        
        
    if (!xarVarFetch('return_url', 'pre:trim:str:1:254',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;
    
    if (empty($return_url) && xarController::isLocalReferer())
        $return_url = xarServer::getVar('HTTP_REFERER');    
    
    if (empty($return_url) ||
        preg_match('!authsystem!', $return_url) || 
        preg_match('!admin!', $return_url))
        $return_url = xarServer::getBaseURL();

    if (!xarAuth::userLogout())
        // @checkme: forbidden operation? 
        throw new ForbiddenOperationException(array('authsystem', 'logout'),
            xarML('There was a problem Logging Out.  Module #(1) Function #(2)'));
    
    xarController::redirect($return_url);  
}
?>

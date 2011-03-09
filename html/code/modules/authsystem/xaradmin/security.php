<?php
/**
 * @package modules
 * @subpackage authsystem module
 * @category Xaraya Web Applications Framework
 * @version 2.2.0
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @link http://xaraya.com/index.php/release/42.html
 */
function authsystem_admin_security(Array $args=array())
{
    // Security
    if (!xarSecurityCheck('AdminAuthsystem')) return;
    extract($args);

    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update', 
        $phase, 'form', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'pre:trim:str:1:',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;   

    $data = array();
    $invalid = array();
    
    if ($phase == 'update') {
        if (!xarSecConfirmAuthKey())
            return xarTplModule('privileges', 'user', 'errors', 
                array('layout' => 'bad_author'));

        if (!xarVarFetch('login_attempts', 'int:1:',
            $login_attempts, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('login_lockedout', 'int:1:',
            $login_lockedout, 15, XARVAR_NOT_REQUIRED)) return;
        xarModVars::set('authsystem', 'login.attempts', $login_attempts);
        xarModVars::set('authsystem', 'login.lockedout', $login_lockedout);
        
        if (empty($return_url))
            $return_url = xarModURL('authsystem', 'admin', 'security');
        xarController::redirect($return_url);    
    }
    
    $data['login_attempts'] = xarModVars::get('authsystem', 'login.attempts');
    $data['login_lockedout'] = xarModVars::get('authsystem', 'login.lockedout');       
    $data['return_url'] = $return_url;
    $data['invalid'] = $invalid;
    
    return $data;
}
?>
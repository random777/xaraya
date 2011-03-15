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
sys::import('modules.authsystem.class.authsystem');
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

    $security = AuthSystem::$security;   
    
    if ($phase == 'update') {
        if (!xarSecConfirmAuthKey())
            return xarTplModule('privileges', 'user', 'errors', 
                array('layout' => 'bad_author'));
        if (!xarVarFetch('security_login_state', 'int:1:3',
            $login_state, AuthSystem::STATE_LOGIN_USER, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('security_login_alias', 'pre:trim:lower:str:1:254',
            $login_alias, '', XARVAR_NOT_REQUIRED)) return;
        $security->login_state = $login_state;
        $security->login_alias = $login_alias;

        if (!xarVarFetch('security_login_attempts', 'int:1:',
            $login_attempts, 0, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('security_lockout_period', 'int:1:',
            $lockout_period, 15, XARVAR_NOT_REQUIRED)) return;
        $security->login_attempts = $login_attempts;
        $security->lockout_period = $lockout_period;
        if (!xarVarFetch('security_log_attempts', 'checkbox',
            $log_attempts, false, XARVAR_NOT_REQUIRED)) return;
        $security->log_attempts = $log_attempts;
        if (!xarVarFetch('security_lockout_notify', 'checkbox',
            $lockout_notify, false, XARVAR_NOT_REQUIRED)) return;
        $security->lockout_notify = $lockout_notify;        
        if (empty($return_url))
            $return_url = xarModURL('authsystem', 'admin', 'security');
        xarController::redirect($return_url);    
    }
    
    $data['security'] = $security->getInfo();
    $data['auth_states'] = AuthSystem::getLoginStates();    
    
    $data['login_attempts'] = xarModVars::get('authsystem', 'login.attempts');
    $data['login_lockedout'] = xarModVars::get('authsystem', 'login.lockedout');       
    $data['return_url'] = $return_url;
    $data['invalid'] = $invalid;
    
    return $data;
}
?>
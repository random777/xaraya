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
/**
 * Modify Authentication Module Configuration
**/
sys::import('modules.authsystem.class.authsystem');
function authsystem_admin_modify(Array $args=array())
{
    // Security
    if (!xarSecurityCheck('AdminAuthsystem')) return;
    extract($args);

    if (!xarVarFetch('authmod', 'pre:trim:lower:str:1:',
        $authmod, null, XARVAR_DONT_SET)) return;
    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update', 
        $phase, 'form', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'pre:trim:str:1:',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;

    $auth = AuthSystem::getAuthSubject('AuthLogin');
    $auth_mods = $auth->getAuthModules();
    $form = AuthSystem::getAuthSubject('AuthLoginForm');
    $form_mods = $form->getAuthModules();
    
    $auth_capable = isset($auth_mods[$authmod]);   
    $form_capable = isset($form_mods[$authmod]);

    if (!$auth_capable && !$form_capable)
        throw new BadParameterException(array('authmod'), 'Invalid #(1) for authsystem_admin_modify()');        
    
    $data = array();
    $invalid = array();
    
    if ($phase == 'update') {
        if (!xarSecConfirmAuthKey())
            return xarTplModule('privileges', 'user', 'errors', array('layout' => 'bad_author'));
        
        $authvalid = $auth_capable ? $auth->checkconfig($authmod) : true;
        $formvalid = $form_capable ? $form->checkconfig($authmod) : true; 
        
        if ($authvalid && $formvalid) {
            if ($auth_capable) {
                $auth->updateconfig($authmod);
                if (!xarVarFetch('auth_active', 'checkbox',
                    $auth_active, false, XARVAR_NOT_REQUIRED)) return;
                AuthSystem::$config->auth_active[$authmod] = $auth_active;
            }
            if ($form_capable) {
                $form->updateconfig($authmod);
                if (!xarVarFetch('form_active', 'checkbox',
                    $form_active, false, XARVAR_NOT_REQUIRED)) return;
                AuthSystem::$config->form_active[$authmod] = $form_active;
            }
        }
        
        if (empty($invalid)) {
            if (empty($return_url))
                $return_url = xarModURL('authsystem', 'admin', 'modify', 
                    array('authmod' => $authmod, 'tab' => $tab));
            xarController::redirect($return_url);        
        }
    }
    
    $data['authmod'] = $authmod;
    $data['auth_config'] = $auth_capable ? $auth->modifyconfig($authmod) : '';
    $data['form_config'] = $form_capable ? $form->modifyconfig($authmod) : '';
    $data['auth_capable'] = $auth_capable;
    $data['form_capable'] = $form_capable;
    $data['auth_active'] = !empty(AuthSystem::$config->auth_active[$authmod]);
    $data['form_active'] = !empty(AuthSystem::$config->form_active[$authmod]);  

    return $data;
}
?>
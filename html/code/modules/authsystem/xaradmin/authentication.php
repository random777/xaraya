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
 * Modify Authentication Configuration
**/
sys::import('modules.authsystem.class.authsystem');
function authsystem_admin_authentication(Array $args=array())
{
    // Security
    if (!xarSecurityCheck('AdminAuthsystem')) return;
    extract($args);

    if (!xarVarFetch('tab', 'pre:trim:lower:str:1:',
        $tab, null, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update:reorder', 
        $phase, 'form', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'pre:trim:str:1:',
        $return_url, '', XARVAR_NOT_REQUIRED)) return;

    switch ($tab) {
        default:
            $login = AuthSystem::getAuthSubject('AuthLogin');
            $auth_mods = $login->getAuthModules();  
        break;
        case 'form':
            $loginform = AuthSystem::getAuthSubject('AuthLoginForm');
            $auth_mods = $loginform->getAuthModules();
        break;
    }            
    
    $data = array();
    $invalid = array();
    
    if ($phase != 'form') {
        if (!xarSecConfirmAuthKey())
            return xarTplModule('privileges', 'user', 'errors', array('layout' => 'bad_author'));
        
        switch ($phase) {
            
            case 'update':
            default:        

                switch ($tab) {
                    
                    default:
                        if (!xarVarFetch('modlist', 'array',
                            $modlist, array(), XARVAR_NOT_REQUIRED)) return;
                        if (empty($modlist))
                            $invalid['update'] = xarML('There was a problem updating the list');
                        // synch the states based on input 
                        if (empty($invalid)) {
                            foreach ($auth_mods as $modname => $auth_mod) {
                                AuthSystem::$config->auth_active[$modname] = isset($modlist[$modname]) && !empty($modlist[$modname]['is_active']);
                            }
                        }
                        
                    break;

                    case 'form':
                        if (!xarVarFetch('modlist', 'array',
                            $modlist, array(), XARVAR_NOT_REQUIRED)) return;
                        if (empty($modlist))
                            $invalid['update'] = xarML('There was a problem updating the list');
                        // synch the states based on input 
                        if (empty($invalid)) {
                            foreach ($auth_mods as $modname => $auth_mod) {
                                AuthSystem::$config->form_active[$modname] = isset($modlist[$modname]) && !empty($modlist[$modname]['is_displayed']);
                            }
                        }
                    break; 

                }                    
            
            break;
            
            case 'reorder':        
            
                if (!xarVarFetch('authmod', 'pre:trim:str:1:',
                    $authmod, null, XARVAR_NOT_REQUIRED)) return;
                if (!xarVarFetch('direction', 'pre:trim:lower:enum:up:down',
                    $direction, null, XARVAR_NOT_REQUIRED)) return;
                if (!isset($authmod) || 
                    !isset($auth_mods[$authmod]) ||
                    !isset($direction)) 
                    $invalid['reorder'] = xarML('Invalid parameters for list re-order');
                if (empty($invalid)) {
                    $listkey = !empty($tab) ? $tab.'_order' : 'auth_order';
                    if (!AuthSystem::$config->reorder($authmod, $direction, $listkey))
                        $invalid['reorder'] = xarML('There was a problem re-ordering the list');
                }           
            break;
        }
        if (empty($invalid)) {
            if (empty($return_url)) 
                $return_url = xarModURL('authsystem', 'admin', 'authentication',
                    array('tab' => $tab));
            xarController::redirect($return_url);
        }
    }

    $data['tab'] = $tab;
    $data['invalid'] = $invalid;
    $data['authid'] = xarSecGenAuthKey();
    $data['auth_mods'] = $auth_mods;
    
    return $data;
}
?>
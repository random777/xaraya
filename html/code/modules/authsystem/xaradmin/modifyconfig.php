<?php
/**
 * Modify the configuration settings of this module
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
 * Modify the configuration settings of this module
 *
 * Standard GUI function to display and update the configuration settings of the module based on input data.
 * 
 * @return mixed data array for the template display or output display string if invalid data submitted
 */
sys::import('modules.authsystem.class.authsystem');
function authsystem_admin_modifyconfig()
{
    // Security
    if (!xarSecurityCheck('AdminAuthsystem')) return;
    
    if (!xarVarFetch('phase', 'pre:trim:lower:enum:update', 
        $phase, 'modify', XARVAR_NOT_REQUIRED, XARVAR_PREP_FOR_DISPLAY)) return;

    $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',
        array('module' => 'authsystem'));
    $data['module_settings']->setFieldList('items_per_page, use_module_alias, module_alias_name, enable_short_urls');
    $data['module_settings']->getItem();

    if ($phase == 'update') {
        // Confirm authorisation code
        if (!xarSecConfirmAuthKey())
            return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
        
        $isvalid = $data['module_settings']->checkInput();
        if ($isvalid) {
            if (!xarVarFetch('exception_redirect', 'checkbox',
                $exception_redirect, false, XARVAR_NOT_REQUIRED)) return;
            xarModVars::set('privileges', 'exceptionredirect', $exception_redirect);
            $data['module_settings']->updateItem();
            if (empty($return_url))
                $return_url = xarModURL('authsystem', 'admin', 'modifyconfig');
            xarController::redirect($return_url);
        }
    }
    
    $data['security'] = AuthSystem::$security->getInfo();
    
    return $data;
}
?>

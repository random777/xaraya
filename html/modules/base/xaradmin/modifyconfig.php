<?php
/**
 * Modify site configuration 
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 */
/**
 * Modify site configuration
 * @author John Robeson
 * @author Greg Allan
 * @return array of template values
 */
function base_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'display', XARVAR_NOT_REQUIRED)) return;

    if (xarConfigGetVar('Site.Core.DefaultModuleType') == 'admin'){
    // Get list of user capable mods
        $data['mods'] = xarModAPIFunc('modules',
                          'admin',
                          'getlist',
                          array('filter'     => array('AdminCapable' => 1)));
    } else {
        $data['mods'] = xarModAPIFunc('modules',
                          'admin',
                          'getlist',
                          array('filter'     => array('UserCapable' => 1)));
    }

    $localehome = xarCoreGetVarDirPath() . "/locales";
    if (!file_exists($localehome)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'LOCALE_NOT_AVAILABLE', new SystemException('The locale directory was not found.'));
    }
    $dd = opendir($localehome);
    $locales = array();
    while ($filename = readdir($dd)) {
            if (is_dir($localehome . "/" . $filename) && file_exists($localehome . "/" . $filename . "/locale.xml")) {
                $locales[] = $filename;
            }
    }
    closedir($dd);

    $timezone = xarConfigGetVar('Site.Core.TimeZone');
    if (!isset($timezone) || substr($timezone,0,2) == 'US') {
        xarConfigSetVar('Site.Core.TimeZone', '');
    }

    $data['editor'] = xarModGetVar('base','editor');
    $data['editors'] = array(array('displayname' => xarML('none')));
    if(xarModIsAvailable('htmlarea')) $data['editors'][] = array('displayname' => 'htmlarea');
    if(xarModIsAvailable('fckeditor')) $data['editors'][] = array('displayname' => 'fckeditor');
    if(xarModIsAvailable('tinymce')) $data['editors'][] = array('displayname' => 'tinymce');    
    $allowedlocales = xarConfigGetVar('Site.MLS.AllowedLocales');
    foreach($locales as $locale) {
        if (in_array($locale, $allowedlocales)) $active = true;
        else $active = false;
        $data['locales'][] = array('name' => $locale, 'active' => $active);
    }
    // TODO: delete after new backend testing
    // $data['translationsBackend'] = xarConfigGetVar('Site.MLS.TranslationsBackend');
    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');
    $data['XARCORE_VERSION_NUM'] = XARCORE_VERSION_NUM;
    $data['XARCORE_VERSION_ID'] =  XARCORE_VERSION_ID;
    $data['XARCORE_VERSION_SUB'] = XARCORE_VERSION_SUB;
    return $data;
}

?>

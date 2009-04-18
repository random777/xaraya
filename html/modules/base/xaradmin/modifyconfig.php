<?php
/**
 * Modify site configuration
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
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

    $localehome = sys::varpath() . "/locales";
    if (!file_exists($localehome)) {
        throw new DirectoryNotFoundException($localehome);
    }
    $dd = opendir($localehome);
    $locales = array();
    while ($filename = readdir($dd)) {
            if (is_dir($localehome . "/" . $filename) && file_exists($localehome . "/" . $filename . "/locale.xml")) {
                $locales[] = $filename;
            }
    }
    closedir($dd);

    sys::import('xaraya.structures.datetime');
    $dateobject = new XarDateTime();
    $tzobject = new DateTimeZone(xarConfigVars::get(null, 'System.Core.TimeZone'));
    $dateobject->setTimezone($tzobject);
    $data['hostnow'] = $dateobject->format("r");

    $tzobject = new DateTimeZone(xarModUserVars::get('roles','usertimezone'));
    $dateobject->setTimezone($tzobject);
    $data['localnow'] = $dateobject->format("r");

    $data['editor'] = xarModVars::get('base','editor');
    $data['editors'] = array(array('displayname' => xarML('none')));
    if(xarModIsAvailable('htmlarea')) $data['editors'][] = array('displayname' => 'htmlarea');
    if(xarModIsAvailable('fckeditor')) $data['editors'][] = array('displayname' => 'fckeditor');
    if(xarModIsAvailable('tinymce')) $data['editors'][] = array('displayname' => 'tinymce');
    $allowedlocales = xarConfigVars::get(null, 'Site.MLS.AllowedLocales');
    foreach($locales as $locale) {
        if (in_array($locale, $allowedlocales)) $active = true;
        else $active = false;
        $data['locales'][] = array('name' => $locale, 'active' => $active);
    }
    $releasenumber=xarModVars::get('base','releasenumber');
    $data['releasenumber']=isset($releasenumber) ? $releasenumber:10;

    // TODO: delete after new backend testing
    // $data['translationsBackend'] = xarConfigVars::get(null, 'Site.MLS.TranslationsBackend');
    $data['authid'] = xarSecGenAuthKey();
    $data['updatelabel'] = xarML('Update Base Configuration');
    $data['XARCORE_VERSION_NUM'] = xarCore::VERSION_NUM;
    $data['XARCORE_VERSION_ID'] =  xarCore::VERSION_ID;
    $data['XARCORE_VERSION_SUB'] = xarCore::VERSION_SUB;
    return $data;
}

?>

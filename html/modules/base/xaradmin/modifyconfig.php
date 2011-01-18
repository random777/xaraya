<?php
/**
 * Modify site configuration
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
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

 * @param string tab        Part of the config to update
 * @param string returnurl  optional
 * @return array of template values
 */
function base_admin_modifyconfig()
{
    // Security Check
    if(!xarSecurityCheck('AdminBase')) return;

    $data = array();
    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'display', XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('returnurl', 'str:1:100', $data['returnurl'], Null, XARVAR_NOT_REQUIRED)) return;

    if (xarConfigGetVar('Site.Core.DefaultModuleType') == 'admin'){
    // Get list of user capable mods
        $data['mods'] = xarMod::apiFunc('modules',
                          'admin',
                          'getlist',
                          array('filter'     => array('AdminCapable' => 1)));
    } else {
        $data['mods'] = xarMod::apiFunc('modules',
                          'admin',
                          'getlist',
                          array('filter'     => array('UserCapable' => 1)));
    }
    $defaultModuleName = xarConfigGetVar('Site.Core.DefaultModuleName');
    $data['defaultModuleName'] = $defaultModuleName;
    $data['defaultModuleMissing']  = true;
    foreach ($data['mods'] as $module) {
        if ($module['name'] == $defaultModuleName) {
            $data['defaultModuleMissing'] = false;
        }
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
    if(xarMod::isAvailable('htmlarea')) $data['editors'][] = array('displayname' => 'htmlarea');
    if(xarMod::isAvailable('fckeditor')) $data['editors'][] = array('displayname' => 'fckeditor');
    if(xarMod::isAvailable('tinymce')) $data['editors'][] = array('displayname' => 'tinymce');
    $allowedlocales = xarConfigGetVar('Site.MLS.AllowedLocales');
    foreach($locales as $locale) {
        if (in_array($locale, $allowedlocales)) $active = true;
        else $active = false;
        $data['locales'][] = array('name' => $locale, 'active' => $active);
    }

    // Javascript tab
    if ($data['tab'] == 'javascript') {
        if (!xarVarFetch('importplugins', 'checkbox', $importplugins, false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('framework', 'pre:trim:lower:str:1', $fwname, NULL, XARVAR_DONT_SET)) return;
        if ($importplugins) {
            xarMod::apiFunc('base', 'javascript', 'importplugins', array('framework' => $fwname));
        }
        $frameworks = xarMod::apiFunc('base','javascript','getframeworkinfo');
        if (!empty($fwname) && isset($frameworks[$fwname])) {
            $data['fwinfo'] = $frameworks[$fwname];
            if (!isset($data['fwinfo']['plugins'])) {
                $data['fwinfo']['plugins'] = array();
            } else {
                ksort($data['fwinfo']['plugins']);
            }
            if (!xarVarFetch('fwinfo', 'array', $fwinfo, array(), XARVAR_NOT_REQUIRED)) return;
            if (!empty($fwinfo)) {
                if (!xarSecConfirmAuthKey()) return;
                if (isset($fwinfo['plugins'])) {
                    foreach($fwinfo['plugins'] as $plName => $plVals) {
                        if (!empty($plVals['defaultmod'])) {
                            if (isset($data['fwinfo']['plugins'][$plName]['modules'][$plVals['defaultmod']])) {
                                $data['fwinfo']['plugins'][$plName]['defaultmod'] = $plVals['defaultmod'];
                            }
                        }
                        if (!empty($plVals['modules'])) {
                            foreach ($plVals['modules'] as $modName => $modVals) {
                                if (!empty($modVals['defaultfile'])) {
                                    if (isset($data['fwinfo']['plugins'][$plName]['modules'][$modName])) {
                                        $data['fwinfo']['plugins'][$plName]['modules'][$modName]['defaultfile'] = $modVals['defaultfile'];
                                    }
                                }
                            }
                        }
                    }
                }
                $frameworks[$fwname] = $data['fwinfo'];
                xarModSetVar('base','RegisteredFrameworks', serialize($frameworks));
                return xarResponse::redirect(xarModURL('base', 'admin', 'modifyconfig', array('tab' => 'javascript', 'framework' => $fwname)));
            }
            $data['fwfiles'] = array();
            // Get details for the module if we have a valid module id.
            if (!empty($data['fwinfo']['module'])) {
                $modId = xarMod::getRegID($data['fwinfo']['module']);
                $modInfo = xarModGetInfo($modId);
                if (!empty($modInfo)) {
                    $modOsDir = $modInfo['osdirectory'];
                }
            }
            $themedir = xarTplGetThemeDir();
            $basedirs = array();
            // The search path for the framework file(s).
            if (isset($modOsDir)) {
                $basedirs[] = $themedir . '/modules/' . $modOsDir . '/includes/' . $fwname;
                $basedirs[] = $themedir . '/modules/' . $modOsDir . '/xarincludes/' . $fwname;
                $basedirs[] = 'modules/' . $modOsDir . '/xartemplates/includes/' . $fwname;
                foreach($basedirs as $basedir) {
                    $fwfiles = xarMod::apiFunc('base', 'user', 'browse_files',
                        array(
                            'basedir' => $basedir,
                            'match_re' => '/\.js$/',
                            'levels' => 1
                        ));
                    if (!empty($fwfiles)) {
                        foreach ($fwfiles as $fwfile) {
                            $data['fwfiles'][$fwfile] = array('id' => $fwfile, 'name' => $fwfile);
                        }
                    }
                }
            }
        }
        $data['framework'] = $fwname;
        $data['frameworks'] = $frameworks;
        $data['defaultframework'] = xarModGetVar('base','DefaultFramework');
        $data['autoloaddefaultframework'] = xarModGetVar('base','AutoLoadDefaultFramework');


    }
    $releasenumber=xarModGetVar('base','releasenumber');
    $data['releasenumber']=isset($releasenumber) ? $releasenumber:10;

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

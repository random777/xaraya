<?php
/**
 * Review and configure Xaraya CSS
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
* Module admin function to review and configure Xaraya CSS
*
* @author AndyV_at_Xaraya_dot_Com
* @returns array
*/
function themes_admin_cssconfig()
{
    // Security Check
    if (!xarSecurityCheck('AdminTheme',0)) return;
    // Generate security key
    $data['authid'] = xarSecGenAuthKey();


    // where are we?
    if (!xarVarFetch('component', 'str::', $component, '', XARVAR_NOT_REQUIRED)) return;

    $data['component'] = $component;
    // is configurable enabled?
    if (!xarVarFetch('configurable', 'checkbox', $configurable, false, XARVAR_NOT_REQUIRED)) return;
    $data['configurable'] = $configurable;

    // labels and defaults
    $data['submitbutton'] = xarVarPrepForDisplay(xarML('Submit'));
    $data['resetbutton'] = xarVarPrepForDisplay(xarML('Reset to defaults'));
    $data['unmanagednote'] = xarVarPrepForDisplay(xarML('No configurable options are available in unmanaged mode.'));

    switch($component) {
        case "common":
            // get and verify modvars and files - all reporting inline in the form
            $data['csslinkoption'] = xarModVars::get('themes', 'csslinkoption');
            $cssfilepath = 'modules/themes/xarstyles/';
            $filemissing = xarML('none (missing)');
            $notlinked = xarML('none - use for template debugging only!!');
            if($data['csslinkoption'] == '') {
                xarModVars::set('themes', 'csslinkoption', 'static');
                if(file_exists($cssfilepath.'core.css')) {
                    $data['currentcssfile'] = xarVarPrepForDisplay($cssfilepath.'core.css');
                } else {
                    $data['currentcssfile'] = xarVarPrepForDisplay($filemissing);
                }
            } else if($data['csslinkoption'] == 'static') {
                if(file_exists($cssfilepath.'/core.css')) {
                    $data['currentcssfile'] = xarVarPrepForDisplay($cssfilepath.'core.css');
                    $handle = fopen($cssfilepath.'/core.css', 'r');
                    $data['csssource'] = fread($handle, filesize($cssfilepath.'/core.css'));
                    fclose($handle);
                } else {
                    $data['currentcssfile'] = xarVarPrepForDisplay($filemissing);
                }
            } else if($data['csslinkoption'] == 'dynamic') {
                if(file_exists($cssfilepath.'corecss.php')) {
                    $data['currentcssfile'] = xarVarPrepForDisplay($cssfilepath.'corecss.php');
                    $data['csssource'] = xarModVars::get('themes', 'corecss');
                } else {
                    $data['currentcssfile'] = xarVarPrepForDisplay($filemissing);
                }
            } else {
                $data['currentcssfile'] = xarVarPrepForDisplay($notlinked);
            }


            break;
        case "modules":
            break;
        case "themes":
            break;
        default:
            break;
    }

    return $data;
}

?>

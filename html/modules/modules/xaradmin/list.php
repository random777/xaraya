<?php
/**
 * List modules and current settings
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Module System
 * @link http://xaraya.com/index.php/release/1.html
 */
/**
 * List modules and current settings
 * @author Xaraya Development Team
 * @param several params from the associated form in template
 * @todo  finish cleanup, styles, filters and sort orders
 */
function modules_admin_list()
{
    // Security Check
    if(!xarSecurityCheck('AdminModules')) return;

    // form parameters
    if (!xarVarFetch('startnum', 'isset', $startnum, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('regen',    'isset', $regen,    NULL, XARVAR_DONT_SET)) {return;}

    // Specify labels for display (most are done in template now)
    $data['infolabel']      = xarML('Info');
    $data['reloadlabel']    = xarML('Reload');

    $authid                 = xarSecGenAuthKey();

    // make sure we dont miss empty variables (which were not passed thru)
    //if(empty($selstyle)) $selstyle                  = 'plain';
    //if(empty($selfilter)) $selfilter                = XARMOD_STATE_ANY;
    //if(empty($selsort)) $selsort                = 'namedesc';

    // pass tru some of the form variables (we dont store them anywhere, atm)
    $data['hidecore']                               = xarModGetUserVar('modules', 'hidecore');
    $data['regen']                                  = $regen;
    $data['selfilter']                              = xarModGetUserVar('modules', 'selfilter');
    $data['selsort']                                = xarModGetUserVar('modules', 'selsort');

    $data['filter'][XARMOD_STATE_ANY]               = xarML('All Modules');
    $data['filter'][XARMOD_STATE_INSTALLED]         = xarML('All Installed');
    $data['filter'][XARMOD_STATE_ACTIVE]            = xarML('All Active');
    $data['filter'][XARMOD_STATE_INACTIVE]          = xarML('All Inactive');
    $data['filter'][XARMOD_STATE_UPGRADED]          = xarML('All Upgraded');
    $data['filter'][XARMOD_STATE_UNINITIALISED]     = xarML('Not Installed');
    $data['filter'][XARMOD_STATE_MISSING_FROM_UNINITIALISED] = xarML('Missing (Not Installed)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_INACTIVE] = xarML('Missing (Inactive)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_ACTIVE]   = xarML('Missing (Active)');
    $data['filter'][XARMOD_STATE_MISSING_FROM_UPGRADED] = xarML('Missing (Upgraded)');
    $data['filter'][XARMOD_STATE_ERROR_UNINITIALISED]  = xarML('Update (Not Installed)');
    $data['filter'][XARMOD_STATE_ERROR_INACTIVE]       = xarML('Update (Inactive)');
    $data['filter'][XARMOD_STATE_ERROR_ACTIVE]         = xarML('Update (Active)');
    $data['filter'][XARMOD_STATE_ERROR_UPGRADED]       = xarML('Update (Upgraded)');
    $data['filter'][XARMOD_STATE_CORE_ERROR_UNINITIALISED]  = xarML('Core Conflict (Not Installed)');
    $data['filter'][XARMOD_STATE_CORE_ERROR_INACTIVE]       = xarML('Core Conflict (Inactive)');
    $data['filter'][XARMOD_STATE_CORE_ERROR_ACTIVE]         = xarML('Core Conflict (Active)');
    $data['filter'][XARMOD_STATE_CORE_ERROR_UPGRADED]       = xarML('Core Conflict (Upgraded)');
    $data['sort']['nameasc']                        = xarML('Name [a-z]');
    $data['sort']['namedesc']                       = xarML('Name [z-a]');


    // reset session-based message var
    xarSessionDelVar('statusmsg');

    // obtain list of modules based on filtering criteria
    // think we need to always check the filesystem
    xarMod::apiFunc('modules', 'admin', 'regenerate');
    $modlist = xarMod::apiFunc('modules','admin','getlist',array('filter' => array('State' => $data['selfilter'], 'numitems' =>20)));

    // get action icons/images
    $img_disabled       = xarTplGetImage('icons/disabled.png','base');
    $img_none           = xarTplGetImage('icons/none.png','base');
    $img_activate       = xarTplGetImage('icons/activate.png','base');
    $img_deactivate     = xarTplGetImage('icons/deactivate.png','base');
    $img_upgrade        = xarTplGetImage('icons/software-upgrade.png','base');
    $img_install        = xarTplGetImage('icons/software-install.png','base');
    $img_remove         = xarTplGetImage('icons/remove.png','base');
    $img_blank          = xarTplGetImage('icons/blank.png','base');
    $img_core           = xarTplGetImage('icons/core.png','base');
    $img_warning        = xarTplGetImage('icons/dialog-warning.png','base');
    $img_error          = xarTplGetImage('icons/dialog-error.png','base');

    // get other images
    $data['infoimg']    = xarTplGetImage('icons/info.png','base');
    $data['editimg']    = xarTplGetImage('icons/hooks.png','base');
    $data['propimg']    = xarTplGetImage('icons/modify.png','base');

    $data['listrowsitems'] = array();

    $listrows = array();
    $i = 0;

    // now we can prepare data for template
    // we will use standard xarMod api calls as much as possible
    //We want class as Authentication for auth mods so need to allow for this. Use hardcode list or core mods for now.
    $coreMods = array('authsystem','base','blocks','dynamicdata','installer','mail','modules','privileges','roles','themes');
    foreach($modlist as $mod){

        // we're going to use the module regid in many places
        $thismodid = $mod['regid'];
        $listrows[$i]['modid'] = $thismodid;
        // if this module has been classified as 'Core'
        // we will disable certain actions
        $modinfo = xarModGetInfo($thismodid);
        $coremod = in_array(strtolower($modinfo['name']),$coreMods);
        /* coreMods are hardcoded so we can gain independance from class for now for core mods
        if(substr($modinfo['class'], 0, 4)  == 'Core'){
            $coremod = true;
        }else{
            $coremod = false;
        }
        */

        // lets omit core modules if a user chosen to hide them from the list
        if($coremod && $data['hidecore']) continue;

        // for the sake of clarity, lets prepare all our links in advance
        $installurl                = xarModURL('modules',
                                    'admin',
                                    'install',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
        $deactivateurl              = xarModURL('modules',
                                    'admin',
                                    'deactivate',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
        $removeurl                  = xarModURL('modules',
                                    'admin',
                                    'remove',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));
        $upgradeurl                 = xarModURL('modules',
                                    'admin',
                                    'upgrade',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));

        $errorurl                   = xarModURL('modules',
                                    'admin',
                                    'viewerror',
                                     array( 'id'        => $thismodid,
                                            'authid'    => $authid));


        // link to module main admin function if any
        $listrows[$i]['modconfigurl'] = '';
        $listrows[$i]['configurl'] = '';
        if(isset($mod['admin']) && $mod['admin'] == 1 && $mod['state'] == XARMOD_STATE_ACTIVE){
            $listrows[$i]['modconfigurl'] = xarModURL($mod['name'], 'admin');
        }

        // common urls
        $listrows[$i]['editurl']    = xarModURL('modules',
                                    'admin',
                                    'modify',
                                     array( 'id'        => $thismodid));
        // added due to the feature request - opens info in new window
        $listrows[$i]['infourl'] = xarModURL('modules',
                                    'admin',
                                    'modinfo',
                                    array( 'id'        => $thismodid));
        if(isset($mod['admin']) && $mod['admin'] == 1 && $mod['state'] == XARMOD_STATE_ACTIVE){
            $listrows[$i]['configurl'] = xarModURL($mod['name'],
                                        'admin',
                                        'modifyconfig');
        }
        // image urls


        // common listitems
        $listrows[$i]['coremod']        = $coremod;
        $listrows[$i]['name']           = $mod['name'];
        $listrows[$i]['displayname']    = $mod['displayname'];
        $listrows[$i]['version']        = $mod['version'];
        $listrows[$i]['regid']          = $thismodid;
        $listrows[$i]['edit']           = xarML('On/Off');
        $listrows[$i]['prop']           = xarML('Modify');

        // conditional data
        if($mod['state'] == XARMOD_STATE_UNINITIALISED){
            // this module is 'Uninitialised' or 'Not Installed' - set labels and links
            $statelabel = xarML('Not Installed');
            $listrows[$i]['state'] = XARMOD_STATE_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('Install');
            $listrows[$i]['actionurl']          = $installurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_install;
            $listrows[$i]['actionimg2']         = $img_remove;
            $listrows[$i]['actionclass1']       = 'xar-install';
            $listrows[$i]['actionclass2']       = 'xar-remove';

            $listrows[$i]['configurl']          = '';

        }elseif($mod['state'] == XARMOD_STATE_INACTIVE){
            // this module is 'Inactive'        - set labels and links
            $statelabel = xarML('Inactive');
            $listrows[$i]['state'] = XARMOD_STATE_INACTIVE;

            $listrows[$i]['removelabel']        = xarML('Remove');
            $listrows[$i]['removeurl']          = $removeurl;

            $listrows[$i]['actionlabel']        = xarML('Activate');
            $listrows[$i]['actionlabel2']       = xarML('Remove');
            $listrows[$i]['actionurl']          = $installurl;

            $listrows[$i]['actionimg1']         = $img_activate;
            $listrows[$i]['actionimg2']         = $img_remove;
            $listrows[$i]['actionclass1']       = 'xar-activate';
            $listrows[$i]['actionclass2']       = 'xar-remove';

            $listrows[$i]['configurl']          = '';

        }elseif($mod['state'] == XARMOD_STATE_ACTIVE){
            // this module is 'Active'          - set labels and links
            $statelabel = xarML('Active');
            $listrows[$i]['state'] = XARMOD_STATE_ACTIVE;
            // here we are checking for module class
            // to prevent ppl messing with the core modules
            if(!$coremod){
                $listrows[$i]['actionlabel']    = xarML('Deactivate');
                $listrows[$i]['actionurl']      = $deactivateurl;
                $listrows[$i]['removeurl']      = '';

                $listrows[$i]['actionimg1']     = $img_deactivate;
                $listrows[$i]['actionimg2']     = $img_remove;

                $listrows[$i]['actionclass1']   = 'xar-deactivate';
                $listrows[$i]['actionclass2']   = 'xar-remove';
            }else{
                $listrows[$i]['actionlabel']    = xarML('[core module]');
                $listrows[$i]['actionlabel2']   = xarML('[core module]');
                $listrows[$i]['actionurl']      = '';
                $listrows[$i]['removeurl']      = '';

                $listrows[$i]['actionimg1']     = $img_install;
                $listrows[$i]['actionimg2']     = $img_remove;

                $listrows[$i]['actionclass1']   = 'xar-install';
                $listrows[$i]['actionclass2']   = 'xar-remove';
            }
        }elseif($mod['state'] == XARMOD_STATE_MISSING_FROM_UNINITIALISED ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_INACTIVE ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_ACTIVE ||
                $mod['state'] == XARMOD_STATE_MISSING_FROM_UPGRADED){
            // this module is 'Missing'         - set labels and links
            $statelabel = xarML('Missing');
            $listrows[$i]['state'] = XARMOD_STATE_MISSING_FROM_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('Module is missing');
            $listrows[$i]['actionlabel2']       = xarML('Remove');
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['removeurl']          = $removeurl;

            $listrows[$i]['actionimg1']         = $img_warning;
            $listrows[$i]['actionimg2']         = $img_remove;

            $listrows[$i]['actionclass1']         = 'xar-missing';
            $listrows[$i]['actionclass2']         = 'xar-remove';

            $listrows[$i]['configurl']          = '';

        }elseif($mod['state'] == XARMOD_STATE_ERROR_UNINITIALISED ||
                $mod['state'] == XARMOD_STATE_ERROR_INACTIVE ||
                $mod['state'] == XARMOD_STATE_ERROR_ACTIVE ||
                $mod['state'] == XARMOD_STATE_ERROR_UPGRADED){
            // Bug 1664 - this module db version is greater than file version
            // 'Error' - set labels and links
            $statelabel = xarML('Error');
            $listrows[$i]['state'] = XARMOD_STATE_ERROR_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('View Error');
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_error;
            $listrows[$i]['actionimg2']         = $img_remove;

            $listrows[$i]['actionclass1']         = 'xar-errorstate';
            $listrows[$i]['actionclass2']         = 'xar-remove';

            $listrows[$i]['configurl']          = '';

        }elseif($mod['state'] == XARMOD_STATE_UPGRADED){
            // this module is 'Upgraded'        - set labels and links
            $statelabel = xarML('New version');
            $listrows[$i]['state'] = XARMOD_STATE_UPGRADED;

            $listrows[$i]['actionlabel']        = xarML('Upgrade');
            $listrows[$i]['actionurl']          = $upgradeurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_upgrade;
            $listrows[$i]['actionimg2']         = $img_remove;

            $listrows[$i]['actionclass1']         = 'xar-upgrade';
            $listrows[$i]['actionclass2']         = 'xar-remove';

            $listrows[$i]['configurl']          = '';

        }elseif($mod['state'] == XARMOD_STATE_CORE_ERROR_UNINITIALISED ||
                $mod['state'] == XARMOD_STATE_CORE_ERROR_INACTIVE ||
                $mod['state'] == XARMOD_STATE_CORE_ERROR_ACTIVE ||
                $mod['state'] == XARMOD_STATE_CORE_ERROR_UPGRADED){
            // this module is incompatible with current core version
            // 'Core Conflict' - set labels and links
            $statelabel = xarML('Core Conflict');
            $listrows[$i]['state'] = XARMOD_STATE_CORE_ERROR_UNINITIALISED;

            $listrows[$i]['actionlabel']        = xarML('View Error');
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_error;
            $listrows[$i]['actionimg2']         = $img_remove;

            $listrows[$i]['actionclass1']         = 'xar-errorstate';
            $listrows[$i]['actionclass2']         = 'xar-remove';

            $listrows[$i]['configurl']          = '';

        } else {
            // Something seriously wrong
            $statelabel = xarML('Unknown');
            $listrows[$i]['actionurl']          = $errorurl;
            $listrows[$i]['removeurl']          = '';
            $listrows[$i]['state']              = xarML('Remove');
            $listrows[$i]['actionlabel']        = xarML('Error in list generation');
            $listrows[$i]['actionlabel2']       = xarML('Remove');

            $listrows[$i]['actionimg1']         = $img_error;
            $listrows[$i]['actionimg2']         = $img_remove;

            $listrows[$i]['actionclass1']         = 'xar-errorstate';
            $listrows[$i]['actionclass2']         = 'xar-remove';

            $listrows[$i]['configurl']          = '';

        }

        // nearly done
        $listrows[$i]['statelabel']     = $statelabel;

        $data['listrowsitems'] = $listrows;
        $i++;
    }

    // total count of items
    $data['totalitems'] = $i;

    // not ideal but would do for now - reverse sort by module names
    if($data['selsort'] == 'namedesc') krsort($data['listrowsitems']);

    // Send to BL.
    return $data;
}

?>

<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * List themes and current settings
 * @author Marty Vance
 * @param none
 */
function themes_admin_list()
{
    // Security Check
    if(!xarSecurityCheck('AdminTheme')) return;

    // form parameters
    if (!xarVarFetch('startnum', 'isset', $startnum,    NULL,  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('regen',    'isset', $regen,       NULL,  XARVAR_DONT_SET)) return;
    if (!xarVarFetch('sort',     'enum:asc:desc:', $sort, NULL,  XARVAR_DONT_SET)) return;
    //if (!xarVarFetch('selfilter','isset', $selfilter,   NULL,  XARVAR_DONT_SET)) return;

    $data['items'] = array();

    $data['infolabel']                              = xarML('Info');
    $data['actionlabel']                            = xarML('Action');
    $data['optionslabel']                           = xarML('Options');
    $data['reloadlabel']                            = xarML('Refresh');
    $data['pager']                                  = '';
    $authid = xarSecGenAuthKey();

    // pass tru some of the form variables (we dont store them anywhere, atm)
    $data['hidecore']                               = xarModGetUserVar('themes', 'hidecore');
    $data['regen']                                  = $regen;
    $data['selstyle']                               = xarModGetUserVar('themes', 'selstyle');
    $data['selfilter']                              = xarModGetUserVar('themes', 'selfilter');
    $data['selclass']                               = xarModGetUserVar('themes', 'selclass');
    $data['useicons']                               = xarModGetUserVar('themes', 'useicons');

    // select vars for drop-down menus
    $data['style']['plain']                         = xarML('Plain');
    $data['style']['preview']                       = xarML('Preview');

    // labels for class names
    $data['class']['all']                           = xarML('All');
    $data['class']['system']                        = xarML('System');
    $data['class']['utility']                       = xarML('Utility');
    $data['class']['user']                          = xarML('User');

    $data['filter'][XARTHEME_STATE_ANY]                         = xarML('All');
    $data['filter'][XARTHEME_STATE_INSTALLED]                   = xarML('Installed');
    $data['filter'][XARTHEME_STATE_ACTIVE]                      = xarML('Active');
    $data['filter'][XARTHEME_STATE_INACTIVE]                    = xarML('Inactive');
    $data['filter'][XARTHEME_STATE_UNINITIALISED]               = xarML('Uninitialized');
    $data['filter'][XARTHEME_STATE_MISSING_FROM_UNINITIALISED]  = xarML('Missing (Not Inited)');
    $data['filter'][XARTHEME_STATE_MISSING_FROM_INACTIVE]       = xarML('Missing (Inactive)');
    $data['filter'][XARTHEME_STATE_MISSING_FROM_ACTIVE]         = xarML('Missing (Active)');
    $data['filter'][XARTHEME_STATE_MISSING_FROM_UPGRADED]       = xarML('Missing (Upgraded)');

    $data['default']                           = xarModGetVar('themes', 'default', 1);

    // obtain list of modules based on filtering criteria
/*     if($regen){ */
        // lets regenerate the list on each reload, for now
        if(!xarModAPIFunc('themes', 'admin', 'regenerate')) return;

        // assemble filter for theme list
        $filter = array('State' => $data['selfilter']);
        if ($data['selclass'] != 'all') {
            $filter['Class'] = strtr(
                $data['selclass'],
                array('system' => 0, 'utility' => 1, 'user' => 2)
            );
        }
        // get themes
        $themelist = xarModAPIFunc('themes','admin','getthemelist',  array('filter'=> $filter));
/*         , array('filter'=> array('State' => $data['selfilter'][0]))); */
/*     }else{ */
/*         // or just fetch the quicker old list */
/*         $themelist = xarModAPIFunc('themes','admin','GetThemeList', array('filter'=> array('State' => $data['selfilter']))); */
/*     } */

    // set sorting vars
    if (empty($sort)) $sort = 'asc'; // this is default for getthemelist()
    if ($sort == 'desc') {
        $themelist = array_reverse($themelist);
        $newsort = 'asc';
        $sortimage = xarTplGetImage('arrow_up.gif', 'base');
        $sortlabel = xarML('Sort Ascending');
    } else {
        $newsort = 'desc';
        $sortimage = xarTplGetImage('arrow_down.gif', 'base');
        $sortlabel = xarML('Sort Descending');
    }
    $data['sorturl'] = xarServerGetCurrentURL(array('sort' => $newsort));
    $data['sortimage'] = $sortimage;
    $data['sortlabel'] = $sortlabel;

    // get action icons/images
    $img_disabled       = xarTplGetImage('set1/disabled.png');
    $img_none           = xarTplGetImage('set1/none.png');
    $img_activate       = xarTplGetImage('set1/activate.png');
    $img_deactivate     = xarTplGetImage('set1/deactivate.png');
    $img_upgrade        = xarTplGetImage('set1/upgrade.png');
    $img_initialise     = xarTplGetImage('set1/initialise.png');
    $img_remove         = xarTplGetImage('set1/remove.png');

    // get other images
    $data['infoimg']    = xarTplGetImage('set1/info.png');
    $data['editimg']    = xarTplGetImage('set1/hooks.png');

    $data['listrowsitems'] = array();
    $listrows = array();
    $i = 0;

    // now we can prepare data for template
    // we will use standard xarMod api calls as much as possible
    foreach($themelist as $theme){

        // we're going to use the module regid in many places
        $thisthemeid = $theme['regid'];

        // if this module has been classified as 'Core'
        // we will disable certain actions
/*         $themeinfo = xarThemeGetInfo($thisthemeid); */
/*         if(substr($themeinfo['class'], 0, 4)  == 'Core'){ */
/*             $coretheme = true; */
/*         }else{ */
            $coretheme = false;
/*         } */

        // for the sake of clarity, lets prepare all our links in advance
        $initialiseurl = xarModURL('themes', 'admin', 'install',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $activateurl = xarModURL('themes', 'admin', 'activate',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $deactivateurl = xarModURL('themes', 'admin', 'deactivate',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $removeurl = xarModURL('themes', 'admin', 'remove',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $upgradeurl = xarModURL('themes', 'admin', 'upgrade',
            array('id' => $thisthemeid, 'authid' => $authid)
        );

        // common urls
        $listrows[$i]['editurl'] = xarModURL('themes', 'admin', 'modify',
            array('id' => $thisthemeid, 'authid' => $authid)
        );
        $listrows[$i]['infourl'] = xarModURL('themes', 'admin', 'themesinfo',
            array('id' => $thisthemeid)
        );
        // added due to the feature request - opens info in new window
        $listrows[$i]['infourlnew'] = xarModURL('themes', 'admin', 'themesinfo',
            array('id' => $thisthemeid)
        );
        $listrows[$i]['defaulturl']= xarModURL('themes', 'admin', 'setdefault',
            array('id' => $thisthemeid, 'authid' => $authid)
        );

        // common listitems
        $listrows[$i]['coretheme']      = $coretheme;
        $listrows[$i]['displayname']    = $theme['name'];
        $listrows[$i]['description']    = $theme['description'];
        $listrows[$i]['version']        = $theme['version'];
        $listrows[$i]['edit']           = xarML('Edit');
        $listrows[$i]['class']          = $theme['class'];
        $listrows[$i]['directory']      = $theme['directory'];

        if (empty($theme['state'])) {
            $theme['state'] = 1;
        }

        // class labels
        switch($theme['class']) {
            case '2':
                $listrows[$i]['classlabel'] = $data['class']['user'];
                break;
            case '1':
                $listrows[$i]['classlabel'] = $data['class']['utility'];
                break;
            case '0':
                $listrows[$i]['classlabel'] = $data['class']['system'];
                break;
            default:
                $listrows[$i]['classlabel'] = xarML('Unknown');
        }

        // conditional data
        if($theme['state'] == 1){
            // this theme is 'Uninitialised'   - set labels and links
            $statelabel = xarML('Uninitialized');
            $listrows[$i]['state'] = 1;

            $listrows[$i]['actionlabel']        = xarML('Initialize');
            $listrows[$i]['actionurl']          = $initialiseurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_initialise;
            $listrows[$i]['actionimg2']         = $img_none;


        }elseif($theme['state'] == 2){
            // this theme is 'Inactive'        - set labels and links
            $statelabel = xarML('Inactive');
            $listrows[$i]['state'] = 2;

            $listrows[$i]['removelabel']        = xarML('Remove');
            $listrows[$i]['removeurl']          = $removeurl;

            $listrows[$i]['actionlabel']        = xarML('Activate');
            $listrows[$i]['actionurl']          = $activateurl;

            $listrows[$i]['actionimg1']         = $img_activate;
            $listrows[$i]['actionimg2']         = $img_remove;
        }elseif($theme['state'] == 3){
            // this theme is 'Active'          - set labels and links
            $statelabel = xarML('Active');
            $listrows[$i]['state'] = 3;
            // here we are checking for theme class
            // to prevent ppl messing with the core themes
            if(!$coretheme){
                $listrows[$i]['actionlabel']    = xarML('Deactivate');
                $listrows[$i]['actionurl']      = $deactivateurl;
                $listrows[$i]['removeurl']      = '';

                $listrows[$i]['actionimg1']     = $img_deactivate;
                $listrows[$i]['actionimg2']     = $img_none;
            }else{
                $listrows[$i]['actionlabel']    = xarML('[core module]');
                $listrows[$i]['actionurl']      = '';
                $listrows[$i]['removeurl']      = '';

                $listrows[$i]['actionimg1']     = $img_disabled;
                $listrows[$i]['actionimg2']     = $img_disabled;
            }
        }elseif($theme['state'] == 4 ||
                $theme['state'] == 7 ||
                $theme['state'] == 8 ||
                $theme['state'] == 9){
            // this theme is 'Missing'         - set labels and links
            $statelabel = xarML('Missing');
            $listrows[$i]['state'] = 4;

            $listrows[$i]['removelabel']        = xarML('Remove');
            $listrows[$i]['actionlabel']        = xarML('Remove');
            $listrows[$i]['actionurl']          = $removeurl;
            $listrows[$i]['removeurl']          = $removeurl;

            $listrows[$i]['actionimg1']         = $img_none;
            $listrows[$i]['actionimg2']         = $img_remove;

        }elseif($theme['state'] == 5){
            // this theme is 'Upgraded'        - set labels and links
            $statelabel = xarML('Upgraded');
            $listrows[$i]['state'] = 5;

            $listrows[$i]['actionlabel']        = xarML('Upgrade');
            $listrows[$i]['actionurl']          = $upgradeurl;
            $listrows[$i]['removeurl']          = '';

            $listrows[$i]['actionimg1']         = $img_none;
            $listrows[$i]['actionimg2']         = $img_upgrade;

        }

        // nearly done
        $listrows[$i]['statelabel']     = $statelabel;
        $listrows[$i]['regid']          = $thisthemeid;

        // preview images
        $previewpath = "themes/$theme[directory]/images/preview.jpg";
        $listrows[$i]['preview'] = file_exists($previewpath) ? $previewpath : '';

        $data['listrowsitems'] = $listrows;
        $i++;
    }

    // detailed info image url
    $data['infoimage'] = xarTplGetImage('help.gif');

    // Send to template
    return $data;
}
?>

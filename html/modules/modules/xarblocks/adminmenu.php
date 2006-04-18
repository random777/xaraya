<?php
/**
 * Admin panels block management
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage modules module
 * @author Andy Varganov <andyv@xaraya.com>
 */

/**
 * Initialise block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 */
function modules_adminmenublock_init()
{
    // Nothing to configure...
    return array('nocache' => 1);
}

/**
 * Get information on block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @param   none
 * @return  data array
 * @throws  no exceptions
 * @todo    nothing
*/
function modules_adminmenublock_info()
{
    // Values
    return array(
        'text_type' => 'adminmenu',
        'module' => 'modules',
        'text_type_long' => 'Admin Menu',
        'allow_multiple' => false,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => false
    );
}

/**
 * Display adminmenu block
 *
 * @author  Andy Varganov <andyv@xaraya.com>
 * @access  public
 * @return  data array on success or void on failure
 * @todo    implement centre menu position
*/
function modules_adminmenublock_display($blockinfo)
{
    // Security Check
    if (!xarSecurityCheck('AdminPanel',0,'Block',"adminmenu:$blockinfo[title]:$blockinfo[bid]")) {return;}

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }
    
    // are there any admin modules, then get the whole list sorted by names
    // checking this as early as possible
    $mods = xarModAPIFunc('modules', 'admin', 'getlist', array('filter' => array('AdminCapable' => 1)));
  
    // which module is loaded atm?
    // we need it's name, type and function - dealing only with admin type mods, aren't we?
    list($thismodname, $thismodtype, $thisfuncname) = xarRequestGetInfo();

    // SETTING 1: Show a logout link in the block?
    $showlogout = false;
    if(isset($vars['showlogout']) && $vars['showlogout']) $showlogout = true;
    
    // SETTING 2: Menustyle 
    if(!isset($vars['menustyle'])) {
        // If it is not set, revert to the default setting
        $vars['menustyle'] = xarModGetVar('modules', 'menustyle');
    }

    // SETTING 3: Show overviews or not
    if(!isset($vars['overview'])) {
        // If it i not set, rever to the default setting
        $vars['overview'] = xarModGetVar('modules','disableoverview');
    }
    
    // Get current URL for later comparisons because we need to compare
    // xhtml compliant url, we fetch the default 'XML'-formatted URL.
    $currenturl = xarServerGetCurrentURL();

    // Admin types
    // FIXME: this is quite ad-hoc here
    $admintypes = array('admin', 'util');

    switch(strtolower($vars['menustyle'])){
        case 'byname': // display by name
            foreach($mods as $mod) {
                $modname = $mod['name'];
                $labelDisplay = $mod['displayname'];
                // get URL to module's main function
                $link = xarModURL($modname, 'admin', 'main', array());
                // if this module is loaded we probably want to display it with -current css rule in the menu
                $adminmods[$modname]['features'] = array(
                    'label'     => $labelDisplay,
                    'link'      => $link,
                    'modactive' => 0,
                    'overview'  => 0,
                    'maintitle' => xarML('Show administration options for module #(1)', $labelDisplay));
                
                if ($modname == $thismodname && in_array($thismodtype, $admintypes)) {
                    // this module is currently loaded (active), we need to display
                    // 1. blank label 2. no URL 3. no title text 4. links to module functions, when users looking at default main function
                    // 5. URL with title text, when user is looking at other than default function of this module

                    // adding attributes and flags to each module link for the template
                    if ($thisfuncname != 'main' || $thismodtype != 'admin'){
                        $adminmods[$modname]['features']['overview'] = 1;
                        $adminmods[$modname]['features']['maintitle'] = xarML('Display overview information for module #(1)', $labelDisplay);
                    }

                    // For active module we need to display the mod functions links
                    // call the api function to obtain function links, but don't raise an exception if it's not there
                    $menulinks = xarModAPIFunc($modname, 'admin', 'getmenulinks', array(), false);

                    // scan array and prepare the links
                    if (!empty($menulinks)) {
                        foreach($menulinks as $menulink) {
                            $adminmods[$modname]['indlinks'][] = array(
                                'adminlink'     => $menulink['url'],
                                'adminlabel'    => $menulink['label'],
                                'admintitle'    => $menulink['title'],
                                'funcactive'    => ($menulink['url'] == $currenturl) ? 1 : 0
                            );
                        }
                    } 
                } // if
            } // foreach
            
            $template = 'verticallistbyname';
            $data = array('adminmods'     => $adminmods);
            break;

        default:
        case 'bycat': // sort by categories
            // <mrb> for the release we can do without the adminmenu table, if 
            // that gains functionality consider putting it back.
            foreach ($mods as $mod) {
                // get URL to module's main function
                $modname=$mod['name'];
                $link = xarModURL($modname, 'admin', 'main', array());
                $labelDisplay = $mod['displayname'];
                if(!isset($mod['category']) or $mod['category'] == '0') {
                    $mod['category'] = xarML('Unknown');
                }
                $cat = xarVarPrepForDisplay($mod['category']);

                // if this module is loaded we probably want to display it with -current css rule in the menu
                $catmods[$cat][$modname]['features'] = array(
                    'label'     => $labelDisplay,
                    'link'      => $link,
                    'modactive' => 0,
                    'overview'  => 0,
                    'maintitle' => xarML('Show administration options for module #(1)', $labelDisplay));
                if ($modname == $thismodname && in_array($thismodtype, $admintypes)) {
                    // this module is currently loaded (active), we need to display
                    // 1. blank label 2. no URL 3. no title text 4. links to module functions, when users looking at default main function
                    // 5. URL with title text, when user is looking at other than default function of this module

                    // adding attributes and flags to each module link for the template
                    $catmods[$cat][$modname]['features']['modactive'] = 1;
                    if ($thisfuncname != 'main' || $thismodtype != 'admin'){
                        $catmods[$cat][$modname]['features']['overview'] = 1;
                        $catmods[$cat][$modname]['features']['maintitle'] = xarML('Display overview information for module #(1)', $labelDisplay);
                    }
                    // For active module we need to display the mod functions links
                    // call the api function to obtain function links, but don't raise an exception if it's not there
                    $menulinks = xarModAPIFunc($modname, 'admin', 'getmenulinks', array(), false);

                    // scan array and prepare the links
                    if (!empty($menulinks)) {
                        foreach($menulinks as $menulink) {
                            $catmods[$cat][$modname]['indlinks'][] = array(
                                'adminlink'     => $menulink['url'],
                                'adminlabel'    => $menulink['label'],
                                'admintitle'    => $menulink['title'],
                                'funcactive'    => ($menulink['url'] == $currenturl) ? 1 : 0
                            );
                        }
                    }
                } else {
                   // Why is this needed?
                   unset($mod['displayname']);
                }
            } //inner foreach
                
            $template = 'verticallistbycats';
            $data = array(
                'catmods'       => $catmods
            );
            break;
    }

    // Set template base.
    // FIXME: not allowed to set private variables of BL directly
    $blockinfo['_bl_template_base'] = $template;

    // Populate block info and pass to BlockLayout.
    $data['showlogout'] = $showlogout;
    $data['menustyle']  = $vars['menustyle'];
    $blockinfo['content'] = $data;
    return $blockinfo;
}

?>

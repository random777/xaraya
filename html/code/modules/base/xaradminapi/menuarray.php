<?php
/**
 * Utility function pass individual menu items to the main menu
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * utility function to create an array for a getmenulinks function
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @returns array
 * @return array of menulinks for a module
 */
function base_adminapi_menuarray($args)
{
    if (!isset($args['module'])) {
        $urlinfo = xarRequest::getInfo();
        $args['module'] = $urlinfo[0];
    }
    $menulinks = array();
    $menuarray = xarMod::apiFunc('base','admin','loadadminmenuarray',array('module' => $args['module']));
    if (!empty($menuarray['items'])) {
        foreach ($menuarray['items'] as $menuitem) {
            $url = isset($menuitem['target']) ? xarModURL($args['module'],'admin',$menuitem['target']) : xarServer::getBaseURL();
            $link = array('url'   => $url,
                          'title' => $menuitem['title'],
                          'label' => $menuitem['label']
                           );
            if(isset($menuitem['mask'])) {
                if (xarSecurityCheck($menuitem['mask'])) {
                    $menulinks[] = $link;
                }
            } else {
                $menulinks[] = $link;
            }
        }
    } else {
        try {
            $tabs = xarMod::apiFunc($args['module'],'data','adminmenu');
            foreach($tabs as $tab) {
                $url = isset($tab['target']) ? xarModURL($args['module'],'admin',$tab['target']) : xarServer::getBaseURL();
                $label = isset($tab['label']) ? $tab['label'] : xarML('Missing label');
                $title = isset($tab['title']) ? $tab['title'] : $label;
                $link = array('url'   => $url,
                              'title' => $title,
                              'label' => $label
                               );
                if(isset($tab['mask'])) {
                    if (xarSecurityCheck($tab['mask'],0)) {
                        $menulinks[] = $link;
                    }
                } else {
                    $menulinks[] = $link;
                }
            }
        } catch (Exception $e) {}
    }
    return $menulinks;
}

?>

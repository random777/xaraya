<?php
/**
 * Menu Block
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Manage block
 *
 * @author  John Cox <admin@dinerminor.com>
 * @access  public
 * @param   none
 * @return  nothing
 * @throws  no exceptions
 * @todo    nothing
*/
sys::import('modules.base.xarblocks.menu');

class MenuBlockAdmin extends MenuBlock implements iBlock
{
/**
 * Modify Function to the Blocks Admin
 * @param $data array containing title,content
 */
    public function modify(Array $data=array())
    {
        $data = parent::modify($data);

        if (!isset($data['marker'])) $data['marker'] = $this->marker;
        if (!isset($data['displaymodules'])) $data['displaymodules'] = $this->displaymodules;
        if (!isset($data['modulelist'])) $data['modulelist'] = $this->modulelist;
        if (!isset($data['displayrss'])) $data['displayrss'] = $this->displayrss;
        if (!isset($data['displayprint'])) $data['displayprint'] = $this->displayprint;
        if (!isset($data['content'])) $data['content'] = $this->content;
        if (!isset($data['showlogout'])) $data['showlogout'] = $this->showlogout;
        if (!isset($data['showback'])) $data['showback'] = $this->showback;

        // @CHECKME: is this used?
        if (empty($data['style'])) $data['style'] = 1;

        $data['modules'] = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('UserCapable' => 1, 'State' => XARMOD_STATE_ACTIVE)));
/*        // Prepare output array
        $c=0;
        if (!empty($data['content'])) {
            $contentlines = explode("LINESPLIT", $data['content']);
            $data['contentlines'] = array();
            foreach ($contentlines as $contentline) {
                $link = explode('|', $contentline);
                $data['contentlines'][] = $link;
                $c++;
            }
        }*/
        $data['view_access'] = isset($data['view_access']) ? $data['view_access'] : array();

        // @CHECKME: is this used?
        if (empty($data['lines'])) $data['lines'] = array($this->content);
        return $data;
    }

/**
 * Updates the Block config from the Blocks Admin
 * @param $data array containing title,content
 */
    public function update(Array $data=array())
    {
        $data = parent::update($data);

        // Global options.
        if (!xarVarFetch('displaymodules', 'str:1',    $content['displaymodules'], $this->displaymodules, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('modulelist',     'str',      $content['modulelist'], $this->modulelist, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showlogout',     'checkbox', $content['showlogout'], false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('displayrss',     'checkbox', $content['displayrss'], false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('displayprint',   'checkbox', $content['displayprint'], false, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('marker',         'str:1',    $content['marker'], $this->marker, XARVAR_NOT_REQUIRED)) return;
        if (!xarVarFetch('showback',       'checkbox', $content['showback'], false, XARVAR_NOT_REQUIRED)) return;

        // User links.
        $content['lines'] = array();
        $c = 1;
        if (!xarVarFetch('name', 'list:str', $linkname, NULL, XARVAR_NOT_REQUIRED)) return;
        if (!empty($linkname)) {
            if (!xarVarFetch('url',     'list:str',      $linkurl,  NULL, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('description',    'list:str',      $linkdesc,  NULL, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('visible', 'array', $linkvisible, NULL, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('child',   'list:checkbox', $linkchild, NULL, XARVAR_NOT_REQUIRED)) {return;}
            if (!xarVarFetch('delete',  'list:checkbox', $linkdelete, NULL, XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('insert',  'list:checkbox', $linkinsert, NULL, XARVAR_NOT_REQUIRED)) return;

            foreach ($linkname as $v) {
                if (!isset($linkdelete[$c]) || $linkdelete[$c] == false) {
                    $content['lines'][] = array(
                                    'url' => $linkurl[$c],
                                    'name' => $linkname[$c],
                                    'description' => $linkdesc[$c],
                                    'visible' => !empty($linkvisible[$c]) ? $linkvisible[$c] : 0,
                                    'child' => !empty($linkchild[$c]) ? $linkchild[$c] : 0,
                                );
                }
                if (!empty($linkinsert[$c])) {
                    $content[] = array();
                }
                $c++;
            }
        }

        if (!xarVarFetch('new_linkname', 'str', $new_linkname, '', XARVAR_NOT_REQUIRED)) return;
        if (!empty($new_linkname)) {
            if (!xarVarFetch('new_linkurl', 'str', $new_linkurl, '', XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('new_linkdesc', 'str', $new_linkdesc, '', XARVAR_NOT_REQUIRED)) return;

            $content['lines'][] = array(
                            'url' => $new_linkurl,
                            'name' => $new_linkname,
                            'description' => $new_linkdesc,
                            'visible' => 1,
                            'child' => 0,
                        );
        }

        $modules = xarMod::apiFunc('modules', 'admin', 'getlist', array('filter' => array('State' => XARMOD_STATE_ACTIVE)));
        sys::import('modules.dynamicdata.class.properties.master');
        $accessproperty = DataPropertyMaster::getProperty(array('name' => 'access'));
        $content['view_access'] = array();
        foreach ($modules as $module) {
            $isvalid = $accessproperty->checkInput('view_access_' . $module['name']);echo $isvalid;
            $content['view_access'][$module['name']] = $accessproperty->value;
        }

        $data['content'] = serialize($content);
        return $data;
    }
}
?>

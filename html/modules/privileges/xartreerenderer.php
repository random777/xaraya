<?php
/**
 * Privileges tree renderer
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 */

/* Purpose of file:  Privileges tree renderer
 *
 * @package modules
 * @subpackage Privileges module
 * @author Marc Lutolf <marcinmilan@xaraya.com>
*/

include_once 'modules/privileges/xarprivileges.php';

class xarTreeRenderer
{

    var $privs;

    // some variables we'll need to hold drawing info
    var $html;
    var $nodeindex;
    var $level;

    // convenience variables to hold strings referring to pictures
    // moved to constructor to make img paths dynamic

    var $icon_delete;
    var $icon_groups;
    var $icon_remove;
    var $icon_toggle;

    // we'll use this to check whether a group has already been processed
    var $alreadydone;

    /**
     * Constructor
     *
    */
        function xarTreeRenderer()
        {
            $this->privs = new xarPrivileges();

            $this->icon_toggle = xarTplGetImage('icons/toggle.png', 'base');
            $this->icon_delete = xarTplGetImage('icons/delete.png', 'base');
            $this->icon_groups = xarTplGetImage('icons/system-user-groups.png', 'base');
            $this->icon_remove = xarTplGetImage('icons/remove.png', 'base');
            $this->icon_toggle = xarTplGetImage('icons/toggle.png', 'base');
        }

    /**
     * maketrees: create an array of all the privilege trees
     *
     * Makes a tree representation of each privileges tree
     * Returns an array of the trees
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   string $arg indicates what types of elements to get
     * @return  array of trees
     * @throws  none
     * @todo    none
    */
        function maketrees($arg)
        {
            $trees = array();
            foreach ($this->privs->gettoplevelprivileges($arg) as $entry) {
                array_push($trees,$this->maketree($this->privs->getPrivilege($entry['pid'])));
            }
            return $trees;
        }

    /**
     * maketree: make a tree of privileges
     *
     * Makes a tree representation of a privileges hierarchy
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   none
     * @return  boolean
     * @throws  none
     * @todo    none
    */
        function maketree($privilege)
        {
            return $this->addbranches(array('parent'=>$this->privs->getprivilegefast($privilege->getID())));
        }

    /**
     * addbranches: given an initial tree node, add on the branches
     *
     * Adds branches to a tree representation of privileges
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   tree node
     * @return  tree node
     * @throws  none
     * @todo    none
    */
        function addbranches($node)
        {
            $object = $node['parent'];
            $node['expanded'] = false;
            $node['selected'] = false;
            $node['children'] = array();
            foreach($this->privs->getChildren($object['pid']) as $subnode){
                $node['children'][] = $this->addbranches(array('parent'=>$subnode));
            }
            return $node;
        }

    /**
     * drawtrees: create an array of tree drawings
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   string $arg indicates what types of elements to get
     * @return  array of tree drawings
     * @throws  none
     * @todo    none
    */
        function drawtrees($arg)
        {
            $drawntrees = array();
            foreach($this->maketrees($arg) as $tree){
                $drawntrees[] = array('tree'=>$this->drawtree($tree));
            }
            return $drawntrees;
        }

    /**
     * drawtree: create a crude html drawing of the privileges tree
     *
     * We use the data from maketree to create a tree layout
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   array representing an initial node
     * @return  none
     * @throws  none
     * @todo    none
    */

    function drawtree($node)
    {

        $this->html = "\n".'<ul>';
        $this->nodeindex = 0;
        $this->level = 0;
        $this->alreadydone = array();

        $this->drawbranch($node);
        $this->html .= "\n".'</ul>'."\n";
        return $this->html;
    }

    /**
     * drawbranch: draw a branch of the privileges tree
     *
     * This is a recursive function
     * This should be in a template or at least in the xaradmin file, but it's easier here
     *
     * @author  Marc Lutolf <marcinmilan@xaraya.com>
     * @access  private
     * @param   array representing a tree node
     * @return  none
     * @throws  none
     * @todo    none
    */

    function drawbranch($node)
    {
        $this->level = $this->level + 1;
        $this->nodeindex = $this->nodeindex + 1;
        $object = $node['parent'];

    // check if we've aleady processed this entry
        if (in_array($object['pid'],$this->alreadydone)) {
            $drawchildren = false;
            $node['children'] = array();
        }
        else {
            $drawchildren = true;
            array_push($this->alreadydone,$object['pid']);
        }

    // is this a branch?
        $isbranch = count($node['children'])>0 ? true : false;

    // now begin adding rows to the string
        $this->html .= "\n\t".'<li>'."\n\t\t";

    // this table holds the index, the tree drawing gifs and the info about the privilege

    // this next part holds the icon links
        $this->html .= "<span class=\"xar-privtree-icons\">";
    // don't allow deletion of certain privileges
        if(!xarSecurityCheck('DeletePrivilege',0,'Privileges',array($object['name']))) {
            $this->html .= '<img src="' . $this->icon_delete . '" alt="' . xarML('Delete this Privilege') . '" title="' . xarML('Delete this Privilege') . '" class="xar-icon-disabled" />';
        }
        else {
            $this->html .= '<a href="' .
                xarModURL('privileges',
                     'admin',
                     'deleteprivilege',
                     array('pid'=>$object['pid'])) .
                     '" title="'.xarML('Delete this Privilege').'" class="xar-icon">
                        <img src="'. $this->icon_delete .'" alt="' . xarML('Delete this Privilege') . '"/>
                    </a>';
        }

    // offer to show the users/groups this privilege is assigned to
        $this->html .= '<a href="' .
                xarModURL('privileges',
                     'admin',
                     'viewroles',
                     array('pid'=>$object['pid'])) .
                     '" title="'.xarML('Show the Groups/Users this Privilege is assigned to').'" class="xar-icon">
                        <img src="'. $this->icon_groups .'" />
                     </a>';

    // offer to remove this privilege from its parent
        if($object['parentid'] == 0) {
            $this->html .= '<img src="' . $this->icon_remove . '" alt="' . xarML('Remove this privilege from its parent') . '" title="' . xarML('Remove this privilege from its parent') . '" class="xar-icon-disabled" />';
        }
        else {
            $this->html .= '<a href="' .
                    xarModURL('privileges',
                         'admin',
                         'removebranch',
                         array('childid'=> $object['pid'], 'parentid' => $object['parentid'])) .
                         '" title="'.xarML('Remove this privilege from its parent').'" class="xar-icon">
                             <img src="'. $this->icon_remove .'" alt="' . xarML('Remove this privilege from its parent') . '" /></a>';
        }

        $this->html .= "</span>";
    // draw the name of the object and make a link
            $this->html .= ' <a href="' .
                        xarModURL('privileges',
                             'admin',
                             'modifyprivilege',
                             array('pid'=>$object['pid'])) .'" title="'.$object['description'].'">' .$object['name'] . '</a>';
        $componentcount = count($this->privs->getChildren($object['pid']));
        $this->html .= $componentcount > 0 ? "&nbsp;:&nbsp;" .$componentcount . '&nbsp;'.xarML('components') : "";
        $this->html .= "\n\t\t";

    // we've finished this row; now do the children of this privilege
        $this->html .= $isbranch ? '<ul>' : '';
        $ind=0;
        foreach($node['children'] as $subnode){
            $ind = $ind + 1;

    // draw this child
            $this->drawbranch($subnode);

    // we're done
        }
            $this->level = $this->level - 1;

    // write the closing tags
        $this->html .= $isbranch ? '</ul>' : '';
    // close the html row
        $this->html .= "</li>\n";

    }
}
?>

<?php
/**
 * Displayprivilege - display privilege details
 *
 * @package core modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Privileges module
 * @link http://xaraya.com/index.php/release/1098.html
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */
/**
 * displayprivilege - display privilege details
 *
 * @param int pid The id of the privilege to display
 *
 * @return array The array with all details of the privilege to show
 */
function privileges_admin_displayprivilege()
{
// Security Check
    if(!xarSecurityCheck('EditPrivilege')) return;

    if(!xarVarFetch('pid',           'isset', $pid,        NULL, XARVAR_DONT_SET)) {return;}

//Call the Privileges class and get the privilege to be modified
    $privs = new xarPrivileges();
    $priv = $privs->getPrivilege($pid);

//Get the array of parents of this privilege
    $parents = array();
    foreach ($priv->getParents() as $parent) {
        $parents[] = array('parentid'=>$parent->getID(),
                           'parentname'=>$parent->getName());
    }

// Load Template
    if(isset($pid)) {$data['ppid'] = $pid;}
    else {$data['ppid'] = $priv->getID();}

    include_once 'modules/privileges/xartreerenderer.php';
    $renderer = new xarTreeRenderer();

    $data['tree'] = $renderer->drawtree($renderer->maketree($priv));
    $data['pname'] = $priv->getName();
    $data['prealm'] = $priv->getRealm();
    $data['pmodule'] = $priv->getModule();
    $data['pcomponent'] = $priv->getComponent();
    $data['plevel'] = $priv->getLevel();
    $data['pdescription'] = $priv->getDescription();

    $instances = $privs->getinstances($data['pmodule'],$data['pcomponent']);
    $numInstances = count($instances); // count the instances to use in later loops

    $default = array();
    $data['instance'] = $priv->getInstanceDisplay();

    $data['ptype'] = $priv->isEmpty() ? "empty" : "full";
    $data['parents'] = $parents;

    // Set page name
    xarTplSetPageTitle(xarVarPrepForDisplay($data['pname']));
    return $data;
}

?>

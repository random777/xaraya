<?php
/**
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */

/**
 * display an item
 * This is a standard function to provide detailed informtion on a single item
 * available from the module.
 *
 * @param $args an array of arguments (if called by other modules)
 */
function dynamicdata_user_display($args)
{
    extract($args);

    if(!xarVarFetch('objectid', 'isset', $objectid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',     'isset', $name,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('module_id',    'isset', $moduleid,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',   'isset', $itemid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',     'isset', $join,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',    'isset', $table,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template', 'isset', $template,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule','isset', $tplmodule, NULL, XARVAR_DONT_SET)) {return;}

    if (!empty($table)) {
        if(!xarSecurityCheck('AdminDynamicData')) return;
    }

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name' => $name,
                                         'moduleid' => $moduleid,
                                         'itemtype' => $itemtype,
                                         'join'     => $join,
                                         'table'    => $table,
                                         'itemid'   => $itemid,
                                         'tplmodule' => $tplmodule));
    if (!isset($myobject)) return;
    $args = $myobject->toArray();
    $myobject->getItem();

    $data = array();

    // *Now* we can set the data stuff
    $data['object'] =& $myobject;
    $data['objectid'] = $args['objectid'];
    $data['itemid'] = $args['itemid'];

    // Display hooks - not called automatically (yet)
    $myobject->callHooks('display');
    $data['hooks'] = $myobject->hookoutput;

    xarTplSetPageTitle($myobject->label);

    // Return the template variables defined in this function
    if (file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display.xt') ||
        file_exists(sys::code() . 'modules/' . $args['tplmodule'] . '/xartemplates/user-display-' . $args['template'] . '.xt')) {
        return xarTplModule($args['tplmodule'],'user','display',$data,$args['template']);
    } else {
        return xarTplModule('dynamicdata','user','display',$data,$args['template']);
    }
}

?>
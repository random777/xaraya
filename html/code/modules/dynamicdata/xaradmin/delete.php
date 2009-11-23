<?php
/**
 * Delete an item
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage dynamicdata
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * delete item
 * @param 'itemid' the id of the item to be deleted
 * @param 'confirm' confirm that this item can be deleted
 */
function dynamicdata_admin_delete($args)
{
   extract($args);

    if(!xarVarFetch('objectid',   'isset', $objectid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('name',       'isset', $name,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemid',     'id',    $itemid                          )) {return;}
    if(!xarVarFetch('confirm',    'isset', $confirm,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('noconfirm',  'isset', $noconfirm,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('join',       'isset', $join,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('table',      'isset', $table,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('tplmodule',  'isset', $tplmodule,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('template',   'isset', $template,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('return_url', 'isset', $return_url, NULL, XARVAR_DONT_SET)) {return;}

    $myobject = & DataObjectMaster::getObject(array('objectid' => $objectid,
                                         'name'       => $name,
                                         'join'       => $join,
                                         'table'      => $table,
                                         'itemid'     => $itemid,
                                         'tplmodule'  => $tplmodule,
                                         'template'   => $template));
    if (empty($myobject)) return;
    $data = $myobject->toArray();

    // Security check
    if(!xarSecurityCheck('DeleteDynamicDataItem',1,'Item',$data['moduleid'].":".$data['itemtype'].":".$data['itemid'])) return;

    // recover any session var information and remove it from the var
    $data = array_merge($data,xarMod::apiFunc('dynamicdata','user','getcontext',array('module' => $tplmodule)));
    xarSession::setVar('ddcontext.' . $tplmodule, array('tplmodule' => $tplmodule));
    extract($data);

    if (!empty($noconfirm)) {
        if (!empty($return_url)) {
            xarResponse::redirect($return_url);
        } elseif (!empty($table)) {
            xarResponse::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array(
                                            'table'     => $table,
                                            'tplmodule' => $data['tplmodule'],
                                          )));
        } else {
            xarResponse::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                          array(
                                            'itemid'    => $data['objectid'],
                                            'tplmodule' => $data['tplmodule'],
                                          )));
        }
        return true;
    }

    $myobject->getItem();

    if (empty($confirm)) {
        // TODO: is this needed?
        $data = array_merge($data,xarMod::apiFunc('dynamicdata','admin','menu'));
        $data['object'] = & $myobject;
        if ($data['objectid'] == 1) {
            $mylist = & DataObjectMaster::getObjectList(array('objectid' => $data['itemid'], 'extend' => false));
            if (count($mylist->properties) > 0) {
                $data['related'] = xarML('Warning : there are #(1) properties and #(2) items associated with this object !', count($mylist->properties), $mylist->countItems());
            }
        }
        $data['authid'] = xarSecGenAuthKey();

        // if we're editing a dynamic object, check its own visibility
        if ($myobject->objectid == 1 && $myobject->itemid > 3) {
            // CHECKME: do we always need to load the object class to get its visibility ?
            $tmpobject = DataObjectMaster::getObject(array('objectid' => $myobject->itemid));
            // override the default visibility and moduleid
            $myobject->visibility = $tmpobject->visibility;
            $myobject->moduleid = $tmpobject->moduleid;
            unset($tmpobject);
        }

        xarTplSetPageTitle(xarML('Delete Item #(1) in #(2)', $data['itemid'], $myobject->label));

        if (file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-delete.xt') ||
            file_exists(sys::code() . 'modules/' . $data['tplmodule'] . '/xartemplates/admin-delete-' . $data['template'] . '.xt')) {
            return xarTplModule($data['tplmodule'],'admin','delete',$data,$data['template']);
        } else {
            return xarTplModule('dynamicdata','admin','delete',$data,$data['template']);
        }
    }

    // If we get here it means that the user has confirmed the action

    if (!xarSecConfirmAuthKey()) {
        return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
    }        

    // special case for a dynamic object : delete its properties too // TODO: and items
// TODO: extend to any parent-child relation ?
    if ($data['objectid'] == 1) {
        $mylist = & DataObjectMaster::getObjectList(array('objectid' => $data['itemid'], 'extend' => false));
        foreach (array_keys($mylist->properties) as $name) {
            $propid = $mylist->properties[$name]->id;
            $propid = DataPropertyMaster::deleteProperty(array('itemid' => $propid));
        }
    }

    $itemid = $myobject->deleteItem();
    if (!empty($return_url)) {
        xarResponse::redirect($return_url);
    } elseif (!empty($table)) {
        xarResponse::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array(
                                      'table'     => $table,
                                      'tplmodule' => $tplmodule,
                                      )));
    } else {
        xarResponse::redirect(xarModURL('dynamicdata', 'admin', 'view',
                                      array(
                                      'name' => $myobject->name,
                                      'tplmodule' => $tplmodule,
                                      )));
    }

    return true;
}

?>

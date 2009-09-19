<?php
/**
 * Update the dynamic properties for a module + itemtype
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Dynamic Data module
 * @link http://xaraya.com/index.php/release/182.html
 * @author mikespub <mikespub@xaraya.com>
 */
/**
 * Re-order the dynamic properties for a module + itemtype
 *
 * @param int objectid
 * @param int modid
 * @param int itemtype
 * @throws BAD_PARAM
 * @return bool true on success and redirect to modifyprop
 */
function dynamicdata_admin_orderprops()
{
    // Get parameters from whatever input we need.  All arguments to this
    // function should be obtained from xarVarFetch()
    if(!xarVarFetch('objectid',      'isset', $objectid,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modid',         'isset', $modid,          NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype',      'isset', $itemtype,       NULL, XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('move_prop',     'isset', $move_prop,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('direction',     'isset', $direction,      NULL, XARVAR_DONT_SET)) {return;}

    // Confirm authorisation code.
    if (!xarSecConfirmAuthKey()) return;

    if (empty($itemtype)) {
        $itemtype = 0;
    }

    if (!xarModAPILoad('dynamicdata', 'user')) return; // throw back

    $object = xarModAPIFunc('dynamicdata','user','getobjectinfo',
                            array('objectid' => $objectid,
                                  'moduleid' => $modid,
                                  'itemtype' => $itemtype));
    if (isset($object)) {
        $objectid = $object['objectid'];
        $modid = $object['moduleid'];
        $itemtype = $object['itemtype'];
    } elseif (!empty($modid)) {
        $modinfo = xarModGetInfo($modid);
        if (!empty($modinfo['name'])) {
            $name = $modinfo['name'];
            if (!empty($itemtype)) {
                $name .= '_' . $itemtype;
            }
            if (!xarModAPILoad('dynamicdata','admin')) return;
            $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                      array('moduleid' => $modid,
                                            'itemtype' => $itemtype,
                                            'name' => $name,
                                            'label' => ucfirst($name)));
            if (!isset($objectid)) return;
        }
    }
    if (empty($modid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'module id', 'admin', 'updateprop', 'dynamicdata');
        xarErrorSet(XAR_USER_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return $msg;
    }

    $fields = xarModAPIFunc('dynamicdata','user','getprop',
                           array('modid' => $modid,
                                 'itemtype' => $itemtype,
                                 'allprops' => true));
    if (!xarModAPILoad('dynamicdata', 'admin')) return;

    if (isset($fields[$move_prop]) && !empty($direction)) {
        $currentorder   = $fields[$move_prop]['order'];
        $currentid      = $fields[$move_prop]['id'];
        $currenttype    = $fields[$move_prop]['type'];
        $currentlabel   = $fields[$move_prop]['label'];
        foreach ($fields as $name => $field) {
            $orders[] = $name;
        }
        $i = 0;
        // update old fields
        foreach ($fields as $name => $field) {
            if ($field['order'] == $currentorder && $direction == 'up') {
                // previous field
                if (isset($orders[$i-1])) {
                    $swapname = $orders[$i-1];
                    $swaporderid = $fields[$swapname]['id'];
                    $swaptype = $fields[$swapname]['type'];
                    $swaplabel = $fields[$swapname]['label'];
                    $swappos = $i;
                    $currentpos = $i+1;
                }
            } elseif ($field['order'] == $currentorder && $direction == 'down') {
                if (isset($orders[$i+1])) {
                    $swapname = $orders[$i+1];
                    $swaporderid = $fields[$swapname]['id'];
                    $swaptype = $fields[$swapname]['type'];
                    $swaplabel = $fields[$swapname]['label'];
                    $swappos = $i+2;
                    $currentpos = $i+1;
                }
            }
            $i++;
        }
        if (isset($swappos)) {
            if (!xarModAPIFunc('dynamicdata','admin','updateprop',
                              array('prop_id' => $currentid,
                                    'label' => $currentlabel,
                                    'type' => $currenttype,
                                    'order' => $swappos))) {
                return;
            }

            if (!xarModAPIFunc('dynamicdata','admin','updateprop',
                              array('prop_id' => $swaporderid,
                                    'label' => $swaplabel,
                                    'type' => $swaptype,
                                    'order' => $currentpos))) {
                return;
            }
        }
    }
    xarResponseRedirect(xarModURL('dynamicdata', 'admin', 'modifyprop',
                        array('modid'    => $modid,
                              'itemtype' => $itemtype,
        )));

    // Return
    return true;
}

?>

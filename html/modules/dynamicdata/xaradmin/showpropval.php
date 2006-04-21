<?php
/**
 * Show validation of some property
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
 * Show validation of some property
 * @return array
 */
function dynamicdata_admin_showpropval($args)
{
    extract($args);

    // get the property id
    if (!xarVarFetch('itemid',  'id',    $itemid)) {return;}
    if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirm', 'isset', $confirm, NULL, XARVAR_DONT_SET)) {return;}

    // check security
    $modid = xarModGetIDFromName('dynamicdata');
    $itemtype = 1; // dynamic properties
    if (!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$modid:$itemtype:$itemid")) return;

    // get the object corresponding to this dynamic property
    $myobject = & Dynamic_Object_Master::getObject(array('objectid' => 2,
                                                         'itemid'   => $itemid));
    if (empty($myobject)) return;

    $newid = $myobject->getItem();

    if (empty($newid) || empty($myobject->properties['id']->value)) {
        throw new BadParameterException(null,'Invalid item id');
    }

    // check if the module+itemtype this property belongs to is hooked to the uploads module
    $modid = $myobject->properties['moduleid']->value;
    $itemtype = $myobject->properties['itemtype']->value;
    $modinfo = xarModGetInfo($modid);
    if (xarModIsHooked('uploads', $modinfo['name'], $itemtype)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }

    $data = array();
    // get a new property of the right type
    $data['type'] = $myobject->properties['type']->value;
    $id = $myobject->properties['validation']->id;

    $data['name']       = 'dd_'.$id;
    // pass the actual id for the property here
    $data['id']         = $id;
    // pass the original invalid value here
    $data['invalid']    = !empty($invalid) ? $invalid :'';
    $property =& Dynamic_Property_Master::getProperty($data);
    if (empty($property)) return;

    if (!empty($preview) || !empty($confirm)) {
        if (!xarVarFetch($data['name'],'isset',$value,NULL,XARVAR_NOT_REQUIRED)) return;

        // pass the current value as validation rule
        $data['validation'] = isset($value) ? $value : '';

        $isvalid = $property->updateValidation($data);

        if ($isvalid) {
            // store the updated validation rule back in the value
            $myobject->properties['validation']->value = $property->validation;
            if (!empty($confirm)) {
                if (!xarSecConfirmAuthKey()) return;

                $newid = $myobject->updateItem();
                if (empty($newid)) return;

                if (!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
                if (empty($return_url)) {
                    // return to modifyprop
                    $return_url = xarModURL('dynamicdata', 'admin', 'modifyprop',
                                            array('itemid' => $myobject->properties['objectid']->value));
                }
                xarResponseRedirect($return_url);
                return true;
            }
        } else {
            $myobject->properties['validation']->invalid = $property->invalid;
        }
    }

    // pass the id for the input field here
    $data['id']         = 'dd_'.$id;
    $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
    $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
    $data['size']       = !empty($size) ? $size : 50;
    // pass the current value as validation rule
    if (!empty($myobject->properties['validation'])) {
        $value = $myobject->properties['validation']->value;
    } else {
        $value = null;
    }
    $data['validation'] = $value;

    // call its showValidation() method and return
    $data['showval'] = $property->showValidation($data);
    $data['itemid'] = $itemid;
    $data['object'] =& $myobject;

    // Return the template variables defined in this function
    return $data;
}

?>
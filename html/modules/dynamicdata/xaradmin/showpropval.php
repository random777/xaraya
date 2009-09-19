<?php
/**
 * Show configuration of some property
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
 * Show configuration of some property
 * @return array
 */
function dynamicdata_admin_showpropval($args)
{
    extract($args);

    // get the property id
    if (!xarVarFetch('itemid',  'id',    $itemid)) {return;}
    if (!xarVarFetch('exit', 'isset', $exit, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('confirm', 'isset', $confirm, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('preview', 'isset', $preview, NULL, XARVAR_DONT_SET)) {return;}

    // get the object corresponding to this dynamic property
    $myobject = & DynamicData_Object_Master::getObject(array('name'   => 'properties',
                                                    'itemid' => $itemid));
    if (empty($myobject)) return;

    // check security
    $module_id = $myobject->moduleid;
    $itemtype = $myobject->itemtype;
    if (!xarSecurityCheck('EditDynamicDataItem',1,'Item',"$module_id:$itemtype:$itemid")) return;

    $newid = $myobject->getItem();

    if (empty($newid) || empty($myobject->properties['id']->value)) {
        throw new BadParameterException(null,'Invalid item id');
    }

    // check if the module+itemtype this property belongs to is hooked to the uploads module
    /* FIXME: can we do without this hardwiring? Comment out for now
    $module_id = $myobject->properties['module_id']->value;
    $itemtype = $myobject->properties['itemtype']->value;
    $modinfo = xarModGetInfo($module_id);
    if (xarModIsHooked('uploads', $modinfo['name'], $itemtype)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }
    */

    $data = array();
    // get a new property of the right type
    $data['type'] = $myobject->properties['type']->value;
    $id = $myobject->properties['configuration']->id;

    $data['name']       = 'dd_'.$id;
    // pass the actual id for the property here
    $data['id']         = $id;
    // pass the original invalid value here
    $data['invalid']    = !empty($invalid) ? $invalid :'';
    $property =& DynamicData_Property_Master::getProperty($data);
    if (empty($property)) return;

    if (!empty($preview) || !empty($confirm) || !empty($exit)) {
        if (!xarVarFetch($data['name'],'isset',$configuration,NULL,XARVAR_NOT_REQUIRED)) return;

        // pass the current value as configuration rule
        $data['configuration'] = isset($configuration) ? $configuration : '';

        $isvalid = $property->updateConfiguration($data);

        if ($isvalid) {
            if (!empty($confirm) || !empty($exit)) {
                // store the updated configuration rule back in the value
                $myobject->properties['configuration']->value = $property->configuration;
                if (!xarSecConfirmAuthKey()) {
                    return xarTplModule('privileges','user','errors',array('layout' => 'bad_author'));
                }

                $newid = $myobject->updateItem();
                if (empty($newid)) return;

            }
            if (!empty($exit)) {
                if (!xarVarFetch('return_url', 'isset', $return_url,  NULL, XARVAR_DONT_SET)) {return;}
                if (empty($return_url)) {
                    // return to modifyprop
                    $return_url = xarModURL('dynamicdata', 'admin', 'modifyprop',
                                            array('itemid' => $myobject->properties['objectid']->value));
                }
                xarResponse::Redirect($return_url);
                return true;
            }
            // show preview/updated values

        } else {
            $myobject->properties['configuration']->invalid = $property->invalid;
        }        

    // pass the current value as configuration rule
    } elseif (!empty($myobject->properties['configuration'])) {
        $data['configuration'] = $myobject->properties['configuration']->value;

    } else {
        $data['configuration'] = null;
    }

    // pass the id for the input field here
    $data['id']         = 'dd_'.$id;
    $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
    $data['maxlength']  = !empty($maxlength) ? $maxlength : 254;
    $data['size']       = !empty($size) ? $size : 50;

    // call its showConfiguration() method and return
    $data['showval'] = $property->showConfiguration($data);
    $data['itemid'] = $itemid;
    $data['object'] =& $myobject;

    // Return the template variables defined in this function
    return $data;
}

?>

<?php
/**
 * Get the default registraton module and related data if it exists
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * getdefaultregdata  - get the default registration module data
 *
 * @return array  defaultregmodname string, empty if no active registration module
 *                defaultregmodactive boolean, regmodule is active or not
 *
 * @author Jo Dalle Nogare <jojodee@xaraya.com>
 */
function roles_userapi_getdefaultregdata()
{
    $defaultregdata      = array();
    $defaultregmodname   = '';
    $defaultregmodactive = false;
    //get the default reg module if it exits  - it either does or does not
    $defaultregmodid     =(int)xarModGetVar('roles','defaultregmodule');
    
    //if it is not set then use registration module    
    if (!isset($defaultregmodid) || $defaultregmodid<=0) {
        //user Registration if it's there else display appropriate error
        if (xarModIsAvailable('registration')) { 
           $defaultregmodname   = 'registration';
           $defaultregid = xarModGetIDFromName('registration');
        } else {
           $msg = xarML('There is no active registration module installed');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', new DefaultUserException($msg));
                return false;       
        }
    } elseif (isset($defaultregmodid)){
        $defaultregmodname = xarModGetNameFromID($defaultregmodid);
        if (xarModIsAvailable($defaultregmodname)) {
            $defaultregmodname   = $defaultregmodname;
            $defaultregid        = $defaultregmodid;   
        } else {
            if (xarModIsAvailable('registration')) { 
                $defaultregmodname   = 'registration';
                $defaultregid = xarModGetIDFromName('registration');
            } else {
                $msg = xarML('There is no active registration module installed');
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST', new DefaultUserException($msg));
                return false;       
            }       
        }
    } 
    xarModSetVar('roles','defaultregmodule', $defaultregid); //set this in case it hasn't been  
    
    //we have reworked this function - leave the returned array for now and set the $defaultregmodactive
    //the function will return an error previously if not now.
    $defaultregmodactive = true;
    $defaultregdata=array('defaultregmodname'   => $defaultregmodname,
                          'defaultregmodactive' => $defaultregmodactive);

    return $defaultregdata;
}
?>
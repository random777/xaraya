<?php
/**
 * Legacy Functions
 *
 * @package lib
 * @subpackage legacy
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marco Canini
 */

/**
 * Exceptions defined by this subsystem
 */
class ApiDeprecationException extends DeprecationExceptions
{
    protected $message = "You are trying to use a deprecated API function [#(1)], Replace this call with #(2)";
}

/***********************************************************************
* This file is for legacy functions needed to make it
* easier to use modules from Xaraya version 1.x in the version 2 series
*
* Please don't fill it with useless
* stuff except as wrappers, and also.. please
* do not duplicate constants that already exist in xaraya core
***********************************************************************/

/**********************************************************************
* WARNING: THIS FILE IS A WORK IN PROGRESS!!!!!!!!!!!!!!!!!!!
* Please mark all stuff that you need in this file or file a bug report
*
* Necessary functions to duplicate
* MODULE SYSTEM FUNCTIONS

* DEPRECATED XAR FUNCTIONS
* xarInclude                    -> use sys:import('dot.separated.path')
*/

/**
 * Returns the relative path name for the var directory
 *
 * @access public
 * @return string the var directory path name
 * @deprec replaced by sys::varpath()
 * @see    sys
 **/
function xarCoreGetVarDirPath() { return sys::varpath(); }

/**
 * Wrapper functions to support Xaraya 1 API for systemvars
 *
 * @todo this was a protected function by mistake i think
 * @deprec replaced by xarSystemVars
 * @see    xarSystemVars
 **/
function xarCore_getSystemVar($name)
{
    sys::import('xaraya.variables.system');
    return xarSystemVars::get(null, $name);
}

/**
 * Get the database host
 *
 * @deprec
 * @see xarDB::getHost()
 */
function xarDBGetHost() { return xarDB::getHost(); }

/**
 * Get the database name
 *
 * @deprec
 * @see xarDB::getName();
 */
function xarDBGetName() { return xarDB::getName(); }

/*
 * Wrapper functions to support Xaraya 1 API for configvars
 * NOTE: the $prep in the signature has been dropped!!
 */
sys::import('xaraya.variables.config');
function xarConfigSetVar($name, $value) { return xarConfigVars::set(null, $name, $value); }
function xarConfigGetVar($name)         { return xarConfigVars::get(null, $name); }

sys::import('xaraya.variables.module');
sys::import('xaraya.variables.moduser');

/**
 * Wrapper functions to support Xaraya 1 API for modvars and moduservars
**/
function xarModGetVar($modName, $name, $prep = NULL) {   return xarModVars::get($modName, $name, $prep);  }
function xarModSetVar($modName, $name, $value)       {   return xarModVars::set($modName, $name, $value); }
function xarModDelVar($modName, $name)               {   return xarModVars::delete($modName, $name);      }
function xarModDelAllVars($modName)                  {   return xarModVars::delete_all($modName);         }

function xarModGetUserVar($modName, $name, $id = NULL, $prep = NULL){   return xarModUserVars::get($modName, $name, $id, $prep);  }
function xarModSetUserVar($modName, $name, $value, $id=NULL)        {   return xarModUserVars::set($modName, $name, $value, $id); }

// These functions no longer do anything
function xarMakePrivilegeRoot($privilege)        {   return true; }
function xarMakeRoleRoot($name) { return true; }

/**
 * Wrapper functions to support Xaraya 1 API Server functions
 *
**/
function xarServerGetVar($name) { return xarServer::getVar($name); }
function xarServerGetBaseURI()  { return xarServer::getBaseURI();  }
function xarServerGetHost()     { return xarServer::getHost();     }
function xarServerGetProtocol() { return xarServer::getProtocol(); }
function xarServerGetBaseURL()  { return xarServer::getBaseURL();  }
function xarServerGetCurrentURL($args = array(), $generateXMLURL = NULL, $target = NULL) { return xarServer::getCurrentURL($args, $generateXMLURL, $target); }
function xarRequestGetVar($name, $allowOnlyMethod = NULL) { return xarRequest::getVar($name, $allowOnlyMethod);}
function xarRequestGetInfo()                              { return xarRequest::getInfo();        }
function xarRequestIsLocalReferer()                       { return xarRequest::IsLocalReferer(); }
function xarResponseRedirect($redirectURL)                { return xarResponse::Redirect($redirectURL); }


/**
 * Wrapper function to support Xaraya 1 API Database functions
 *
**/
function &xarDBGetConn($index = 0)   { return xarDB::getConn($index);}
function xarDBGetSystemTablePrefix() { return xarDB::getPrefix(); }
function xarDBGetSiteTablePrefix()   { return xarDBGetSystemTablePrefix(); }
function &xarDBGetTables()           { return xarDB::getTables();}
// Does this work?
function xarDBLoadTableMaintenanceAPI() { return sys::import('xaraya.tableddl'); }
function xarDBGetType()              { return xarDB::getType(); }
function &xarDBNewDataDict(Connection &$dbconn, $mode = 'READONLY') 
{
    throw new ApiDeprecationException(array('xarDBNewDataDict','[TO BE DETERMINED]'));
}

/**
 * Legacy function from the cleancore scenario
 *
**/
function xarMakeRoleMemberByName($childName, $parentName)
{
    return xarRoles::makeMemberByName($childName, $parentName);
}
function xarRegisterPrivilege($name,$realm,$module,$component,$instance,$level,$description='')
{
    // Check if the privilege already exists
    $privilege = xarPrivileges::findPrivilege($name);
    if (!$privilege) {
        return xarPrivileges::register($name,$realm,$module,$component,$level,$description);
    }
    return true;
}
function xarMakePrivilegeMember($childName, $parentName)
{
    return xarPrivileges::makeMember($childName, $parentName);
}
function xarAssignPrivilege($privilege,$role)
{
    return xarPrivileges::assign($privilege,$role);
}
function xarRemoveInstances($module)
{
    return xarPrivileges::removeInstances($module);
}
function xarRegisterMask($name,$realm,$module,$component,$instance,$level,$description='')
{
    return xarMasks::register($name,$realm,$module,$component,$level,$description);
}
function xarUnregisterMask($name)
{
    return xarMasks::unregister($name);
}
function xarSecurityLevel($levelname)
{
    return xarMasks::xarSecLevel($levelname);
}
?>
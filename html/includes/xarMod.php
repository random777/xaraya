<?php
/**
 * Module handling subsystem
 *
 * @package modules
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @license GPL <http://www.gnu.org/licenses/gpl.html>
 * @link http://www.xaraya.com
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo Use serialize in module variables?
 */

/**
 * Exceptions defined by this subsystem
 *
 */
class ModuleBaseInfoNotFoundException extends NotFoundExceptions
{
    protected $message = 'The base info for module "#(1)" could not be found';
}

class ModuleNotFoundException extends NotFoundExceptions
{
    protected $message = 'A module is missing, the module name could not be determined in the current context';
}

class ModuleNotActiveException extends xarExceptions
{
    protected $message = 'The module "#(1)" was called, but it is not active.';
}

/**
 * State of modules
 * @todo do we really need 13 module states?
 */
define('XARMOD_STATE_UNINITIALISED', 1);
define('XARMOD_STATE_INACTIVE', 2);
define('XARMOD_STATE_ACTIVE', 3);
define('XARMOD_STATE_MISSING_FROM_UNINITIALISED', 4);
define('XARMOD_STATE_UPGRADED', 5);
// This isn't a module state, but only a convenient definition to indicates,
// where it's used, that we don't care about state, any state is good
define('XARMOD_STATE_ANY', 0);
// <andyv> added an extra superficial state, need it for the list filter because
// some ppl requested not to see modules which are not initialised FR/BUG #252
// now we define 'Installed' state as all except 'uninitialised'
// in fact we dont even need a record as it's an exact reverse of state 1
// tell me if there is something wrong with my (twisted) logic ;-)
define('XARMOD_STATE_INSTALLED', 6);
define('XARMOD_STATE_MISSING_FROM_INACTIVE', 7);
define('XARMOD_STATE_MISSING_FROM_ACTIVE', 8);
define('XARMOD_STATE_MISSING_FROM_UPGRADED', 9);
// Bug 1664 - Add  module states for modules that have a db version
// that is greater than the file version
define('XARMOD_STATE_ERROR_UNINITIALISED', 10);
define('XARMOD_STATE_ERROR_INACTIVE', 11);
define('XARMOD_STATE_ERROR_ACTIVE', 12);
define('XARMOD_STATE_ERROR_UPGRADED', 13);

/**
 * Define the theme here for now as well
 *
 */
define('XARTHEME_STATE_UNINITIALISED', 1);
define('XARTHEME_STATE_INACTIVE', 2);
define('XARTHEME_STATE_ACTIVE', 3);
define('XARTHEME_STATE_MISSING_FROM_UNINITIALISED', 4);
define('XARTHEME_STATE_UPGRADED', 5);
define('XARTHEME_STATE_ANY', 0);
define('XARTHEME_STATE_INSTALLED', 6);
define('XARTHEME_STATE_MISSING_FROM_INACTIVE', 7);
define('XARTHEME_STATE_MISSING_FROM_ACTIVE', 8);
define('XARTHEME_STATE_MISSING_FROM_UPGRADED', 9);

/**
 * Flags for loading APIs
 */
define('XARMOD_LOAD_ONLYACTIVE', 1);
define('XARMOD_LOAD_ANYSTATE', 2);

/*
 * Modules modes
 * @todo get rid of these
 */
define('XARMOD_MODE_SHARED', 1);
define('XARMOD_MODE_PER_SITE', 2);

define('XARTHEME_MODE_SHARED', 1);
define('XARTHEME_MODE_PER_SITE', 2);


/*
 * Used caches are:
 * Mod.Variables
 * Mod.Infos
 * Mod.BaseInfos
 */

/**
 * Start the module subsystem
 *
 * @access protected
 * @global xarMod_generateShortURLs bool
 * @global xarMod_generateXMLURLs bool
 * @param args['generateShortURLs'] bool
 * @param args['generateXMLURLs'] bool
 * @return bool true
 */
function xarMod_init($args, $whatElseIsGoingLoaded)
{
    // generateShortURLs
    $GLOBALS['xarMod_generateShortURLs'] = $args['enableShortURLsSupport'];
    $GLOBALS['xarMod_generateXMLURLs'] = $args['generateXMLURLs'];

    xarEvt_registerEvent('ModLoad');
    xarEvt_registerEvent('ModAPILoad');

    // Modules Support Tables
    $systemPrefix = xarDBGetSystemTablePrefix();

    // How we want it
    $tables['modules']         = $systemPrefix . '_modules';
    $tables['module_vars']     = $systemPrefix . '_module_vars';
    $tables['module_itemvars'] = $systemPrefix . '_module_itemvars';
    $tables['hooks']           = $systemPrefix . '_hooks';
    $tables['themes']          = $systemPrefix . '_themes';

    xarDB::importTables($tables);

    // Subsystem initialized, register a handler to run when the request is over
    //register_shutdown_function ('xarMod__shutdown_handler');
    return true;
}

/**
 * Shutdown handler for the modules subsystem
 *
 * @access private
 */
function xarMod__shutdown_handler()
{
    //xarLogMessage("xarMod shutdown handler");
}

/**
 * Get a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @return mixed The value of the variable or void if variable doesn't exist
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModGetVar($modName, $name, $prep = NULL)
{
    if (empty($modName)) throw new EmptyParameterException('modName');
    return xarVar__GetVarByAlias($modName, $name, $uid = NULL, $prep, 'modvar');
}

/**
 * Set a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @param value The value of the variable
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo  We could delete the item vars for the module with the new value to save space?
 */
function xarModSetVar($modName, $name, $value)
{
    if (empty($modName)) throw new EmptyParameterException('modName');
    return xarVar__SetVarByAlias($modName, $name, $value, $prime = NULL, $description = NULL, $uid = NULL, $type = 'modvar');
}


/**
 * Delete a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo Add caching for item variables?
 */
function xarModDelVar($modName, $name)
{
    if (empty($modName)) throw new EmptyParameterException('modName');
    return xarVar__DelVarByAlias($modName, $name, $uid = NULL, $type = 'modvar');
}

/**
 * Delete all module variables
 *
 * @access public
 * @param modName The name of the module
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo Add caching for item variables?
 */
function xarModDelAllVars($modName)
{
    if(empty($modName)) throw new EmptyParameterException('modName');

    $modBaseInfo = xarMod_getBaseInfo($modName);

        // Takes the right table basing on module mode
    $module_varstable     = $tables['module_vars'];
    $module_itemvarstable = $tables['module_itemvars'];

        // PostGres (allows only one table in DELETE)
        // MySql: multiple table delete only from 4.0 up
        // Select the id's which need to be removed
        $sql="SELECT $module_varstable.xar_id FROM $module_varstable WHERE $module_varstable.xar_modid = ?";
    $stmt = $dbconn->prepareStatement($sql);
    $result = $stmt->executeQuery(array($modBaseInfo['systemid']), ResultSet::FETCHMODE_NUM);

        // Seems that at least mysql and pgsql support the scalar IN operator
        $idlist = array();
    while ($result->next()) {
        $idlist[] = $result->getInt(1);
    }
    $result->close();
    unset($result);

    // We delete the module vars and the user vars in a transaction, which either succeeds completely or totally fails
    try {
        $dbconn->begin();
        if(count($idlist) != 0 ) {
            $bindmarkers = '?' . str_repeat(',?', count($idlist) -1);
            $sql = "DELETE FROM $module_itemvarstable WHERE $module_itemvarstable.xar_mvid IN (".$bindmarkers.")";
            $stmt = $dbconn->prepareStatement($sql);
            $result = $stmt->executeUpdate($idlist);
        }

        // Now delete the module vars
        $query = "DELETE FROM $module_varstable WHERE xar_modid = ?";
        $stmt  = $dbconn->prepareStatement($query);
        $result = $stmt->executeUpdate(array($modBaseInfo['systemid']));
        $dbconn->commit();
    } catch (SQLException $e) {
        // If there was an SQL exception roll back to where we started
        $dbconn->rollback();
        // and raise it again so the handler catches
        // TODO: demote to error? rais other type of exception?
        throw $e;
    }
    return true;
}

/**
 * Get a user variable for a module
 *
 * This is basically the same as xarModSetVar, but this
 * allows for getting variable values which are tied to
 * a specific item for a certain module. Typical usage
 * is storing user preferences.
 *
 * @access public
 * @param modName The name of the module
 * @param name    The name of the variable to get
 * @param uid     User id for which value is to be retrieved
 * @return mixed Teh value of the variable or void if variable doesn't exist.
 * @raise  DATABASE_ERROR, BAD_PARAM (indirect)
 * @see  xarModGetVar
 * @todo Mrb : Add caching?
 */
function xarModGetUserVar($modName, $name, $uid = NULL, $prep = NULL)
{
    // Module name and variable name are necessary
    if (empty($modName)) throw new EmptyParameterException('modName');

    // If uid not specified take the current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // Anonymous user always uses the module default setting
    if ($uid== _XAR_ID_UNREGISTERED) return xarModGetVar($modName,$name);

    return xarVar__GetVarByAlias($modName, $name, $uid, $prep, $type = 'moditemvar');
}

/**
 * Set a user variable for a module
 *
 * This is basically the same as xarModSetVar, but this
 * allows for setting variable values which are tied to
 * a specific user for a certain module. Typical usage
 * is storing user preferences.
 * Only deviations from the module vars are stored.
 *
 * @access public
 * @param modName The name of the module to set a user variable for
 * @param name    The name of the variable to set
 * @param value   Value to set the variable to.
 * @param uid     User id for which value needs to be set
 * @return bool true on success false on failure
 * @raise BAD_PARAM
 * @see xarModSetVar
 * @todo Add caching?
 */
function xarModSetUserVar($modName, $name, $value, $uid=NULL)
{
    // Module name and variable name are necessary
    if (empty($modName)) throw new EmptyParameterException('modName');

    // If no uid specified assume current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // For anonymous users no preference can be set
    // MrB: should we raise an exception here?
    if ($uid == _XAR_ID_UNREGISTERED) return false;

    return xarVar__SetVarByAlias($modName, $name, $value, $prime = NULL, $description = NULL, $uid, $type = 'moditemvar');
}

/**
 * Delete a user variable for a module
 *
 * This is the same as xarModDelVar but this allows
 * for deleting a specific user variable, effectively
 * setting the value for that user to the default setting
 *
 * @access public
 * @param modName The name of the module to set a variable for
 * @param name    The name of the variable to set
 * @param uid     User id of the user to delete the variable for.
 * @return bool true on success
 * @raise BAD_PARAM
 * @see xarModDelVar
 * @todo Add caching?
 */
function xarModDelUserVar($modName, $name, $uid=NULL)
{
    // ModName and name are required
    if (empty($modName)) throw new EmptyParameterException('modName');

    // If uid is not set assume current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // Deleting for anonymous user is useless return true
    // MrB: should we continue, can't harm either and we have
    //      a failsafe that records are deleted, bit dirty, but
    //      it would work.
    if ($uid == _XAR_ID_UNREGISTERED ) return true;

    return xarVar__DelVarByAlias($modName, $name, $uid, $type = 'moditemvar');
}

/**
 * Support function for xarMod*UserVar functions
 *
 * private function which delivers a module user variable
 * id based on the module name and the variable name
 *
 * @access private
 * @param modName The name of the module
 * @param name    The name of the variable
 * @return int id identifier for the variable
 * @raise BAD_PARAM
 * @see xarModSetUserVar, xarModGetUserVar, xarModDelUserVar
*/
function xarModGetVarId($modName, $name)
{
    // Module name and variable name are both necesary
    if (empty($modName) or empty($name)) throw new EmptyParameterException('modName and/or name');

    // Retrieve module info, so we can decide where to look
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

    if (xarCore::isCached('Mod.GetVarID', $modBaseInfo['name'] . $name)) {
        return xarCore::getCached('Mod.GetVarID', $modBaseInfo['name'] . $name);
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    // Takes the right table basing on module mode
    $module_varstable = $tables['module_vars'];

    $query = "SELECT xar_id FROM $module_varstable WHERE xar_modid = ? AND xar_name = ?";
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array((int)$modBaseInfo['systemid'],$name),ResultSet::FETCHMODE_NUM);
    // If there is no such thing, the callee is responsible, return null
    if(!$result->next()) return;

    // Return the ID
    $modvarid = $result->getInt(1);
    $result->Close();

    xarCore::setCached('Mod.GetVarID', $modName . $name, $modvarid);

    return $modvarid;
}


/**
 * Get module registry ID by name
 *
 * @access public
 * @param modName string The name of the module
 * @param type determines theme or module
 * @return string The module registry ID.
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarModGetIDFromName($modName, $type = 'module')
{
    if (empty($modName)) throw new EmptyParameterException('modName');

    // For themes, kinda weird
    $modBaseInfo = xarMod_getBaseInfo($modName,$type);
    if (!isset($modBaseInfo)) return; // throw back
    // MrB: this is confusing
    return $modBaseInfo['regid'];
}

/**
 * Get information on module
 *
 * @access public
 * @param modRegId string module id
 * @param type determines theme or module
 * @return array of module information
 * @raise DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarModGetInfo($modRegId, $type = 'module')
{
    if (empty($modRegId)) throw new EmptyParameterException('modRegid');

    switch($type) {
        case 'module':
            default:
                if (xarCore::isCached('Mod.Infos', $modRegId)) {
                    return xarCore::getCached('Mod.Infos', $modRegId);
            }
            break;
        case 'theme':
            if (xarCore::isCached('Theme.Infos', $modRegId)) {
                return xarCore::getCached('Theme.Infos', $modRegId);
            }
            break;
    }
    // Log it when it doesnt come from the cache
    xarLogMessage("xarModGetInfo ". $modRegId ." / " . $type);

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch($type) {
        case 'module':
        default:
            $the_table = $tables['modules'];
            $query = "SELECT xar_id,
                             xar_name,
                             xar_directory,
                             xar_mode,
                             xar_version,
                             xar_admin_capable,
                             xar_user_capable,
                             xar_state
                       FROM  $the_table WHERE xar_regid = ?";
            break;
        case 'theme':
            $the_table = $tables['themes'];
            $query = "SELECT xar_id,
                             xar_name,
                             xar_directory,
                             xar_mode,
                             xar_version,
                             xar_state
                       FROM  $the_table WHERE xar_regid = ?";
            break;
    }
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery(array($modRegId),ResultSet::FETCHMODE_NUM);

    if (!$result->next()) {
        $result->close();
        throw new IDNotFoundException($modRegId);
    }

    switch($type) {
        case 'module':
        default:
            list($modInfo['systemid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $mode,
                 $modInfo['version'],
                 $modInfo['admincapable'],
                 $modInfo['usercapable'],
                 $modInfo['state']) = $result->getRow();
            break;
        case 'theme':
            list($modInfo['systemid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $mode,
                 $modInfo['version'],
                 $modInfo['state']) = $result->getRow();
            break;
    }
    $result->Close();
    unset($result);

    $modInfo['regid'] = (int) $modRegId;
    $modInfo['mode'] = (int) $mode;
    $modInfo['displayname'] = xarModGetDisplayableName($modInfo['name'], $type);
    $modInfo['displaydescription'] = xarModGetDisplayableDescription($modInfo['name'], $type);

    // Shortcut for os prepared directory
    $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

    switch($type) {
        case 'module':
        default:
            if (!isset($modInfo['state'])) $modInfo['state'] = XARMOD_STATE_MISSING_FROM_UNINITIALISED; //return; // throw back
            $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
            break;
        case 'theme':
            if (!isset($modInfo['state'])) {
                $modInfo['state']= XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
            }
            $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory'], $type = 'theme');
            break;
    }

    if (!isset($modFileInfo)) {
        // We couldn't get file info, fill in unknowns.
        // The exception for this is logged in getFileInfo
        $modFileInfo['class'] = xarML('Unknown');
        $modFileInfo['description'] = xarML('This module is not installed properly. Not all info could be retrieved');
        $modFileInfo['category'] = xarML('Unknown');
        $modFileInfo['displayname'] = xarML('Unknown');
        $modFileInfo['displaydescription'] = xarML('Unknown');
        $modFileInfo['author'] = xarML('Unknown');
        $modFileInfo['contact'] = xarML('Unknown');
        $modFileInfo['admin'] = xarML('Unknown');
        $modFileInfo['user'] = xarML('Unknown');
        $modFileInfo['dependency'] = array();
        $modFileInfo['extensions'] = array();

        $modFileInfo['xar_version'] = xarML('Unknown');
        $modFileInfo['bl_version'] = xarML('Unknown');
        $modFileInfo['class'] = xarML('Unknown');
        $modFileInfo['author'] = xarML('Unknown');
        $modFileInfo['homepage'] = xarML('Unknown');
        $modFileInfo['email'] = xarML('Unknown');
        $modFileInfo['description'] = xarML('Unknown');
        $modFileInfo['contactinfo'] = xarML('Unknown');
        $modFileInfo['publishdate'] = xarML('Unknown');
        $modFileInfo['license'] = xarML('Unknown');
    }

    $modInfo = array_merge($modFileInfo, $modInfo);

    switch($type) {
        case 'module':
        default:
            xarCore::setCached('Mod.Infos', $modRegId, $modInfo);
            break;
        case 'theme':
            xarCore::setCached('Theme.Infos', $modRegId, $modInfo);
            break;
    }

    return $modInfo;
}

function xarModGetNameFromID($regid)
{
    $modinfo = xarModGetInfo($regid);
    return $modinfo['name'];
}


/**
 * Load the modType of module identified by modName.
 *
 * @access private
 * @param modName string - name of module to load
 * @param modType string - type of functions to load
 * @param flags number - flags to modify function behaviour
 * @return mixed
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
 */
function xarModPrivateLoad($modName, $modType, $flags = 0)
{
    static $loadedModuleCache = array();
    if (empty($modName)) throw new EmptyParameterException('modName');

    // Make sure we access the cache with lower case key
    // Q: Why to repeat a functionality already present in the PHP functions?
    // A: because the function may be missing, and we only want to try once.
    if (isset($loadedModuleCache[strtolower($modName . $modType)])) {
        // Already loaded (or tried to) from somewhere else.
        return true;
    }
    // Log it when it doesnt come from the cache
    xarLogMessage("xarModLoad: loading $modName:$modType");

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) throw new ModuleNotFoundException($modName);

    if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE && !($flags & XARMOD_LOAD_ANYSTATE) ) {
        throw new ModuleNotActiveException($modName);
    }

    // TODO: use the xarVarPrepForOS()'d version for $modDir and $modType

    // Load the module files
    $modDir = $modBaseInfo['directory'];

    $fileName = 'modules/'.$modDir.'/xar'.$modType.'.php';

    // Removed the exception.  Causing some wierd results with modules without an api.
    // <nuncanada> But now we wont know if something was loaded or not!
    // <nuncanada> We need some way to find it out.
    if (file_exists($fileName)) {
        xarInclude($fileName);

        // Make sure we access the case with lower case key
        $loadedModuleCache[strtolower($modBaseInfo['name'] . $modType)] = true;
    } elseif (is_dir('modules/'.$modDir.'/xar'.$modType)) {
        // this is OK too - do nothing

        // Make sure we access the case with lower case key
        $loadedModuleCache[strtolower($modBaseInfo['name'] . $modType)] = true;
    } else {
        // this is not OK - we don't have this -> set cache to false

        // Make sure we access the case with lower case key
        $loadedModuleCache[strtolower($modBaseInfo['name'] . $modType)] = false;
    }

    // Load the module translations files (common functions, uncut functions etc.)
    if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modBaseInfo['name'], 'modules:', $modType) === NULL) return;

    // Load database info
    xarMod__loadDbInfo($modBaseInfo['name'], $modDir);

    // Module loaded successfully, trigger the proper event
    xarEvt_trigger('ModLoad', $modBaseInfo['name']);

    return true;
}



/**
 * Load the modType of module identified by modName.
 *
 * @access public
 * @param modName string - name of module to load
 * @param modType string - type of functions to load
 * @return mixed
 * @raise XAR_SYSTEM_EXCEPTION
 */
function xarModLoad($modName, $modType = 'user')
{
    if (!xarCoreIsApiAllowed($modType)) {
        // InputValidationException is more clear here, even though it's not user input.
        throw new BadParameterException(array($modType,$modName), 'The API named: "#(1)" is not allowed for module "#(2)"');
    }
    return xarModPrivateLoad($modName, $modType);
}

/**
 * Load the modType API for module identified by modName.
 *
 * @access public
 * @param modName string registered name of the module
 * @param modType string type of functions to load
 * @return mixed true on success
 * @raise XAR_SYSTEM_EXCEPTION
 */
function xarModAPILoad($modName, $modType = 'user')
{
    if (!xarCoreIsAPIAllowed($modType)) {
        // InputValidationException is more clear here, even though it's not user input.
        throw new BadParameterException(array($modType,$modName), 'The API named: "#(1)" is not allowed for module "#(2)"');
    }

    return xarModPrivateLoad($modName, $modType.'api', XARMOD_LOAD_ANYSTATE);
}

/**
 * Load database definition for a module
 *
 * @param modName name of module to load database definition for
 * @param modDir directory that module is in (if known)
 * @param type determines theme or module
 * @return bool true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarModDBInfoLoad($modName, $modDir = NULL, $type = 'module')
{
    if (empty($modName)) throw new EmptyParameterException('modName');

    // Get the directory if we don't already have it
    if (empty($modDir)) {
        $modBaseInfo = xarMod_getBaseInfo($modName,$type);
        if (!isset($modBaseInfo)) return; // throw back
        $modDir = xarVarPrepForOS($modBaseInfo['directory']);
    } else {
        $modDir = xarVarPrepForOS($modDir);
    }

    switch($type) {
        case 'module':
            default:
            xarMod__loadDbInfo($modBaseInfo['name'], $modDir);
            return true;
            break;
        case 'theme':
            return true;
            break;
    }
}

/**
 * Call a module GUI function.
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function to run
 * @param funcName string specific function to run
 * @param args array
 * @return mixed The output of the function, or raise an exception
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarModFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{
    //xarLogMessage("xarModFunc: begin $modName:$modType:$funcName");

    if (empty($modName)) throw new EmptyParameterException('modName');

    if (!xarCoreIsApiAllowed($modType)) {
        // InputValidationException is more clear here, even though it's not user input.
        throw new BadParameterException(array($modType,$modName), 'The API named: "#(1)" is not allowed for module "#(2)"');
    }
    if (empty($funcName)) throw new EmptyParameterException('modName');

    // good thing this information is cached :)
    $modBaseInfo = xarMod_getBaseInfo($modName);

    // Build function name and call function
    $modFunc = "{$modName}_{$modType}_{$funcName}";
    $found = true;
    $isLoaded = true;
    $msg='';
    if (!function_exists($modFunc)) {
        // attempt to load the module's api
        xarModLoad($modName, $modType);
        // let's check for that function again to be sure
        if (!function_exists($modFunc)) {
            if (!isset($modBaseInfo)) return; // throw back

            $funcFile = 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.'/'.strtolower($funcName).'.php';
            if (!file_exists($funcFile)) {
                $found = false;
            } else {

                ob_start();
                $r = require_once $funcFile;
                $error_msg = strip_tags(ob_get_contents());
                ob_end_clean();

                if (empty($r) || !$r) {
                    $msg = "Could not load function file: [#(1)]\n\nError Caught: #(2)";
                    $params= array($funcFile,$error_msg);
                    $isLoaded = false;
                }

                if (!function_exists($modFunc)) {
                    $found = false;
                }
            }
        }

        if ($found) {
            // Load the translations file - only if we have successfuly loaded the module function.
            if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modBaseInfo['name'], 'modules:'.$modType, $funcName) === NULL) {return;}
        }
    }

    if (!$found) {
        // if it's loaded but not found, then set the error message to that
        if (!$isLoaded || empty($msg)) {
            $msg = 'Module function #(1) does not exist.';
            $params = array($modFunc);
        }
        throw new FunctionNotFoundException($params,$msg);
    }

    $tplData = $modFunc($args);
    if (!is_array($tplData)) {
        return $tplData;
    }

    $templateName = NULL;
    if (isset($tplData['_bl_template'])) {
        $templateName = $tplData['_bl_template'];
    }

    $tplOutput = xarTplModule($modBaseInfo['name'], $modType, $funcName, $tplData, $templateName);

    //xarLogMessage("xarModFunc: end $modName:$modType:$funcName");

    return $tplOutput;
}

/**
 * Call a module API function.
 *
 * Using the modules name, type, func, and optional arguments
 * builds a function name by joining them together
 * and using the optional arguments as parameters
 * like so:
 * Ex: modName_modTypeapi_modFunc($args);
 *
 * @access public
 * @param modName string registered name of module
 * @param modType string type of function to run
 * @param funcName string specific function to run
 * @param args array arguments to pass to the function
 * @param throwException boolean optional flag to throw an exception if the function doesn't exist or not (default = 1)
 * @return mixed The output of the function, or false on failure
 * @raise BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
 */
function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array(), $throwException = 1)
{
    //xarLogMessage("xarModAPIFunc: begin $modName:$modType:$funcName");

    if (empty($modName)) throw new EmptyParameterException('modName');
    if (!xarCoreIsApiAllowed($modType)) {
        // InputValidationException is more clear here, even though it's not user input.
        throw new BadParameterException(array($modType,$modName), 'The API named: "#(1)" is not allowed for module "#(2)"');
    }
    if (empty($funcName)) throw new EmptyParameterException('modName');


    // Build function name and call function
    $modAPIFunc = "{$modName}_{$modType}api_{$funcName}";
    $found = true;
    $isLoaded = true;
    if (!function_exists($modAPIFunc)) {
        // attempt to load the module's api
        xarModAPILoad($modName, $modType, $throwException);
        // let's check for that function again to be sure
        if (!function_exists($modAPIFunc)) {
            // good thing this information is cached :)
            $modBaseInfo = xarMod_getBaseInfo($modName);
            if (!isset($modBaseInfo)) {return;} // throw back

            $funcFile = 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.'api/'.strtolower($funcName).'.php';
            if (!file_exists($funcFile)) {
                $found = false;
            } else {
                ob_start();
                $r = require_once $funcFile;
                $error_msg = strip_tags(ob_get_contents());
                ob_end_clean();

                if (empty($r) || !$r) {
                    $msg = "Could not load function file: [#(1)].\n\n Error Caught:\n #(2)";
                    $params = array($funcFile, $error_msg);
                    $isLoaded = false;
                }

                if (!function_exists($modAPIFunc)) {
                    $found = false;
                }
            }
        }

        if ($found) {
            // Load the translations file.
            // Load only if we have loaded the API function for the first time here.
            if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType.'api', $funcName) === NULL) {return;}
        }
    }

    if (!$found) {
        if ($throwException) {
            if (!$isLoaded || empty($msg)) {
                $msg = 'Module API function #(1) does not exist or could not be loaded.';
                $params = array($modAPIFunc);
            }

            // MrB: When there is a parse error in the api file we sometimes end up
            // here, the error is never shown !!!! (xmlrpc for example)
            // TODO: the isloaded stuff -should- fix the problem above
            //       someone needs to double check this to be sure
            throw new FunctionNotFoundException($params, $msg);
        }
        return;
    }

    $funcResult = $modAPIFunc($args);

    //xarLogMessage("xarModAPIFunc: end $modName:$modType:$funcName");

    return $funcResult;
}

/**
 * Encode parts of a URL.
 * This will encode the path parts, the and GET parameter names
 * and data. It cannot encode a complete URL yet.
 *
 * @access private
 * @param data string the data to be encoded (see todo)
 * @param type string the type of string to be encoded ('getname', 'getvalue', 'path', 'url', 'domain')
 * @return string the encoded URL parts
 * @todo this could be made public
 * @todo support arrays and encode the complete array (keys and values)
 **/
function xarMod__URLencode($data, $type = 'getname')
{
    // Different parts of a URL are encoded in different ways.
    // e.g. a '?' and '/' are allowed in GET parameters, but
    // '?' must be encoded when in a path, and '/' is not
    // allowed in a path at all except as the path-part
    // separators.
    // The aim is to encode as little as possible, so that URLs
    // remain as human-readable as we can allow.

    // We will encode everything first, then restore a select few
    // characters.
    // TODO: tackle it the other way around, i.e. have rules for
    // what to encode, rather than undoing some ecoded characters.
    $data = rawurlencode($data);

    $decode = array(
        'path' => array(
            array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D'),
            array(',', '$', '!', '*', '(', ')', '=')
        ),
        'getname' => array(
            array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D', '%27', '%5B', '%5D'),
            array(',', '$', '!', '*', '(', ')', '=', '\'', '[', ']')
        ),
        'getvalue' => array(
            array('%2C', '%24', '%21', '%2A', '%28', '%29', '%3D', '%27', '%5B', '%5D', '%3A', '%2F', '%3F', '%3D'),
            array(',', '$', '!', '*', '(', ')', '=', '\'', '[', ']', ':', '/', '?', '=')
        )
    );

    // TODO: check what automatic ML settings have on this.
    // I suspect none, as all multi-byte characters have ASCII values
    // of their parts > 127.
    if (isset($decode[$type])) {
        $data = str_replace($decode[$type][0], $decode[$type][1], $data);
    }

    return $data;
}

/**
 * Format GET parameters formed by nested arrays, to support xarModURL().
 * This function will recurse for each level to the arrays.
 *
 * @access private
 * @param args array the array to be expanded as a GET parameter
 * @param prefix string the prefix for the GET parameter
 * @return string the expanded GET parameter(s)
 **/
function xarMod__URLnested($args, $prefix)
{
    $path = '';
    foreach ($args as $key => $arg) {
        if (is_array($arg)) {
            $path .= xarMod__URLnested($arg, $prefix . '['.xarMod__URLencode($key, 'getname').']');
        } else {
            $path .= $prefix . '['.xarMod__URLencode($key, 'getname').']' . '=' . xarMod__URLencode($arg, 'getvalue');
        }
    }

    return $path;
}

/**
 * Add further parameters to the path, ensuring each value is encoded correctly.
 *
 * @access private
 * @param args array the array to be encoded
 * @param path string the current path to append parameters to
 * @param psep string the path seperator to use
 * @return string the path with encoded parameters
 */
function xarMod__URLaddParametersToPath($args, $path, $pini, $psep)
{
    if (count($args) > 0)
    {
        $params = '';

        foreach ($args as $k=>$v) {
            if (is_array($v)) {
                // Recursively walk the array tree to as many levels as necessary
                // e.g. ...&foo[bar][dee][doo]=value&...
                $params .= xarMod__URLnested($v, $psep . $k);
            } elseif (isset($v)) {
                // TODO: rather than rawurlencode, use a xar function to encode
                $params .= (!empty($params) ? $psep : '') . xarMod__URLencode($k, 'getname') . '=' . xarMod__URLencode($v, 'getvalue');
            }
        }

        // Join to the path with the appropriate character,
        // depending on whether there are already GET parameters.
        $path .= (strpos($path, $pini) === FALSE ? $pini : $psep) . $params;
    }

    return $path;
}


/**
 * Generates an URL that reference to a module function.
 *
 * @access public
 * @global xarMod_generateShortURLs bool
 * @global xarMod_generateXMLURLs bool
 * @param modName string registered name of module
 * @param modType string type of function
 * @param funcName string module function
 * @param string fragment document fragment target (e.g. somesite.com/index.php?foo=bar#target)
 * @param args array of arguments to put on the URL
 * @param entrypoint array of arguments for different entrypoint than index.php
 * @return mixed absolute URL for call, or false on failure
 * @todo allow for an alternative entry point (e.g. stream.php) without affecting the other parameters
 */
function xarModURL($modName = NULL, $modType = 'user', $funcName = 'main', $args = array(), $generateXMLURL = NULL, $fragment = NULL, $entrypoint = array())
{
    // Parameter separator and initiator.
    $psep = '&';
    $pini = '?';
    $pathsep = '/';

    // Initialise the path.
    $path = '';

    // The following allows you to modify the BaseModURL from the config file
    // it can be used to configure Xaraya for mod_rewrite by
    // setting BaseModURL = '' in config.system.php
    try {
        $BaseModURL = xarCore_getSystemVar('BaseModURL');
    } catch(VariableNotFoundException $e) {
        $BaseModURL = 'index.php';
    }

    // No module specified - just jump to the home page.
    if (empty($modName)) {
        return xarServerGetBaseURL() . $BaseModURL;
    }

    // Take the global setting for XML format generation, if not specified.
    if (!isset($generateXMLURL)) {
        $generateXMLURL = $GLOBALS['xarMod_generateXMLURLs'];
    }

    // If an entry point has been set, then modify the URL entry point and modType.
    if (!empty($entrypoint)) {
        if (is_array($entrypoint)) {
            $modType = $entrypoint['action'];
            $entrypoint = $entrypoint['entry'];
        }
        $BaseModURL = $entrypoint;
    }

    // If we have an empty argument (ie null => null) then set a flag and
    // remove that element.
    // NOTE: array_key_exists is 30x slower than isset, but we can not use it here
    // FIXME: this begs to be refactored.
    if (is_array($args) && @array_key_exists(NULL, $args) && $args[NULL] === NULL) {
        // This flag means that the GET part of the URL must be opened.
        $open_get_flag = true;
        unset($args[NULL]);
    }

    // Check the global short URL setting before trying to load the URL encoding function
    // for the module. This also applies to custom entry points.
    if ($GLOBALS['xarMod_generateShortURLs']) {
        // The encode_shorturl will be in userapi.
        // Note: if a module declares itself as supporting short URLs, then the encoding
        // API subsequently fails to load, then we want those errors to be raised.
        if ($modType == 'user' && xarModGetVar($modName, 'SupportShortURLs') && xarModAPILoad($modName, $modType)) {
            $encoderArgs = $args;
            $encoderArgs['func'] = $funcName;

            // Execute the short URL function.
            // It must exist if the SupportShortURLs variable is set for the module.
            // FIXME: if the function does not exist, then errors are not handled well, often hidden.
            // Ensure a missing short URL encoding function gets written to the log file.
            $short = xarModAPIFunc($modName, $modType, 'encode_shorturl', $encoderArgs);
            if (!empty($short)) {
                if (is_array($short)) {
                    // An array of path and args has been returned (both optional) - new style.
                    if (!empty($short['path'])) {
                        foreach($short['path'] as $pathpart) {
                            // Use path encoding method, which can differ from
                            // the GET parameter encoding method.
                            if ($pathpart != '') {
                                $path .= $pathsep . xarMod__URLencode($pathpart, 'path');
                            }
                        }
                    }
                    // Unconsumed arguments, to be treated as additional GET parameters.
                    // These may actually be additional GET parameters injected by the
                    // short URL function - it makes no difference either way.
                    if (!empty($short['get']) && is_array($short['get'])) {
                        $path = xarMod__URLaddParametersToPath($short['get'], $path, $pini, $psep);
                    } else {
                        $args = array();
                    }
                } else {
                    // A string URL has been returned - old style - deprecated.
                    $path = $short;
                    $args = array();
                }

                // Use xaraya default (index.php) or BaseModURL if provided in config.system.php
                $path = $BaseModURL . $path;

                // Remove the leading / from the path (if any).
                $path = preg_replace('/^\//', '', $path);

                // Workaround for bug 3603
                // why: template might add extra params we dont see here
                if (!empty($open_get_flag) && !strpos($path, $pini)) {$path .= $pini;}

                // We now have the short form of the URL.
                // Further custom manipulation of the URL can be added here.
                // It may be worthwhile allowing for some kind of hook?
            }
        }
    }

    // If the path is still empty, then there is either no short URL support
    // at all, or no short URL encoding was available for these arguments.
    if (empty($path)) {
        if (!empty($entrypoint)) {
            // Custom entry-point.
            // TODO: allow the alt entry point to work without assuming it is calling
            // ws.php, so retaining the module and type params, and short url.
            // Entry Point comes as an array since ws.php sets a type var.
            // Entry array should be $entrypoint['entry'], $entrypoint['action']
            // e.g. ws.php?type=xmlrpc&args=foo
            // * Can also pass in the 'action' to $modType, and the entry point as
            // a string. It makes sense using existing parameters that way.
            $args = array('type' => $modType) + $args;
        }  else {
            $baseargs = array('module' => $modName);
            if ($modType !== 'user') {
                $baseargs['type'] = $modType;
            }
            if ($funcName !== 'main') {
                $baseargs['func'] = $funcName;
            }
            // Standard entry point - index.php or BaseModURL if provided in config.system.php
            $args = $baseargs + $args;
        }

        // Add GET parameters to the path, ensuring each value is encoded correctly.
        $path = xarMod__URLaddParametersToPath($args, $BaseModURL, $pini, $psep);

        // We have the long form of the URL here.
        // Again, some form of hook may be useful.
    }

    // Add the fragment if required.
    if (isset($fragment)) {
        $path = $path . '#' . urlencode($fragment);
    }

    // Encode the URL if an XML-compatible format is required.
    if ($generateXMLURL) {
        $path = htmlspecialchars($path);
    }

    // Return the URL.
    return xarServerGetBaseURL() . $path;
}


/**
 * Get the displayable name for modName
 *
 * The displayable name is sensible to user language.
 *
 * @access public
 * @param modName string registered name of module
 * @return string the displayable name
 */
function xarModGetDisplayableName($modName = NULL, $type = 'module')
{
    if (empty($modName)) {
        $modName = xarModGetName();
    }
    $modInfo = xarMod_getFileInfo($modName, $type);

    //xarLogMessage("xarModGetDisplayableName ". $modName ." / " . $type);

    return xarML($modInfo['displayname']);
}

/**
 * Get the displayable description for modName
 *
 * The displayable description is sensible to user language.
 *
 * @access public
 * @param modName string registered name of module
 * @return string the displayable description
 */
function xarModGetDisplayableDescription($modName = NULL, $type = 'module')
{
    //xarLogMessage("xarModGetDisplayableDescription ". $modName ." / " . $type);

    if (empty($modName)) {
        $modName = xarModGetName();
    }
    $modInfo = xarMod_getFileInfo($modName, $type);
    return xarML($modInfo['displaydescription']);
}

/**
 * Check if a module is installed and its state is XARMOD_STATE_ACTIVE
 *
 * @access public
 * @static modAvailableCache array
 * @param modName string registered name of module
 * @param type determines theme or module
 * @return mixed true if the module is available
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModIsAvailable($modName, $type = 'module')
{
    //xarLogMessage("xarModIsAvailable: begin $type:$modName");

    // FIXME: there is no point to the cache here, since
    // xarMod_getBaseInfo() caches module details anyway.
    static $modAvailableCache = array();

    if (empty($modName)) throw new EmptyParameterException('modName');

    // Get the real module details.
    // The module details will be cached anyway.
    $modBaseInfo = xarMod_getBaseInfo($modName, $type);

    // Return null if the result wasn't set
    if (!isset($modBaseInfo)) return false; // throw back

    if (!empty($GLOBALS['xarMod_noCacheState']) || !isset($modAvailableCache[$modBaseInfo['name']])) {
        // We should be ok now, return the state of the module
        $modState = $modBaseInfo['state'];
        $modAvailableCache[$modBaseInfo['name']] = false;

        if ($modState == XARMOD_STATE_ACTIVE) {
            $modAvailableCache[$modBaseInfo['name']] = true;
        }
    }
    //xarLogMessage("xarModIsAvailable: end $type:$modName");

    return $modAvailableCache[$modBaseInfo['name']];
}

/**
 * Carry out hook operations for module
 * Some commonly used hooks are :
 *   item - display        (user GUI)
 *   item - transform      (user API)
 *   item - new            (admin GUI)
 *   item - create         (admin API)
 *   item - modify         (admin GUI)
 *   item - update         (admin API)
 *   item - delete         (admin API)
 *   item - search         (user GUI)
 *   item - usermenu       (user GUI)
 *   module - modifyconfig (admin GUI)
 *   module - updateconfig (admin API)
 *   module - remove       (module API)
 *
 * @access public
 * @param hookObject string the object the hook is called for - 'item', 'category', 'module', ...
 * @param hookAction string the action the hook is called for - 'transform', 'display', 'new', 'create', 'delete', ...
 * @param hookId integer the id of the object the hook is called for (module-specific)
 * @param extraInfo mixed extra information for the hook, dependent on hookAction
 * @param callerModName string for what module are we calling this (default = current main module)
 *        Note : better pass the caller module via $extrainfo['module'] if necessary, so that hook functions receive it too
 * @param callerItemType string optional item type for the calling module (default = none)
 *        Note : better pass the item type via $extrainfo['itemtype'] if necessary, so that hook functions receive it too
 * @return mixed output from hooks, or null if there are no hooks
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 * @todo <marco> #1 add BAD_PARAM exception
 * @todo <marco> <mikespub> re-evaluate how GUI / API hooks are handled
 * @todo add itemtype (in extrainfo or as additional parameter)
 */
function xarModCallHooks($hookObject, $hookAction, $hookId, $extraInfo, $callerModName = NULL, $callerItemType = '')
{
    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    throw new BadParameterException('hookObject');
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    throw new BadParameterException('hookAction');


    // allow override of current module if necessary (e.g. modules admin, blocks, API functions, ...)
    if (empty($callerModName)) {
        if (isset($extraInfo) && is_array($extraInfo) && !empty($extraInfo['module'])) {
            $modName = $extraInfo['module'];
        } else {
            list($modName) = xarRequestGetInfo();
            $extraInfo['module'] = $modName;
        }
    } else {
        $modName = $callerModName;
    }
    // retrieve the item type from $extraInfo if necessary (e.g. for articles, xarbb, ...)
    if (empty($callerItemType) && isset($extraInfo) &&
        is_array($extraInfo) && !empty($extraInfo['itemtype'])) {
        $callerItemType = $extraInfo['itemtype'];
    }
    xarLogMessage("xarModCallHooks: getting $hookObject $hookAction hooks for $modName.$callerItemType");
    $hooklist = xarModGetHookList($modName, $hookObject, $hookAction, $callerItemType);

    $output = array();
    $isGUI = false;

    // TODO: #3

    // Call each hook
    foreach ($hooklist as $hook) {
        //THIS IS BROKEN
        //$hook['type'] and $type in the xarModIsAvailable ARE NOT THE SAME THING
//        if (!xarModIsAvailable($hook['module'], $hook['type'])) continue;
        if (!xarModIsAvailable($hook['module'])) continue;
        if ($hook['area'] == 'GUI') {
            $isGUI = true;
            if (!xarModLoad($hook['module'], $hook['type'])) return;
            $res = xarModFunc($hook['module'],
                              $hook['type'],
                              $hook['func'],
                              array('objectid' => $hookId,
                              'extrainfo' => $extraInfo));
            if (!isset($res)) return;
            // Note: hook modules can only register 1 hook per hookObject, hookAction and hookArea
            //       so using the module name as key here is OK (and easier for designers)
            $output[$hook['module']] = $res;
        } else {
            if (!xarModAPILoad($hook['module'], $hook['type'])) return;
            $res = xarModAPIFunc($hook['module'],
                                 $hook['type'],
                                 $hook['func'],
                                 array('objectid' => $hookId,
                                       'extrainfo' => $extraInfo));
            if (!isset($res)) return;
            $extraInfo = $res;
        }
    }

// FIXME: this still returns the wrong output for many of the hook calls, whenever there are no hooks enabled
// Reason : we don't "know" here if the hooks defined by hookObject + hookAction are GUI or API hooks,
//          if we don't get that information from at least one enabled hook. But this is silly, really,
//          because there are *no* cases where you can have the same hookObject + hookAction in 2 different
//          hookAreas (GUI or API).
    if ($isGUI || eregi('^(display|new|modify|search|usermenu|modifyconfig)$',$hookAction)) {
        return $output;
    } else {
        return $extraInfo;
    }
}

/**
 * Get list of available hooks for a particular module, object and action
 *
 * @access private
 * @param callerModName string name of the calling module
 * @param object string the hook object
 * @param action string the hook action
 * @param callerItemType string optional item type for the calling module (default = none)
 * @return array of hook information arrays, or null if database error
 * @raise DATABASE_ERROR
 */
function xarModGetHookList($callerModName, $hookObject, $hookAction, $callerItemType = '')
{
    static $hookListCache = array();

    if (empty($callerModName)) throw new EmptyParameterException('callerModName');

    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    throw new BadParameterException('hookObject');
    //}
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    throw new BadParameterException('hookAction');
    //}

    if (isset($hookListCache["$callerModName$callerItemType$hookObject$hookAction"])) {
        return $hookListCache["$callerModName$callerItemType$hookObject$hookAction"];
    }

    // Get database info
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $hookstable    = $xartable['hooks'];
    $modulestable  = $xartable['modules'];

    // Get applicable hooks
    // New query:
    $query ="SELECT DISTINCT hooks.xar_tarea, tmods.xar_name,
                             hooks.xar_ttype, hooks.xar_tfunc, hooks.xar_order
             FROM $hookstable hooks, $modulestable tmods, $modulestable smods
             WHERE hooks.xar_tmodid = tmods.xar_id AND
                   hooks.xar_smodid = smods.xar_id AND
                   smods.xar_name = ?";
    $bindvars = array($callerModName);

    if (empty($callerItemType)) {
        // Itemtype is not specified, only get the generic hooks
        $query .= " AND hooks.xar_stype = ?";
        $bindvars[] = '';
    } else {
        // hooks can be enabled for all or for a particular item type
        $query .= " AND (hooks.xar_stype = ? OR hooks.xar_stype = ?)";
        $bindvars[] = '';
        $bindvars[] = (string)$callerItemType;
        // FIXME: if itemtype is specified, why get the generic hooks? To save a function call in the modules?
        // Answer: generic hooks apply for *all* itemtypes, so if a caller specifies an itemtype, you
        //         need to check whether hooks are enabled for this particular itemtype or for all
        //         itemtypes here...
    }
    $query .= " AND hooks.xar_object = ? AND hooks.xar_action = ? ORDER BY hooks.xar_order ASC";
    $bindvars[] = $hookObject;
    $bindvars[] = $hookAction;
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_NUM);

    $resarray = array();
    while($result->next()) {
        list($hookArea,
             $hookModName,
             $hookModType,
             $hookFuncName,
             $hookOrder) = $result->getRow();

        $tmparray = array('area' => $hookArea,
                          'module' => $hookModName,
                          'type' => $hookModType,
                          'func' => $hookFuncName);

        array_push($resarray, $tmparray);
    }
    $result->Close();
    $hookListCache["$callerModName$callerItemType$hookObject$hookAction"] = $resarray;
    return $resarray;
}

/**
 * Check if a particular hook module is hooked to the current module (+ itemtype)
 *
 * @access public
 * @static modHookedCache array
 * @param hookModName string name of the hook module we're looking for
 * @param callerModName string name of the calling module (default = current)
 * @param callerItemType string optional item type for the calling module (default = none)
 * @return mixed true if the module is hooked
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarModIsHooked($hookModName, $callerModName = NULL, $callerItemType = '')
{
    static $modHookedCache = array();

    if (empty($hookModName)) throw new EmptyParameterException('hookModName');

    if (empty($callerModName)) {
        list($callerModName) = xarRequestGetInfo();
    }

    // Get all hook modules for the caller module once
    if (!isset($modHookedCache[$callerModName])) {
        // Get database info
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
        $hookstable   = $xartable['hooks'];
        $modulestable = $xartable['modules'];

        // Get applicable hooks
        // New query:
        $query = "SELECT DISTINCT tmods.xar_name, hooks.xar_stype
                  FROM  $hookstable hooks, $modulestable tmods, $modulestable smods
                  WHERE hooks.xar_smodid = smods.xar_id AND
                        hooks.xar_tmodid = tmods.xar_id AND
                        smods.xar_name = ?";
        $bindvars = array($callerModName);

        $result =& $dbconn->Execute($query,$bindvars);

        $modHookedCache[$callerModName] = array();
        while(!$result->EOF) {
            list($modname,$itemtype) = $result->fields;
            if (!empty($itemtype)) {
                $itemtype = trim($itemtype);
            }
            if (!isset($modHookedCache[$callerModName][$itemtype])) {
                $modHookedCache[$callerModName][$itemtype] = array();
            }
            $modHookedCache[$callerModName][$itemtype][$modname] = 1;
            $result->MoveNext();
        }
        $result->Close();
    }

    if (empty($callerItemType)) {
        if (isset($modHookedCache[$callerModName][''][$hookModName])) {
            // generic hook is enabled
            return true;
        } else {
            return false;
        }
    } elseif (is_numeric($callerItemType)) {
        if (isset($modHookedCache[$callerModName][''][$hookModName])) {
            // generic hook is enabled
            return true;
        } elseif (isset($modHookedCache[$callerModName][$callerItemType][$hookModName])) {
            // or itemtype-specific hook is enabled
            return true;
        } else {
            return false;
        }
    } elseif (is_array($callerItemType) && count($callerItemType) > 0) {
        if (isset($modHookedCache[$callerModName][''][$hookModName])) {
            // generic hook is enabled
            return true;
        } else {
            foreach ($callerItemType as $itemtype) {
                if (!is_numeric($itemtype)) continue;
                if (isset($modHookedCache[$callerModName][$itemtype][$hookModName])) {
                    // or at least one of the itemtype-specific hooks is enabled
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Get info from xarversion.php for module specified by modOsDir
 *
 * @access protected
 * @param modOSdir the module's directory
 * @param type determines theme or module
 * @return array an array of module file information
 * @raise MODULE_FILE_NOT_EXIST
 * @todo <marco> #1 FIXME: admin or admin capable?
 */
function xarMod_getFileInfo($modOsDir, $type = 'module')
{
    if (empty($modOsDir)) throw new EmptyParameterException('modOsDir');
    if (empty($GLOBALS['xarMod_noCacheState']) && xarCore::isCached('Mod.getFileInfos', $modOsDir)) {
        return xarCore::getCached('Mod.getFileInfos', $modOsDir);
    }
    // Log it when it didnt came from cache
    xarLogMessage("xarMod_getFileInfo ". $modOsDir ." / " . $type);

    xarLogMessage("xarMod_getFileInfo ". $modOsDir ." / " . $type);

    // TODO redo legacy support via type.
    switch($type) {
        case 'module':
            default:
            // Spliffster, additional mod info from modules/$modDir/xarversion.php
            $fileName = 'modules/' . $modOsDir . '/xarversion.php';

            // If the locale is already present, it means we can make the translations available
            if(!empty($GLOBALS['xarMLS_currentLocale']))
                xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modOsDir, 'modules:', 'version');
            break;
        case 'theme':
            $fileName = xarConfigGetVar('Site.BL.ThemesDirectory'). '/' . $modOsDir . '/xartheme.php';
            break;
    }

    if (!file_exists($fileName)) {
        // Don't raise an exception, it is too harsh, but log it tho (bug 295)
        xarLogMessage("xarMod_getFileInfo: Could not find xarversion.php, skipping $modOsDir");
        // throw new FileNotFoundException($fileName);
        return;
    }

    include($fileName);

    if (!isset($themeinfo)){
        $themeinfo = array();
    }
    if (!isset($modversion)){
        $modversion = array();
    }
    $version = array_merge($themeinfo, $modversion);

    // name and id are required, assert them, otherwise the module is invalid
    assert('isset($version["name"]) && isset($version["id"]); /* Both name and id need to be present in xarversion.php */');
    $FileInfo['name']           = $version['name'];
    $FileInfo['id']             = (int) $version['id'];
    if (isset($version['displayname'])) {
        $FileInfo['displayname'] = $version['displayname'];
    } else {
        $FileInfo['displayname'] = $version['name'];
    }

    $FileInfo['description']    = isset($version['description'])    ? $version['description'] : false;
    if (isset($version['displaydescription'])) {
        $FileInfo['displaydescription'] = $version['displaydescription'];
    } else {
        $FileInfo['displaydescription'] = $FileInfo['description'];
    }
    $FileInfo['admin']          = isset($version['admin'])          ? $version['admin'] : false;
    $FileInfo['admin_capable']  = isset($version['admin'])          ? $version['admin'] : false;
    $FileInfo['user']           = isset($version['user'])           ? $version['user'] : false;
    $FileInfo['user_capable']   = isset($version['user'])           ? $version['user'] : false;
    $FileInfo['securityschema'] = isset($version['securityschema']) ? $version['securityschema'] : false;
    $FileInfo['class']          = isset($version['class'])          ? $version['class'] : false;
    $FileInfo['category']       = isset($version['category'])       ? $version['category'] : false;
    $FileInfo['locale']         = isset($version['locale'])         ? $version['locale'] : 'en_US.iso-8859-1';
    $FileInfo['author']         = isset($version['author'])         ? $version['author'] : false;
    $FileInfo['contact']        = isset($version['contact'])        ? $version['contact'] : false;
    $FileInfo['dependency']     = isset($version['dependency'])     ? $version['dependency'] : array();
    $FileInfo['dependencyinfo'] = isset($version['dependencyinfo']) ? $version['dependencyinfo'] : array();
    $FileInfo['extensions']     = isset($version['extensions'])     ? $version['extensions'] : array();
    $FileInfo['directory']      = isset($version['directory'])      ? $version['directory'] : false;
    $FileInfo['homepage']       = isset($version['homepage'])       ? $version['homepage'] : false;
    $FileInfo['email']          = isset($version['email'])          ? $version['email'] : false;
    $FileInfo['contact_info']   = isset($version['contact_info'])   ? $version['contact_info'] : false;
    $FileInfo['publish_date']   = isset($version['publish_date'])   ? $version['publish_date'] : false;
    $FileInfo['license']        = isset($version['license'])        ? $version['license'] : false;
    $FileInfo['version']        = isset($version['version'])        ? $version['version'] : false;
    // Check that 'xar_version' key exists before assigning
    if (!$FileInfo['version'] && isset($version['xar_version'])) {
        $FileInfo['version'] = $version['xar_version'];
    }
    $FileInfo['bl_version']     = isset($version['bl_version'])     ? $version['bl_version'] : false;

    xarCore::setCached('Mod.getFileInfos', $modOsDir, $FileInfo);

    return $FileInfo;
}

/**
 * Load a module's base information
 *
 * @access protected
 * @param modName string the module's name
 * @param type determines theme or module
 * @return mixed an array of base module info on success
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 */
function xarMod_getBaseInfo($modName, $type = 'module')
{
    if (empty($modName)) throw new EmptyParameterException('modName');

    if ($type != 'module' && $type != 'theme') {
        throw new BadParameterException($type,'The value of the "type" parameter must be "module" or "theme", it was "#(1)"');
    }

    // FIXME: <MrB> I've seen cases where the cache info is not in sync
    // with reality. I've take a couple ones out, but I haven't tested all
    // the way through.
    // <mikespub> There were some issues where you tried to initialize/activate
    // several modules one after the other during the same page request (e.g. at
    // installation), since the state changes of those modules weren't taken
    // into account. The GLOBALS['xarMod_noCacheState'] flag tells Xaraya *not*
    // to cache module (+state) information in that case...

    if ($type == 'module') {
        $cacheCollection = 'Mod.BaseInfos';
        $checkNoState = 'xarMod_noCacheState';
    } else {
        $cacheCollection = 'Theme.BaseInfos';
        $checkNoState = 'xarTheme_noCacheState';
    }

    if (empty($GLOBALS[$checkNoState]) && xarCore::isCached($cacheCollection, $modName)) {
        return xarCore::getCached($cacheCollection, $modName);
    }
    // Log it when it doesnt come from the cache
    xarLogMessage("xarMod_getBaseInfo ". $modName ." / ". $type);

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $table = $tables[$type.'s'];

    $query = "SELECT items.xar_regid, items.xar_directory, items.xar_mode,
                     items.xar_id, items.xar_state, items.xar_name
              FROM   $table items
              WHERE  items.xar_name = ? OR items.xar_directory = ?";
    $bindvars = array($modName, $modName);
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

    if (!$result->next()) {
        $result->Close();
        return;
    }

    $modBaseInfo = array();
    list($regid,  $directory, $mode, $systemid, $state, $name) = $result->getRow();
    $result->Close();

    $modBaseInfo['regid'] = (int) $regid;
    $modBaseInfo['mode'] = (int) $mode;
    $modBaseInfo['systemid'] = (int) $systemid;
    $modBaseInfo['state'] = (int) $state;
    $modBaseInfo['name'] = $name;
    $modBaseInfo['directory'] = $directory;
    $modBaseInfo['displayname'] = xarModGetDisplayableName($directory, $type);
    $modBaseInfo['displaydescription'] = xarModGetDisplayableDescription($directory, $type);
    // Shortcut for os prepared directory
    // TODO: <marco> get rid of it since useless
    $modBaseInfo['osdirectory'] = xarVarPrepForOS($directory);

    // This needed?
    if (empty($modBaseInfo['state'])) {
        $modBaseInfo['state'] = XARMOD_STATE_UNINITIALISED;
    }
    xarCore::setCached($cacheCollection, $name, $modBaseInfo);

    return $modBaseInfo;
}

/**
 * Get all module variables for a particular module
 *
 * @author Michel Dalle
 * @access protected
 * @param modName string
 * @return mixed true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 */
function xarMod_getVarsByModule($modName)
{
    if (empty($modName)) throw new EmptyParameterException('modName');

    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return;

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    // Takes the right table basing on module mode
    $module_varstable = $tables['module_vars'];

    $query = "SELECT xar_name, xar_value FROM $module_varstable WHERE xar_modid = ?";
    $stmt =& $dbconn->prepareStatement($query);
    $result =& $stmt->executeQuery(array($modBaseInfo['systemid']),ResultSet::FETCHMODE_ASSOC);

    while ($result->next()) {
        xarCore::setCached('Mod.Variables.' . $modName, $result->getString('xar_name'), $result->get('xar_value'));
    }
    $result->Close();

    xarCore::setCached('Mod.GetVarsByModule', $modName, true);
    return true;
}


/**
 * Get all module variables with a particular name
 *
 * @author Michel Dalle
 * @access protected
 * @param name string
 * @return mixed true on success
 * @raise DATABASE_ERROR, BAD_PARAM
 * @todo <marco> #1 fetch from site table too ?
 * @todo <mrb> #2 fetch from site table too? (yes i know it's the same, just making you thing twice before changing this)
 */
function xarMod_getVarsByName($varName, $type = 'module')
{
    if (empty($varName)) throw new EmptyParameterException('varName');

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();
    $varstable = $tables['module_vars'];

    switch($type) {
        case 'module':
        default:
            $ownertbl = $tables['modules'];
            break;
        case 'theme':
            $ownertbl = $tables['themes'];
            break;
    }
    $query = "SELECT  owner.xar_name, vars.xar_value
              FROM    $ownertbl owner, $varstable vars
              WHERE   owner.xar_id = vars.xar_modid AND
                      vars.xar_name = ?";

    $stmt =& $dbconn->prepareStatement($query);
    $result =& $stmt->executeQuery(array($varName),ResultSet::FETCHMODE_NUM);

    // Add module variables to cache
    while ($result->next()) {
        // Name is the first field, value the second, cache them like that too.
        xarCore::setCached('Mod.Variables.' . $result->getString(1), $varName, $result->get(2));
    }
    $result->Close();
    switch($type) {
        case 'module':
        default:
            xarCore::setCached('Mod.GetVarsByName', $varName, true);
            break;
        case 'theme':
            xarCore::setCached('Theme.GetVarsByName', $varName, true);
            break;
    }

    return true;
}

/**
 * Load database definition for a module
 *
 * @access private
 * @param modName string name of module to load database definition for
 * @param modOsDir string directory that module is in
 * @return mixed true on success
 * @raise DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarMod__loadDbInfo($modName, $modDir)
{
    static $loadedDbInfoCache = array();

    if (empty($modName)) throw new EmptyParameterException('modName');
    if (empty($modDir))  throw new EmptyParameterException('modDir');


    // Check to ensure we aren't doing this twice
    if (isset($loadedDbInfoCache[$modName])) {
        return true;
    }

    // Load the database definition if required
    $osxartablefile = "modules/$modDir/xartables.php";

    if (!file_exists($osxartablefile)) {
        return false;
    }
    include_once $osxartablefile;

    $tablefunc = $modName . '_' . 'xartables';

    if (function_exists($tablefunc)) {
        xarDB::importTables($tablefunc());
    }

    $loadedDbInfoCache[$modName] = true;

    return true;
}

/**
 * Get the module's current state
 *
 * @access public
 * @param  integer the module's registered id
 * @param modMode integer the module's site mode
 * @param type determines theme or module
 * @return mixed the module's current state
 * @raise DATABASE_ERROR, MODULE_NOT_EXIST
 * @todo implement the xarMod__setState reciproke
 * @todo We dont need this, used nowhere
 */
function xarMod_getState($modRegId, $modMode = XARMOD_MODE_PER_SITE, $type = 'module')
{
    $tmp = xarModGetInfo($modRegid, $type);
    return $tmp['state'];
}

/**
 * register a hook function
 *
 * @access public
 * @param hookObject the hook object
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type
 * @param hookFuncName name of the hook function
 * @return bool true on success
 * @raise DATABASE_ERROR
 */
function xarModRegisterHook($hookObject,
                           $hookAction,
                           $hookArea,
                           $hookModName,
                           $hookModType,
                           $hookFuncName)
{
    // FIXME: <marco> BAD_PARAM?

    // Get database info
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $hookstable = $xartable['hooks'];

    // Insert hook
    try {
        $dbconn->begin();
        // New query: the same but insert the modid's instead of the modnames into tmodule
        $tmodInfo = xarMod_getBaseInfo($hookModName);
        $tmodId = $tmodInfo['systemid'];
        $query = "INSERT INTO $hookstable
                  (xar_id, xar_object, xar_action, xar_tarea, xar_tmodid, xar_ttype, xar_tfunc)
                  VALUES (?,?,?,?,?,?,?)";
        $seqId = $dbconn->GenId($hookstable);
        $bindvars = array($seqId,$hookObject,$hookAction,$hookArea,$tmodId,$hookModType,$hookFuncName);
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeUpdate($bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}

/**
 * unregister a hook function
 *
 * @access public
 * @param hookObject the hook object
 * @param hookAction the hook action
 * @param hookArea the area of the hook (either 'GUI' or 'API')
 * @param hookModName name of the hook module
 * @param hookModType name of the hook type
 * @param hookFuncName name of the hook function
 * @return bool true if the unregister call suceeded, false if it failed
 */
function xarModUnregisterHook($hookObject,
                             $hookAction,
                             $hookArea,
                             $hookModName,
                             $hookModType,
                             $hookFuncName)
{
    // Get database info
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $hookstable = $xartable['hooks'];

    // Remove hook
    try {
        $dbconn->begin();
        // New query: same but test on tmodid instead of tmodname
        $tmodInfo = xarMod_getBaseInfo($hookModName);
        $tmodId = $tmodInfo['systemid'];
        $query = "DELETE FROM $hookstable
                  WHERE xar_object = ?
                  AND xar_action = ? AND xar_tarea = ? AND xar_tmodid = ?
                  AND xar_ttype = ?  AND xar_tfunc = ?";
        $stmt = $dbconn->prepareStatement($query);
        $bindvars = array($hookObject,$hookAction,$hookArea,$tmodId,$hookModType,$hookFuncName);
        $stmt->executeUpdate($bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }
    return true;
}


/**
 * Resolve a module alias
 *
 * This is only a convenience wrapper fot xarRequest function
 *
 * @todo evalutate dependency consequences
*/
function xarModGetAlias($var)
{
    return xarRequest__resolveModuleAlias($var);
}

/**
 * Set an alias for a module
 *
 * @todo evalutate dependency consequences
 *
*/
function xarModSetAlias($alias, $modName)
{
    if (!xarModAPILoad('modules', 'admin')) return;
    $args = array('modName' => $modName, 'aliasModName' => $alias);
    return xarModAPIFunc('modules', 'admin', 'add_module_alias', $args);
}

/**
 * Delete an alias for a module
 * @todo evalutate dependency consequences
 *
*/
function xarModDelAlias($alias, $modName)
{
    if (!xarModAPILoad('modules', 'admin')) return;
    $args = array('modName' => $modName, 'aliasModName' => $alias);
    return xarModAPIFunc('modules', 'admin', 'delete_module_alias', $args);
}

/**
 * get name of current top-level module
 *
 * @access public
 * @return string the name of the current top-level module, false if not in a module
 */
function xarModGetName()
{
    //TODO Work around for the prefix.
    list($modName) = xarRequestGetInfo();
    return $modName;
}

?>

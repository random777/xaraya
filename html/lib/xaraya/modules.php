<?php
/**
 * Module handling subsystem
 *
 * Eventually we want this to split up in multiple files, for reference:
 * current classes in here (disregarding exceptions):
 *      xarModVars
 *      xarModUserVars
 *      xarMod
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Jim McDonald
 * @author Marco Canini <marco@xaraya.com>
 * @author Marcel van der Boom <marcel@xaraya.com>
 * @todo Use serialize in module variables?
 * @todo the double headed theme/module stuff needs to go, a theme is not a module
 */

/**
 * Exceptions defined by this subsystem
 *
 * @package modules
 */
class ModuleBaseInfoNotFoundException extends NotFoundExceptions
{
    protected $message = 'The base info for module "#(1)" could not be found';
}
/**
 * @package modules
**/
class ModuleNotFoundException extends NotFoundExceptions
{
    protected $message = 'A module is missing, the module name could not be determined in the current context';
}
/**
 * @package modules
**/
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

define('XARMOD_STATE_CORE_ERROR_UNINITIALISED', 14);
define('XARMOD_STATE_CORE_ERROR_INACTIVE', 15);
define('XARMOD_STATE_CORE_ERROR_ACTIVE', 16);
define('XARMOD_STATE_CORE_ERROR_UPGRADED', 17);

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
// Theme states for themes which have a block layout requirement
// that is incompatible with current block layout version (added in 1.2.0)
define('XARTHEME_STATE_BL_ERROR_UNINITIALISED', 10);
define('XARTHEME_STATE_BL_ERROR_INACTIVE', 11);
define('XARTHEME_STATE_BL_ERROR_ACTIVE', 12);
define('XARTHEME_STATE_BL_ERROR_UPGRADED', 13);
/**
 * Flags for loading APIs
 */
define('XARMOD_LOAD_ONLYACTIVE', 1);
define('XARMOD_LOAD_ANYSTATE', 2);

/*
 * Modules modes
 */
    //The Shared Mode should not use 2 different tables!!!!!
    //It should add an extra column
    //2 different tables multiplies per 2 the number of sql queries we must make
    //to get the appropriate information
define('XARMOD_MODE_SHARED', 1);
define('XARMOD_MODE_PER_SITE', 2);

define('XARTHEME_MODE_SHARED', 1);
define('XARTHEME_MODE_PER_SITE', 2);


/**
 * Generates an URL that reference to a module function.
 *
 * @access public
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
        $BaseModURL = xarCore_getSystemVar('BaseModURL', true);
    } catch(Exception $e) {
        $BaseModURL = 'index.php';
    }

    // No module specified - just jump to the home page.
    if (empty($modName)) return xarServer::getBaseURL() . $BaseModURL;

    // Take the global setting for XML format generation, if not specified.
    if (!isset($generateXMLURL)) $generateXMLURL = xarMod::$genXmlUrls;

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
    // FIXME: this is way too hacky, NULL as a key for an array sooner or later will fail. (php 4.2.2 ?)
    if (is_array($args) && @array_key_exists(NULL, $args) && $args[NULL] === NULL) {
        // This flag means that the GET part of the URL must be opened.
        $open_get_flag = true;
        unset($args[NULL]);
    }

    // Check the global short URL setting before trying to load the URL encoding function
    // for the module. This also applies to custom entry points.
    if (xarMod::$genShortUrls) {
        // The encode_shorturl will be in userapi.
        // Note: if a module declares itself as supporting short URLs, then the encoding
        // API subsequently fails to load, then we want those errors to be raised.
        if ($modType == 'user' && xarModVars::get($modName, 'enable_short_urls') && xarMod::apiLoad($modName, $modType)) {
            $encoderArgs = $args;
            $encoderArgs['func'] = $funcName;

            // Execute the short URL function.
            // It must exist if the enable_short_urls variable is set for the module.
            // FIXME: if the function does not exist, then errors are not handled well, often hidden.
            // Ensure a missing short URL encoding function gets written to the log file.
            $short = xarMod::apiFunc($modName, $modType, 'encode_shorturl', $encoderArgs);
            if (!empty($short)) {
                if (is_array($short)) {
                    // An array of path and args has been returned (both optional) - new style.
                    if (!empty($short['path'])) {
                        foreach($short['path'] as $pathpart) {
                            // Use path encoding method, which can differ from
                            // the GET parameter encoding method.
                            if ($pathpart != '') {
                                $path .= $pathsep . xarURL::encode($pathpart, 'path');
                            }
                        }
                    }
                    // Unconsumed arguments, to be treated as additional GET parameters.
                    // These may actually be additional GET parameters injected by the
                    // short URL function - it makes no difference either way.
                    if (!empty($short['get']) && is_array($short['get'])) {
                        $path = xarURL::addParametersToPath($short['get'], $path, $pini, $psep);
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
            if ($modType !== 'user')  $baseargs['type'] = $modType;
            if ($funcName !== 'main') $baseargs['func'] = $funcName;

            // Standard entry point - index.php or BaseModURL if provided in config.system.php
            $args = $baseargs + $args;
        }

        // Add GET parameters to the path, ensuring each value is encoded correctly.
        $path = xarURL::addParametersToPath($args, $BaseModURL, $pini, $psep);

        // We have the long form of the URL here.
        // Again, some form of hook may be useful.
    }

    // Add the fragment if required.
    if (isset($fragment)) $path .= '#' . urlencode($fragment);

    // Encode the URL if an XML-compatible format is required.
    if ($generateXMLURL) $path = htmlspecialchars($path);

    // Return the URL.
    return xarServer::getBaseURL() . $path;
}

 /**
 * Wrapper functions to support Xaraya 1 API for module managment
 *
 */
function xarModGetName()
{   return xarMod::getName(); }

function xarModGetNameFromID($regid)
{   return xarMod::getName($regid); }

function xarModGetDisplayableName($modName = NULL, $type = 'module')
{   return xarMod::getDisplayName($modName, $type); }

function xarModGetDisplayableDescription($modName = NULL, $type = 'module')
{   return xarMod::getDisplayDescription($modName,$type); }

function xarModGetIDFromName($modName, $type = 'module')
{   return xarMod::getRegID($modName, $type); }

function xarModIsAvailable($modName, $type = 'module')
{   return xarMod::isAvailable($modName, $type); }

function xarModDBInfoLoad($modName, $modDir = NULL, $type = 'module')
{   return xarMod::loadDbInfo($modName, $modDir, $type); }

function xarModFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{   return xarMod::guiFunc($modName, $modType, $funcName, $args); }

function xarModAPIFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
{   return xarMod::apiFunc($modName, $modType, $funcName, $args,'api'); }

function xarModLoad($modName, $modType = 'user')
{   return xarMod::load($modName, $modType); }

function xarModAPILoad($modName, $modType = 'user')
{   return xarMod::apiLoad($modName, $modType); }

/**
 * Interface declaration for xarMod
 *
 * @todo this is very likely to change, it was created as baseline for refactoring
 */
interface IxarMod
{

}

/**
 * Preliminary class to model xarMod interface
 *
 */
class xarMod extends Object implements IxarMod
{
    static $genShortUrls = false;
    static $genXmlUrls   = true;

    /**
     * Initialize
     *
     */
    static function init($args)
    {
        self::$genShortUrls = $args['enableShortURLsSupport'];
        self::$genXmlUrls   = $args['generateXMLURLs'];

        // Register the events for this subsystem
        xarEvents::register('ModLoad');
        xarEvents::register('ModAPILoad');

        // Modules Support Tables
        $systemPrefix = xarDB::getPrefix();

    // New tables
    $tables = array('modules' => $systemPrefix . '_modules',
                    'system/module_states' => $systemPrefix . '_module_states',
                    'system/module_vars' => $systemPrefix . '_module_vars',
                    'site/module_states' => $systemPrefix . '_module_states',
                    'site/module_vars' => $systemPrefix . '_module_vars',
                    'system/module_uservars' => $systemPrefix . '_module_uservars',
                    'site/module_uservars' => $systemPrefix . '_module_uservars',
                    'themes' => $systemPrefix . '_themes',
                    'system/theme_states' => $systemPrefix . '_theme_states',
                    'system/theme_vars' => $systemPrefix . '_theme_vars',
                    'site/theme_states' => $systemPrefix . '_theme_states',
                    'site/theme_vars' => $systemPrefix . '_theme_vars');

    // JC -- Question are these depreciated?
    // Old tables
    $tables['theme_vars']           = $systemPrefix . '_theme_vars';
    $tables['module_vars']           = $systemPrefix . '_module_vars';
    $tables['module_uservars']       = $systemPrefix . '_module_uservars';
    $tables['hooks']                 = $systemPrefix . '_hooks';

    xarDB_importTables($tables);
        return true;
    }

    /**
     * Get name of a module
     *
     * If regID is passed in, return the name of that module, otherwise use
     * current toplevel module.
     *
     * @access public
     * @param  $regID integer optional regID for module
     * @return string the name of the current top-level module
     */
    static function getName($regID = NULL)
    {
        if(!isset($regID)) {
            list($modName) = xarRequest::getInfo();
        } else {
            $modinfo = xarModGetInfo($regID);
            $modName = $modinfo['name'];
        }
        assert('!empty($modName)');
        return $modName;
    }

    /**
     * Get the displayable name for modName
     *
     * The displayable name is sensible to user language.
     *
     * @access public
     * @param modName string registered name of module
     * @return string the displayable name
     * @todo   re-evaluate this, i think it causes more harm than joy
     */
    static function getDisplayName($modName = NULL, $type = 'module')
    {
        if (empty($modName)) $modName = self::getName();
        $modInfo = xarMod_getFileInfo($modName, $type);
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
    static function getDisplayDescription($modName = NULL, $type = 'module')
    {
        //xarLogMessage("xarMod::getDisplayDescription ". $modName ." / " . $type);
        if (empty($modName)) $modName = self::getName();

        $modInfo = xarMod_getFileInfo($modName, $type);
        return xarML($modInfo['displaydescription']);
    }

    /**
     * Temporary helper function during regid->systemid migration
     *
     * @todo once the migration is done, migrate this out.
     */
    private static function getIds($modName, $type = 'module')
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        // For themes, kinda weird
        $modBaseInfo = xarMod_getBaseInfo($modName,$type);
        if (!isset($modBaseInfo)) return; // throw back
        return array('systemid' => $modBaseInfo['systemid'], 'regid' => $modBaseInfo['regid']);
    }

    /**
     * Get module registry ID by name
     *
     * @access public
     * @param modName string The name of the module
     * @param type determines theme or module
     * @return string The module registry ID.
     */
    static function getRegId($modName, $type = 'module')
    {
        $ids = self::getIds($modName);
        return !is_null($ids['regid']) ? (int)$ids['regid'] : null;
    }

    /**
     * Get module system ID by name
     *
     * @access public
     * @param modName string The name of the module
     * @param type determines theme or module
     * @return string The module registry ID.
     */
    static function getId($modName)
    {
        $ids = self::getIds($modName);
        return $ids['systemid'];
    }

    /**
     * Get the module's current state
     *
     * @access public
     * @param  integer the module's registered id
     * @param type determines theme or module
     * @return mixed the module's current state
     * @throws DATABASE_ERROR, MODULE_NOT_EXIST
     * @todo implement the xarMod__setState reciproke
     * @todo We dont need this, used nowhere
     */
    static function getState($modRegId, $type = 'module')
    {
        $tmp = self::getInfo($modRegId, $type);
        return (int)$tmp['state'];
    }

    /**
     * Check if a module is installed and its state is XARMOD_STATE_ACTIVE
     *
     * @access public
     * @static modAvailableCache array
     * @param modName string registered name of module
     * @param type determines theme or module
     * @return mixed true if the module is available
     * @throws DATABASE_ERROR, BAD_PARAM
     */
    static function isAvailable($modName, $type = 'module')
    {
        //xarLogMessage("xarMod::isAvailable: begin $type:$modName");

        // FIXME: there is no point to the cache here, since
        // xarMod::getBaseInfo() caches module details anyway.
        static $modAvailableCache = array();

        if (empty($modName)) throw new EmptyParameterException('modName');

        // Get the real module details.
        // The module details will be cached anyway.
        $modBaseInfo = xarMod_getBaseInfo($modName, $type);

        // Return false if the result wasn't set
        if (!isset($modBaseInfo)) return false; // throw back

        if (!empty($GLOBALS['xarMod_noCacheState']) || !isset($modAvailableCache[$modBaseInfo['name']])) {
            // We should be ok now, return the state of the module
            $modState = $modBaseInfo['state'];
            $modAvailableCache[$modBaseInfo['name']] = false;

            if ($modState == XARMOD_STATE_ACTIVE) {
                $modAvailableCache[$modBaseInfo['name']] = true;
            }
        }
        //xarLogMessage("xarMod::isAvailable: end $type:$modName");
        return $modAvailableCache[$modBaseInfo['name']];
    }

    /**
     * Get information on module
     *
     * @access public
     * @param modRegId string module id
     * @param type determines theme or module
     * @return array of module information
     * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
     */
    static function getInfo($modRegId, $type = 'module')
    {
        if (empty($modRegId)) throw new EmptyParameterException('modRegid');

        switch($type) {
        case 'module':
            if (xarCoreCache::isCached('Mod.Infos', $modRegId)) {
                return xarCoreCache::getCached('Mod.Infos', $modRegId);
            }
            break;
        case 'theme':
            if (xarCoreCache::isCached('Theme.Infos', $modRegId)) {
                return xarCoreCache::getCached('Theme.Infos', $modRegId);
            }
            break;
        default:
            throw new BadParameterException('module/theme type');
        }
        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::getInfo ". $modRegId ." / " . $type);

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        switch($type) {
        case 'module':
        default:
            $the_table = $tables['modules'];
            $query = "SELECT id,
                             name,
                             directory,
                             version,
                             admin_capable,
                             user_capable,
                             state
                       FROM  $the_table WHERE regid = ?";
            break;
        case 'theme':
            $the_table = $tables['themes'];
            $query = "SELECT id,
                             name,
                             directory,
                             version,
                             state
                       FROM  $the_table WHERE regid = ?";
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
                 $modInfo['version'],
                 $modInfo['admincapable'],
                 $modInfo['usercapable'],
                 $modInfo['state']) = $result->getRow();
            break;
        case 'theme':
            list($modInfo['systemid'],
                 $modInfo['name'],
                 $modInfo['directory'],
                 $modInfo['version'],
                 $modInfo['state']) = $result->getRow();
            break;
        }
        $result->Close();
        unset($result);

        $modInfo['regid'] = (int) $modRegId;
        $modInfo['displayname'] = self::getDisplayName($modInfo['name'], $type);
        $modInfo['displaydescription'] = self::getDisplayDescription($modInfo['name'], $type);
        $modInfo['systemid'] = (int)$modInfo['systemid'];
        $modInfo['state'] = (int)$modInfo['state'];

        // Shortcut for os prepared directory
        $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

        switch($type) {
        case 'module':
        default:
            if (!isset($modInfo['state'])) $modInfo['state'] = XARMOD_STATE_MISSING_FROM_UNINITIALISED; //return; // throw back
            $modFileInfo = self::getFileInfo($modInfo['osdirectory']);
            break;
        case 'theme':
            if (!isset($modInfo['state'])) {
                $modInfo['state']= XARTHEME_STATE_MISSING_FROM_UNINITIALISED;
            }
            $modFileInfo = self::getFileInfo($modInfo['osdirectory'], $type = 'theme');
            break;
        }

        if (!isset($modFileInfo)) {
            // We couldn't get file info, fill in unknowns.
            // The exception for this is logged in getFileInfo
            $unknown = xarML('Unknown');
            $modFileInfo['class'] = $unknown;
            $modFileInfo['description'] = xarML('This module is not installed properly. Not all info could be retrieved');
            $modFileInfo['category'] = $unknown;
            $modFileInfo['displayname'] = $unknown;
            $modFileInfo['displaydescription'] = $unknown;
            $modFileInfo['author'] = $unknown;
            $modFileInfo['contact'] = $unknown;
            $modFileInfo['admin'] = $unknown;
            $modFileInfo['user'] = $unknown;
            $modFileInfo['dependency'] = array();
            $modFileInfo['extensions'] = array();

            $modFileInfo['xar_version'] = $unknown;
            $modFileInfo['bl_version'] = $unknown;
            $modFileInfo['class'] = $unknown;
            $modFileInfo['author'] = $unknown;
            $modFileInfo['homepage'] = $unknown;
            $modFileInfo['email'] = $unknown;
            $modFileInfo['description'] = $unknown;
            $modFileInfo['contactinfo'] = $unknown;
            $modFileInfo['publishdate'] = $unknown;
            $modFileInfo['license'] = $unknown;
        }

        $modInfo = array_merge($modFileInfo, $modInfo);

        switch($type) {
        case 'module':
        default:
            xarCoreCache::setCached('Mod.Infos', $modRegId, $modInfo);
            break;
        case 'theme':
            xarCoreCache::setCached('Theme.Infos', $modRegId, $modInfo);
            break;
        }
        return $modInfo;
    }

    /**
     * Load a module's base information
     *
     * @access public
     * @param modName string the module's name
     * @param type determines theme or module
     * @return mixed an array of base module info on success
     * @throws EmptyParameterException, BadParameterException
     */
    static function getBaseInfo($modName, $type = 'module')
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        if ($type != 'module' && $type != 'theme') {
            throw new BadParameterException($type,'The value of the "type" parameter must be "module" or "theme", it was "#(1)"');
        }

        // The GLOBALS['xarMod_noCacheState'] flag tells Xaraya *not*
        // to cache module (+state) where this would lead to problems
        // like in the installer for example.
        if ($type == 'module') {
            $cacheCollection = 'Mod.BaseInfos';
            $checkNoState = 'xarMod_noCacheState';
        } else {
            $cacheCollection = 'Theme.BaseInfos';
            $checkNoState = 'xarTheme_noCacheState';
        }

        if (empty($GLOBALS[$checkNoState]) && xarCoreCache::isCached($cacheCollection, $modName)) {
            return xarCoreCache::getCached($cacheCollection, $modName);
        }
        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::getBaseInfo ". $modName ." / ". $type);

        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        // theme+s or module+s
        $table = $tables[$type.'s'];

        $query = "SELECT items.regid, items.directory,
                     items.id, items.state, items.name
              FROM   $table items
              WHERE  items.name = ? OR items.directory = ?";
        $bindvars = array($modName, $modName);
        $stmt = $dbconn->prepareStatement($query);
        $result = $stmt->executeQuery($bindvars,ResultSet::FETCHMODE_NUM);

        if (!$result->next()) {
            $result->Close();
            return;
        }

        $modBaseInfo = array();
        list($regid,  $directory, $systemid, $state, $name) = $result->getRow();
        $result->Close();

        $modBaseInfo['regid'] = (int) $regid;
        $modBaseInfo['systemid'] = (int) $systemid;
        $modBaseInfo['version'] = (int) $state;
        $modBaseInfo['state'] = (int) $state;
        $modBaseInfo['name'] = $name;
        $modBaseInfo['directory'] = $directory;
        $modBaseInfo['displayname'] = xarMod::getDisplayName($directory, $type);
        $modBaseInfo['displaydescription'] = xarMod::getDisplayDescription($directory, $type);
        // Shortcut for os prepared directory
        // TODO: <marco> get rid of it since useless
        $modBaseInfo['osdirectory'] = xarVarPrepForOS($directory);

        // This needed?
        if (empty($modBaseInfo['state'])) {
            $modBaseInfo['state'] = XARMOD_STATE_UNINITIALISED;
        }
        xarCoreCache::setCached($cacheCollection, $name, $modBaseInfo);

        return $modBaseInfo;
    }

    /**
     * Get info from xarversion.php for module specified by modOsDir
     *
     * @access protected
     * @param modOSdir the module's directory
     * @param type determines theme or module
     * @return array an array of module file information
     * @throws MODULE_FILE_NOT_EXIST
     * @todo <marco> #1 FIXME: admin or admin capable?
     */
    static function getFileInfo($modOsDir, $type = 'module')
    {
        if (empty($modOsDir)) throw new EmptyParameterException('modOsDir');

        if (empty($GLOBALS['xarMod_noCacheState']) && xarCoreCache::isCached('Mod.getFileInfos', $modOsDir ." / " . $type)) {
            return xarCoreCache::getCached('Mod.getFileInfos', $modOsDir ." / " . $type);
        }
        // Log it when it didnt came from cache
        xarLogMessage("xarMod::getFileInfo ". $modOsDir ." / " . $type);


        // TODO redo legacy support via type.
        switch($type) {
        case 'module':
            // Spliffster, additional mod info from modules/$modDir/xarversion.php
            $fileName = sys::code() . 'modules/' . $modOsDir . '/xarversion.php';
            $part = 'xarversion';
            // If the locale is already present, it means we can make the translations available
            if(!empty($GLOBALS['xarMLS_currentLocale']))
                xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modOsDir, 'modules:', 'version');
            break;
        case 'theme':
            $fileName = xarConfigVars::get(null,'Site.BL.ThemesDirectory') . '/' . $modOsDir . '/xartheme.php';
            $part = 'xartheme';
            break;
        default:
            throw new BadParameterException('module/theme type');
        }

        if (!file_exists($fileName)) {
            // Don't raise an exception, it is too harsh, but log it tho (bug 295)
            xarLogMessage("xarMod::getFileInfo: Could not find xarversion.php, skipping $modOsDir");
            // throw new FileNotFoundException($fileName);
            return;
        }
        // We can NOT use sys::import here, since the xarversion/xartheme files contain variables only
        // If they were loaded earlier, sys::import does nothing (as it should)
        // since inclusion of variables can be done multiple times (they just get overwritten)
        // the include is safe. Ergo: leave this in place.
        include $fileName;

        if (!isset($themeinfo))  $themeinfo = array();
        if (!isset($modversion)) $modversion = array();

        $version = array_merge($themeinfo, $modversion);

        // name and id are required, assert them, otherwise the module is invalid
        assert('isset($version["name"]) && isset($version["id"]); /* Both name and id need to be present in xarversion.php */');
        $FileInfo['name']           = $version['name'];
        $FileInfo['id']             = (int) $version['id'];
        $FileInfo['displayname']    = isset($version['displayname'])    ? $version['displayname'] : $version['name'];
        $FileInfo['description']    = isset($version['description'])    ? $version['description'] : false;
        $FileInfo['displaydescription'] = isset($version['displaydescription']) ? $version['displaydescription'] : $FileInfo['description'];
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

        xarCoreCache::setCached('Mod.getFileInfos', $modOsDir ." / " . $type, $FileInfo);
        return $FileInfo;
    }

    /**
     * Load database definition for a module
     *
     * @access private
     * @param modName string name of module to load database definition for
     * @param modOsDir string directory that module is in
     * @return mixed true on success
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
     *
     * @todo make this private again
     */
    static function loadDbInfo($modName, $modDir = NULL, $type = 'module')
    {
        static $loadedDbInfoCache = array();

        if($type == 'theme') return true; // sigh.

        if (empty($modName)) throw new EmptyParameterException('modName');

        // Check to ensure we aren't doing this twice
        if (isset($loadedDbInfoCache[$modName])) return true;

        // Get the directory if we don't already have it
        if (empty($modDir)) {
            $modBaseInfo = self::getBaseInfo($modName,$type);
            if (!isset($modBaseInfo)) return; // throw back
            $modDir = xarVarPrepForOS($modBaseInfo['directory']);
        } else {
            $modDir = xarVarPrepForOS($modDir);
        }

        // For base and modules, which don't have a xartables - CHECKME: why not again ?
        if (!file_exists(sys::code() . 'modules/' . $modDir . '/xartables.php')) {
            // set anyway, so we don't try over and over
            $loadedDbInfoCache[$modName] = false;
            return false;
        }

        // Load the database definition if required
        try {
            sys::import('modules.'.$modDir.'.xartables');
        } catch (Exception $e) {
            // set anyway, so we don't try over and over
            $loadedDbInfoCache[$modName] = false;
            return false;
        }

        $tablefunc = $modName . '_' . 'xartables';
        if (function_exists($tablefunc)) xarDB_importTables($tablefunc());

        $loadedDbInfoCache[$modName] = true;
        return true;
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
     * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
     */
    static function guiFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
    {
        if (empty($modName)) throw new EmptyParameterException('modName');

        // Get a cache key for this module function if it's suitable for module caching
        $cacheKey = xarCache::getModuleKey($modName, $modType, $funcName, $args);

        // Check if the module function is cached
        if (!empty($cacheKey) && xarModuleCache::isCached($cacheKey)) {
            // Return the cached module function output
            return xarModuleCache::getCached($cacheKey);
        }

        $tplData = self::callFunc($modName,$modType,$funcName,$args);

        // If we have a string of data, we assume someone else did xarTpl* for us
        if (!is_array($tplData)) {
            // Set the output of the module function in cache
            if (!empty($cacheKey)) {
                xarModuleCache::setCached($cacheKey, $tplData);
            }
            return $tplData;
        }

        // See if we have a special template to apply
        $templateName = NULL;
        if (isset($tplData['_bl_template'])) $templateName = $tplData['_bl_template'];

        // Create the output.
        $tplOutput = xarTplModule($modName, $modType, $funcName, $tplData, $templateName);

        // Set the output of the module function in cache
        if (!empty($cacheKey)) {
            xarModuleCache::setCached($cacheKey, $tplOutput);
        }

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
     * @return mixed The output of the function, or false on failure
     * @throws BAD_PARAM, MODULE_FUNCTION_NOT_EXIST
     */
    static function apiFunc($modName, $modType = 'user', $funcName = 'main', $args = array())
    {
        if (empty($modName)) throw new EmptyParameterException('modName');
        return self::callfunc($modName, $modType, $funcName, $args,'api');
    }

    /**
     * Work horse method for the lazy calling of module functions
     *
     * @access private
     */
    private static function callFunc($modName,$modType,$funcName,$args,$funcType = '')
    {
        assert('($funcType == "api" or $funcType==""); /* Wrong funcType argument in private callFunc method */');
        if (empty($modName) || empty($funcName)) {
            // This is not a valid function syntax - CHECKME: also for api functions ?
            return xarResponse::NotFound();
        }

        // good thing this information is cached :)
        $modBaseInfo = xarMod_getBaseInfo($modName);
        if (!isset($modBaseInfo)) {
            // This is not a valid module - CHECKME: also for api functions ?
            return xarResponse::NotFound();
        }

        // Build function name and call function
        $modFunc = "{$modName}_{$modType}{$funcType}_{$funcName}";
        $found = true;
        $isLoaded = true;
        $msg = '';
        if (!function_exists($modFunc)) {
            // attempt to load the module's api
            if ($funcType == 'api') {
                xarMod::apiLoad($modName, $modType);
            } else {
                xarMod::load($modName,$modType);
            }
            // let's check for that function again to be sure
            if (!function_exists($modFunc)) {
                // Q: who are we kidding with this? osdirectory == modName always, no?
                $funcFile = sys::code() . 'modules/'.$modBaseInfo['osdirectory'].'/xar'.$modType.$funcType.'/'.strtolower($funcName).'.php';
                if (!file_exists($funcFile)) {
                    // Valid syntax, but the function doesn't exist
                    if ($funcType == 'api') {
                        // throw an exception below for unknown api functions - catch later if you want
                        $found = false;
                    } else {
                        // fatal error for unknown gui functions !?
                        return xarResponse::NotFound();
                    }
                } else {
                    ob_start();
                    sys::import('modules.'.$modName.'.xar'.$modType.$funcType.'.'.strtolower($funcName));
                    $error_msg = strip_tags(ob_get_contents());
                    ob_end_clean();

                    if (empty($r) || !$r) {
                        $msg = "Could not load function file: [#(1)].\n\n Error Caught:\n #(2)";
                        $params = array($funcFile, $error_msg);
                        $isLoaded = false;
                    }
                    if (!function_exists($modFunc)) $found = false;
                }
            }

            if ($found) {
                // Load the translations file, only if we have loaded the API function for the first time here.
                if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:'.$modType.$funcType, $funcName) === NULL) {return;}
            }
        }

        if (!$found) {
            if (!$isLoaded || empty($msg)) {
                // if it's loaded but not found, then set the error message to that
                $msg = 'Module '. strtoupper($funcType) .' function #(1) does not exist or could not be loaded.';
                $params = array($modFunc);
            }
            throw new FunctionNotFoundException($params, $msg);
        }

        $funcResult = $modFunc($args);
        return $funcResult;
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @access public
     * @param modName string - name of module to load
     * @param modType string - type of functions to load
     * @return mixed
     * @throws XAR_SYSTEM_EXCEPTION
     */
    static function load($modName, $modType = 'user')
    {
        return self::privateLoad($modName, $modType);
    }

    /**
     * Load the modType API for module identified by modName.
     *
     * @access public
     * @param modName string registered name of the module
     * @param modType string type of functions to load
     * @return mixed true on success
     * @throws XAR_SYSTEM_EXCEPTION
     */
    static function apiLoad($modName, $modType = 'user')
    {
        return self::privateLoad($modName, $modType.'api', XARMOD_LOAD_ANYSTATE);
    }

    /**
     * Load the modType of module identified by modName.
     *
     * @access private
     * @param modName string - name of module to load
     * @param modType string - type of functions to load
     * @param flags number - flags to modify function behaviour
     * @return mixed
     * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_NOT_ACTIVE
     */
    static private function privateLoad($modName, $modType, $flags = 0)
    {
        static $loadedModuleCache = array();
        if (empty($modName)) throw new EmptyParameterException('modName');

        // Make sure we access the cache with lower case key, return true when we already loaded
        $cacheKey = strtolower($modName.$modType);
        if (isset($loadedModuleCache[$cacheKey])) return true;

        // Log it when it doesnt come from the cache
        xarLogMessage("xarMod::load: loading $modName:$modType");

        $modBaseInfo = xarMod_getBaseInfo($modName);
        // Not a valid module - throw exception
        if (!isset($modBaseInfo)) throw new ModuleNotFoundException($modName);

        // Not a valid module state - throw exception
        if ($modBaseInfo['state'] != XARMOD_STATE_ACTIVE && !($flags & XARMOD_LOAD_ANYSTATE) ) {
            throw new ModuleNotActiveException($modName);
        }
        
        // Not the correct version - throw exception unless we are upgrading
        // FIXME: reinstate this
//        if (!self::checkVersion($modName) && !xarVarGetCached('Upgrade', 'upgrading')) {
//            die('The core module "' . $modName . '" does not have the correct version. Please run the upgrade routine by clicking <a href="upgrade.php">here</a>');
//        }
        
        // Load the module files
        $modDir = $modBaseInfo['directory'];
        $fileName = sys::code() . 'modules/'.$modDir.'/xar'.$modType.'.php';

        // Removed the exception.  Causing some wierd results with modules without an api.
        // <nuncanada> But now we wont know if something was loaded or not!
        // <nuncanada> We need some way to find it out.
        // Assume failure
        if (file_exists($fileName)) {
            sys::import('modules.'.$modDir.'.xar'.$modType);
            $loadedModuleCache[$cacheKey] = true;
        } elseif (is_dir(sys::code() . 'modules/'.$modDir.'/xar'.$modType)) {
            // this is OK too - do nothing
            $loadedModuleCache[$cacheKey] = true;
        } else {
            // this is (not really) OK too - do nothing
            $loadedModuleCache[$cacheKey] = false;
        }

        // Load the module translations files (common functions, uncut functions etc.)
        if (xarMLS_loadTranslations(XARMLS_DNTYPE_MODULE, $modName, 'modules:', $modType) === NULL) return;

        // Load database info
        self::loadDbInfo($modName, $modDir);

        // Module loaded successfully, trigger the proper event
        xarEvents::trigger('ModLoad', $modName);
        return true;
    }

    /**
     * Check the version of this moduleagainst the core version
     *
     * @return boolean
     */
    public static function checkVersion($modName)
    {
        $modInfo = xarModgetInfo(self::getRegId($modName));
        if ((strpos($modInfo['class'], 'Core') !== false)) {
            return $modInfo['version'] == XARCORE_VERSION_NUM;
        } else {
            // Add check for non core modules here
            return true;
        }
    }
    
    /**
     * Check if a particular module function exists, or default back to 'dynamicdata'
     *
     * @return string tplmodule or 'dynamicdata'
     */
    static function checkModuleFunction($tplmodule = 'dynamicdata', $type = 'user', $func = 'display', $defaultmodule = 'dynamicdata')
    {
        static $tplmodule_cache = array();

        $key = "$tplmodule:$type:$func";
        if (!isset($tplmodule_cache[$key])) {
            $file = sys::code() . 'modules/' . $tplmodule . '/xar' . $type . '/' . $func . '.php';
            if (file_exists($file)) {
                $tplmodule_cache[$key] = $tplmodule;
            } else {
                $tplmodule_cache[$key] = $defaultmodule;
            }
        }
        return $tplmodule_cache[$key];
    }

    /**
     * Check access for a specific action on module level (see also xarObject and xarBlock)
     *
     * @access public
     * @param moduleName string the module we want to check access for
     * @param action string the action we want to take on this module (view/admin) // CHECKME: any others we really use on module level ?
     * @param roleid mixed override the current user or null
     * @return bool true if access
     */
    static function checkAccess($moduleName, $action, $roleid = null)
    {
        // TODO: get module variable with access config: groups, masks, levels or whatever

        // TODO: check for access e.g. by group

        // Fall back on mask-less security check with access levels corresponding to action
        sys::import('modules.privileges.class.securitylevel');

        // default actions supported on modules
        switch($action)
        {
            case 'admin':
                $seclevel = SecurityLevel::ADMIN;
                break;

        // CHECKME: any others we really use on module level (instead of object/item/block/... level) ?

            case 'view':
                $seclevel = SecurityLevel::OVERVIEW;
                break;

            default:
                throw new BadParameterException('action', "Supported actions on module level are 'view' and 'admin'");
                break;
        }

        if (!empty($roleid)) {
            $role = xarRoles::get($roleid);
            $rolename = $role->getName();
            return xarSecurity::check('',0,'All','All',$moduleName,$rolename,0,$seclevel);
        } else {
            return xarSecurity::check('',0,'All','All',$moduleName,'',0,$seclevel);
        }
    }
}

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
function xarMod_init(&$args, $whatElseIsGoingLoaded)
{
    // generateShortURLs
    $GLOBALS['xarMod_generateShortURLs'] = $args['enableShortURLsSupport'];
    $GLOBALS['xarMod_generateXMLURLs'] = $args['generateXMLURLs'];

    xarEvt_registerEvent('ModLoad');
    xarEvt_registerEvent('ModAPILoad');

    // Modules Support Tables
    $systemPrefix = xarDBGetSystemTablePrefix();
    $sitePrefix = xarDBGetSiteTablePrefix();

    // New tables
    $tables = array('modules' => $systemPrefix . '_modules',
                    'system/module_states' => $systemPrefix . '_module_states',
                    'system/module_vars' => $systemPrefix . '_module_vars',
                    'site/module_states' => $sitePrefix . '_module_states',
                    'site/module_vars' => $sitePrefix . '_module_vars',
                    'system/module_uservars' => $systemPrefix . '_module_uservars',
                    'site/module_uservars' => $sitePrefix . '_module_uservars',
                    'themes' => $systemPrefix . '_themes',
                    'system/theme_states' => $systemPrefix . '_theme_states',
                    'system/theme_vars' => $systemPrefix . '_theme_vars',
                    'site/theme_states' => $sitePrefix . '_theme_states',
                    'site/theme_vars' => $sitePrefix . '_theme_vars');

    // JC -- Question are these depreciated?
    // Old tables
    $tables['theme_vars']           = $systemPrefix . '_theme_vars';
    $tables['module_vars']           = $systemPrefix . '_module_vars';
    $tables['module_uservars']       = $systemPrefix . '_module_uservars';
    $tables['hooks']                 = $systemPrefix . '_hooks';

    xarDB_importTables($tables);

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
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarModGetVar($modName, $name, $prep = NULL)
{
    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

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
 * @throws DATABASE_ERROR, BAD_PARAM
 * @todo  We could delete the user vars for the module with the new value to save space?
 */
function xarModSetVar($modName, $name, $value)
{
    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    return xarVar__SetVarByAlias($modName, $name, $value, $prime = NULL, $description = NULL, $uid = NULL, $type = 'modvar');
}


/**
 * Delete a module variable
 *
 * @access public
 * @param modName The name of the module
 * @param name The name of the variable
 * @return bool true on success
 * @throws DATABASE_ERROR, BAD_PARAM
 * @todo Add caching for user variables?
 */
function xarModDelVar($modName, $name)
{
    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    return xarVar__DelVarByAlias($modName, $name, $uid = NULL, $type = 'modvar');
}

/**
 * Delete all module variables
 *
 * @access public
 * @param modName The name of the module
 * @return bool true on success
 * @throws DATABASE_ERROR, BAD_PARAM
 * @todo Add caching for user variables?
 */
function xarModDelAllVars($modName)
{
    if(empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    $modBaseInfo = xarMod_getBaseInfo($modName);
    //if (!isset($modBaseInfo)) return; // throw back
    if (isset($modBaseInfo)) { //only continue if the module info exists
        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        // Takes the right table basing on module mode
        if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
            $module_varstable = $tables['system/module_vars'];
            $module_uservarstable = $tables['system/module_uservars'];
        } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
            $module_varstable = $tables['site/module_vars'];
            $module_uservarstable = $tables['site/module_uservars'];
        }

        // PostGres (allows only one table in DELETE)
        // MySql: multiple table delete only from 4.0 up
        // Select the id's which need to be removed
        $sql="SELECT $module_varstable.xar_id FROM $module_varstable WHERE $module_varstable.xar_modid = ?";
        $result =& $dbconn->Execute($sql, array($modBaseInfo['systemid']));
        if(!$result) return;

        // Seems that at least mysql and pgsql support the scalar IN operator
        $idlist = array();
        while (!$result->EOF) {
            list($id) = $result->fields;
            $result->MoveNext();
            $idlist[] = (int) $id;
        }

        if (count($idlist) != 0) {
            $idlist = join(', ', $idlist);
            // CHECKME: can bind variables be used here?
            $sql = "DELETE FROM $module_uservarstable WHERE $module_uservarstable.xar_mvid IN (".$idlist.")";
            $result =& $dbconn->Execute($sql);
            if (!$result) return;
            $result->Close();
        }

        // Now delete the module vars
        $query = 'DELETE FROM '.$module_varstable.' WHERE xar_modid = ?';
        $result =& $dbconn->Execute($query, array($modBaseInfo['systemid']));
        if (!$result) return;
    }
    return true;
}

/**
 * Get a user variable for a module
 *
 * This is basically the same as xarModSetVar, but this
 * allows for getting variable values which are tied to
 * a specific user for a certain module. Typical usage
 * is storing user preferences.
 *
 * @access public
 * @param modName The name of the module
 * @param name    The name of the variable to get
 * @param uid     User id for which value is to be retrieved
 * @return mixed Teh value of the variable or void if variable doesn't exist.
 * @throws  DATABASE_ERROR, BAD_PARAM (indirect)
 * @see  xarModGetVar
 * @todo Mrb : Add caching?
 */
function xarModGetUserVar($modName, $name, $uid = NULL, $prep = NULL)
{
    // Module name and variable name are necessary
    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // If uid not specified take the current user
    if ($uid == NULL) $uid=xarUserGetVar('uid');

    // Anonymous user always uses the module default setting
    if ($uid==_XAR_ID_UNREGISTERED) return xarModGetVar($modName,$name);

    return xarVar__GetVarByAlias($modName, $name, $uid, $prep, $type = 'moduservar');


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
 * @throws BAD_PARAM
 * @see xarModSetVar
 * @todo Add caching?
 */
function xarModSetUserVar($modName, $name, $value, $uid=NULL)
{
    // Module name and variable name are necessary
    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // If no uid specified assume current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // For anonymous users no preference can be set
    // MrB: should we raise an exception here?
    if ($uid==_XAR_ID_UNREGISTERED) return false;

    return xarVar__SetVarByAlias($modName, $name, $value, $prime = NULL, $description = NULL, $uid, $type = 'moduservar');
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
 * @throws BAD_PARAM
 * @see xarModDelVar
 * @todo Add caching?
 */
function xarModDelUserVar($modName, $name, $uid=NULL)
{
    // ModName and name are required
    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }

    // If uid is not set assume current user
    if ($uid == NULL) $uid = xarUserGetVar('uid');

    // Deleting for anonymous user is useless return true
    // MrB: should we continue, can't harm either and we have
    //      a failsafe that records are deleted, bit dirty, but
    //      it would work.
    if ($uid == 0 ) return true;

    return xarVar__DelVarByAlias($modName, $name, $uid, $type = 'moduservar');
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
 * @throws BAD_PARAM
 * @see xarModSetUserVar, xarModGetUserVar, xarModDelUserVar
*/
function xarModGetVarId($modName, $name)
{
    // Module name and variable name are both necesary
    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($name)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'name');
        return;
    }

    // Retrieve module info, so we can decide where to look
    $modBaseInfo = xarMod_getBaseInfo($modName);
    if (!isset($modBaseInfo)) return; // throw back

    if (xarCore_IsCached('Mod.GetVarID', $modBaseInfo['name'] . $name)) {
        return xarCore_GetCached('Mod.GetVarID', $modBaseInfo['name'] . $name);
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    // Takes the right table basing on module mode
    if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
        $module_varstable = $tables['system/module_vars'];
    } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
        $module_varstable = $tables['site/module_vars'];
    }

    $query = 'SELECT xar_id FROM '.$module_varstable.' WHERE xar_modid = ? AND xar_name = ?';
    $result =& $dbconn->Execute($query, array((int)$modBaseInfo['systemid'], $name));

    if(!$result) return;

    if ($result->EOF) {
        return;
    }
    list($modvarid) = $result->fields;
    $result->Close();

    $modvarid = (int) $modvarid;
    xarCore_SetCached('Mod.GetVarID', $modBaseInfo['name'] . $name, $modvarid);

    return $modvarid;
}



/**
 * Get information on a module
 *
 * @access public
 * @param modRegId string module id
 * @param type determines theme or module
 * @return array of module information
 * @throws DATABASE_ERROR, BAD_PARAM, ID_NOT_EXIST
 */
function xarModGetInfo($modRegId, $type = 'module')
{
    if (empty($modRegId) || $modRegId == 0) {
        $msg = xarML('Empty RegId (#(1)) or RegId is equal to 0.', $modRegId);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));return;
    }

    switch($type) {
        case 'module':
            default:
            if (xarCore_IsCached('Mod.Infos', $modRegId)) {
                return xarCore_GetCached('Mod.Infos', $modRegId);
            }
            break;
        case 'theme':
            if (xarCore_IsCached('Theme.Infos', $modRegId)) {
                return xarCore_GetCached('Theme.Infos', $modRegId);
            }
            break;
    }
    xarLogMessage("xarModGetInfo ". $modRegId ." / " . $type);

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch($type) {
        case 'module':
        default:
            $the_table = $tables['modules'];
            $query = "SELECT xar_name,
                            xar_directory,
                            xar_mode,
                            xar_version,
                            xar_admin_capable,
                            xar_user_capable
                       FROM $the_table WHERE xar_regid = ?";
            break;
        case 'theme':
            $the_table = $tables['themes'];
            $query = "SELECT xar_name,
                            xar_directory,
                            xar_mode,
                            xar_version
                       FROM $the_table WHERE xar_regid = ?";
            break;
    }
    $result =& $dbconn->Execute($query,array($modRegId));
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'ID_NOT_EXIST', $modRegId);
        return;
    }

    switch($type) {
        case 'module':
        default:
            list($modInfo['name'],
                 $modInfo['directory'],
                 $mode,
                 $modInfo['version'],
                 $modInfo['admincapable'],
                 $modInfo['usercapable']) = $result->fields;
            break;
        case 'theme':
            list($modInfo['name'],
                 $modInfo['directory'],
                 $mode,
                 $modInfo['version']) = $result->fields;
            break;
    }
    $result->Close();

    $modInfo['regid'] = (int) $modRegId;
    $modInfo['mode'] = (int) $mode;
    $modInfo['displayname'] = xarMod::getDisplayName($modInfo['name'], $type);
    $modInfo['displaydescription'] = xarMod::getDisplayDescription($modInfo['name'], $type);

    // Shortcut for os prepared directory
    $modInfo['osdirectory'] = xarVarPrepForOS($modInfo['directory']);

    switch($type) {
        case 'module':
            default:
            $modState = xarMod_getState($modInfo['regid'], $modInfo['mode']);
            if (!isset($modState)) $modState = XARMOD_STATE_MISSING_FROM_UNINITIALISED; //return; // throw back
            $modInfo['state'] = $modState;

            $modFileInfo = xarMod_getFileInfo($modInfo['osdirectory']);
            break;
        case 'theme':
            $modState = xarMod_getState($modInfo['regid'], $modInfo['mode'], $type = 'theme');
            if (!isset($modState)) $modState = XARTHEME_STATE_MISSING_FROM_UNINITIALISED; //return; // throw back
            $modInfo['state'] = $modState;

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
        $modFileInfo['dependencyinfo'] = array();
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
            xarCore_SetCached('Mod.Infos', $modRegId, $modInfo);
            break;
        case 'theme':
            xarCore_SetCached('Theme.Infos', $modRegId, $modInfo);
            break;
    }

    return $modInfo;
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
 * Wrapper functions to support Xaraya 1 API for module aliases
 *
 */
function xarModGetAlias($alias) { return xarModAlias::resolve($alias);}
function xarModSetAlias($alias, $modName) { return xarModAlias::set($alias,$modName);}
function xarModDelAlias($alias, $modName) { return xarModAlias::delete($alias,$modName);}

/**
 * Interface declaration for module aliases
 *
 */
interface IxarModAlias
{
    static function resolve($alias);
    static function set    ($alias,$modName);
    static function delete ($alias,$modName);
}

/**
 * Class to model interface to module aliases
 *
 * @todo evaluate dependency consequences
 * @todo evaluate usage in modules, it's not very common, as in, perhaps worth to scrap and bolt onto a request mapper
 */
class xarModAlias extends Object implements IxarModAlias
{
    /**
     * Resolve an alias for a module
     */
    static function resolve($alias)
    {
        $aliasesMap = xarConfigGetVar(null,'System.ModuleAliases');
        return (!empty($aliasesMap[$alias])) ? $aliasesMap[$alias] : $alias;
    }

    /**
     * Set an alias for a module
     */
    static function set($alias,$modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) return;
        $args = array('modName' => $modName, 'aliasModName' => $alias);
        return xarMod::apiFunc('modules', 'admin', 'add_module_alias', $args);
    }

    /**
     * Delete an alias for a module
     */
    static function delete($alias, $modName)
    {
        if (!xarMod::apiLoad('modules', 'admin')) return;
        $args = array('modName' => $modName, 'aliasModName' => $alias);
        return xarMod::apiFunc('modules', 'admin', 'delete_module_alias', $args);
    }
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
 * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST, MODULE_FILE_NOT_EXIST, MODULE_FUNCTION_NOT_EXIST
 * @todo <marco> #1 add BAD_PARAM exception
 * @todo <marco> #2 check way of hanlding exception
 * @todo <marco> <mikespub> re-evaluate how GUI / API hooks are handled
 * @todo add itemtype (in extrainfo or as additional parameter)
 */
function xarModCallHooks($hookObject, $hookAction, $hookId, $extraInfo, $callerModName = NULL, $callerItemType = '')
{
    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookObject');
    //    return;
    //}
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookAction');
    //    return;
    //}

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

    // TODO: #2
    if (!isset($hooklist) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }

    $output = array();
    $isGUI = false;

    // TODO: #3

    // Call each hook
    foreach ($hooklist as $hook) {
        //THIS IS BROKEN
        //$hook['type'] and $type in the xarMod::isAvailable ARE NOT THE SAME THING
        //if (!xarMod::isAvailable($hook['module'], $hook['type'])) continue;
        if (!xarMod::isAvailable($hook['module'])) continue;
        if ($hook['area'] == 'GUI') {
            $isGUI = true;
            if (!xarMod::load($hook['module'], $hook['type']))  return;
            // return; Bug 4843 return causes all hooks to fail
            /* jojodee : it's not the return causing the fail necessarily. In fact there is no logical fail due to api or mod not loading
                in many cases, as the module or hook function is successfully loaded. The failure is in some modules' hook
                functions that do not set return after a priv check but return unset. It should return empty, not unset.
                Only a few of the newer modules or hook functions seem to be doing this and these have been corrected where possible in code.
                We still need a review of this function - and at each step where there is now an existing return.
            */

            $res = xarMod::guiFunc($hook['module'],
                              $hook['type'],
                              $hook['func'],
                              array('objectid' => $hookId,
                              'extrainfo' => $extraInfo));
            if (!isset($res)) return;//continue;
            // Note: hook modules can only register 1 hook per hookObject, hookAction and hookArea
            //       so using the module name as key here is OK (and easier for designers)
            $output[$hook['module']] = $res;
        } else {
            if (!xarMod::apiLoad($hook['module'], $hook['type']))  return; //return;
            $res = xarMod::apiFunc($hook['module'],
                                 $hook['type'],
                                 $hook['func'],
                                 array('objectid' => $hookId,
                                       'extrainfo' => $extraInfo));
            if (!isset($res))  return; //return;
            $extraInfo = $res;
        }
    }

// FIXME: this still returns the wrong output for many of the hook calls, whenever there are no hooks enabled
// Reason : we don't "know" here if the hooks defined by hookObject + hookAction are GUI or API hooks,
//          if we don't get that information from at least one enabled hook. But this is silly, really,
//          because there are *no* cases where you can have the same hookObject + hookAction in 2 different
//          hookAreas (GUI or API).
    if ($isGUI || preg_match('/^(display|new|modify|search|usermenu|modifyconfig)$/i',$hookAction)) {
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
 * @throws DATABASE_ERROR
 */
function xarModGetHookList($callerModName, $hookObject, $hookAction, $callerItemType = '')
{
    static $hookListCache = array();

    if (empty($callerModName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'callerModName');
        return;
    }
    //if ($hookObject != 'item' && $hookObject != 'category') {
    //    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookObject');
    //    return;
    //}
    //if ($hookAction != 'create' && $hookAction != 'delete' && $hookAction != 'transform' && $hookAction != 'display') {
    //    xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'hookAction');
    //    return;
    //}

    if (isset($hookListCache["$callerModName$callerItemType$hookObject$hookAction"])) {
        return $hookListCache["$callerModName$callerItemType$hookObject$hookAction"];
    }

    // Get database info
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $hookstable = $xartable['hooks'];

    // Get applicable hooks
    $query = "SELECT DISTINCT xar_tarea, xar_tmodule, xar_ttype, xar_tfunc, xar_order
              FROM $hookstable WHERE xar_smodule = ?";
    $bindvars = array($callerModName);

    if (empty($callerItemType)) {
        // Itemtype is not specified, only get the generic hooks
        $query .= " AND xar_stype = ''";
    } else {
        // hooks can be enabled for all or for a particular item type
        $query .= " AND (xar_stype = '' OR xar_stype = ?)";
        $bindvars[] = (string)$callerItemType;
        // FIXME: if itemtype is specified, why get the generic hooks? To save a function call in the modules?
        // Answer: generic hooks apply for *all* itemtypes, so if a caller specifies an itemtype, you
        //         need to check whether hooks are enabled for this particular itemtype or for all
        //         itemtypes here...
    }
    $query .= " AND xar_object = ? AND xar_action = ? ORDER BY xar_order ASC";
    $bindvars[] = $hookObject;
    $bindvars[] = $hookAction;
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    $resarray = array();
    while(!$result->EOF) {
        list($hookArea,
             $hookModName,
             $hookModType,
             $hookFuncName,
             $hookOrder) = $result->fields;

        $tmparray = array('area' => $hookArea,
                          'module' => $hookModName,
                          'type' => $hookModType,
                          'func' => $hookFuncName);

        array_push($resarray, $tmparray);
        $result->MoveNext();
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
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarModIsHooked($hookModName, $callerModName = NULL, $callerItemType = '')
{
    static $modHookedCache = array();

    if (empty($hookModName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'hookModName');
        return;
    }
    if (empty($callerModName)) {
        list($callerModName) = xarRequestGetInfo();
    }

    // Get all hook modules for the caller module once
    if (!isset($modHookedCache[$callerModName])) {
        // Get database info
        $dbconn =& xarDBGetConn();
        $xartable =& xarDBGetTables();
        $hookstable = $xartable['hooks'];

        // Get applicable hooks
        $query = "SELECT DISTINCT xar_tmodule, xar_stype FROM $hookstable WHERE xar_smodule = ?";
        $bindvars = array($callerModName);

        $result =& $dbconn->Execute($query,$bindvars);
        if (!$result) return;

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
 * @throws MODULE_FILE_NOT_EXIST
 * @todo <marco> #1 FIXME: admin or admin capable?
 */
function xarMod_getFileInfo($modOsDir, $type = 'module')
{
    if (empty($modOsDir)) {
        $msg = xarML('Directory information #(1) is empty.', '$modOsDir');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', new SystemException($msg));
        return;
    }

    if (empty($GLOBALS['xarMod_noCacheState']) && xarCore_IsCached('Mod.getFileInfos', $modOsDir)) {
        return xarCore_GetCached('Mod.getFileInfos', $modOsDir);
    }
    xarLogMessage("xarMod_getFileInfo ". $modOsDir ." / " . $type);

    // TODO redo legacy support via type.
    switch($type) {
        case 'module':
            default:
            // Spliffster, additional mod info from modules/$modDir/xarversion.php
            $fileName = 'modules/' . $modOsDir . '/xarversion.php';
            if (!file_exists($fileName)) {
                $fileName = 'modules/' . $modOsDir . '/pnversion.php';
                if (file_exists($fileName)) {
                    $fd = fopen($fileName, 'r');
                    if (!$fd){
                        $msg = xarML('Cannot open file #(1).', $fd);
                        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException($msg));
                        return;
                    }
                    $buf = '';
                    while (!feof($fd)) {
                        $buf .= fgets($fd, 1024);
                    }
                    fclose($fd);
                    //generate a checksum of max 10 digits
                    $checksum = abs(crc32($buf));
                    $modversion['id'] = "666" . substr($checksum,5);
                }
            }
            // If the locale is already present, it means we can make the translations available

            if(!empty($GLOBALS['xarMLS_currentLocale']) and file_exists($fileName))
                xarMLSLoadTranslations($fileName);
            break;
        case 'theme':
            $fileName = xarConfigGetVar('Site.BL.ThemesDirectory'). '/' . $modOsDir . '/xartheme.php';
            // pnAPI compatibility
            if (!file_exists($fileName)) {
                $fileName = 'themes/' . $modOsDir . '/xartheme.php';
            }

            break;
    }

    if (!file_exists($fileName)) {
        // Don't raise an exception, it is too harsh, but log it tho (bug #295)
        xarLogMessage("xarMod_getFileInfo: Could not find xarversion.php or pnversion.php, skipping $modOsDir");
        //xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', $fileName);
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

    xarCore_SetCached('Mod.getFileInfos', $modOsDir, $FileInfo);

    return $FileInfo;
}

/**
 * Load a module's base information
 *
 * @access protected
 * @param modName string the module's name
 * @param type determines theme or module
 * @return mixed an array of base module info on success
 * @throws DATABASE_ERROR, MODULE_NOT_EXIST
 */
function xarMod_getBaseInfo($modName, $type = 'module')
{
    if (empty($modName)) {
        $msg = xarML('Module or Theme Name #(1) is empty.', '$modName');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM',  new SystemException($msg));
        return;
    }

    if ($type != 'module' && $type != 'theme') {
        $msg = xarML('Wrong type, it must be \'module\' or \'theme\': #(1).', $type);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM',  new SystemException($msg));
        return;
    }

    // Fixme: this is a presentation issue.
    $upperFirstLetter = $type;
    $upperFirstLetter[0] = strtoupper($type[0]);

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

    if (empty($GLOBALS[$checkNoState]) && xarCore_IsCached($cacheCollection, $modName)) {
       return xarCore_GetCached($cacheCollection, $modName);
    }
    xarLogMessage("xarMod_getBaseInfo ". $modName ." / ". $type);

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $modulestable = $tables[$type.'s'];
    //The Shared Mode should not use 2 different tables!!!!!
    //It should add an extra column

    //FIXME:
    //This is a hack while we have 2 different tables for modules states
    //It will look in the most probable one first (SHARED)
    $modules_statesTable = $tables['system/'.$type.'_states'];

    $query = 'SELECT mods.xar_regid, mods.xar_directory, mods.xar_mode,'
        . ' mods.xar_id, modstates.xar_state, mods.xar_name'
        . ' FROM '.$modulestable.' mods'
        . ' LEFT JOIN '.$modules_statesTable.' modstates'
        . ' ON modstates.xar_regid = mods.xar_regid'
        . ' WHERE mods.xar_name = ? OR mods.xar_directory = ?';
    $bindvars = array($modName, $modName);

    $result =& $dbconn->Execute($query, $bindvars);
    if (!$result) return;

    if ($result->EOF) {
        $result->Close();
        return;
    }

    $modBaseInfo = array();

    list($regid, $directory, $mode, $systemid, $state, $name) = $result->fields;
    $result->Close();

    $modBaseInfo['regid'] = (int) $regid;
    $modBaseInfo['mode'] = (int) $mode;
    $modBaseInfo['systemid'] = (int) $systemid;
    $modBaseInfo['state'] = (int) $state;
    $modBaseInfo['name'] = $name;
    $modBaseInfo['directory'] = $directory;
    $modBaseInfo['displayname'] = xarMod::getDisplayName($directory, $type);
    $modBaseInfo['displaydescription'] = xarMod::getDisplayDescription($directory, $type);
    // Shortcut for os prepared directory
    // TODO: <marco> get rid of it since useless
    $modBaseInfo['osdirectory'] = xarVarPrepForOS($directory);

    //If we guessed wrong, then get the right state then.
    if ($modBaseInfo['mode'] != XARMOD_MODE_SHARED) {
         $modBaseInfo['state'] = xarMod_getState($modBaseInfo['regid'], $modBaseInfo['mode']);
         if (!isset($modBaseInfo['state'])) return; // throw back
    } else {
        if (empty($modBaseInfo['state'])) {
            $modBaseInfo['state'] = XARMOD_STATE_UNINITIALISED;
        }
    }

    xarCore_SetCached($cacheCollection, $name, $modBaseInfo);

    return $modBaseInfo;
}

/**
 * Get all module variables for a particular module
 *
 * @author Michel Dalle
 * @access protected
 * @param modName string
 * @return mixed true on success
 * @throws DATABASE_ERROR, BAD_PARAM
 */
function xarMod_getVarsByModule($modName, $type = 'module')
{
    if (empty($modName)) {
        $msg = xarML('Empty theme or module name (#(1)).', '$modName');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    switch($type) {
        case 'module':
            default:
            $modBaseInfo = xarMod_getBaseInfo($modName);
            if (!isset($modBaseInfo)) {
                return; // throw back
            }
            break;
        case 'theme':
            $modBaseInfo = xarMod_getBaseInfo($modName, $type = 'theme');
            if (!isset($modBaseInfo)) {
                return; // throw back
            }
            break;
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch($type) {
        case 'module':
            default:
            // Takes the right table basing on module mode
            if ($modBaseInfo['mode'] == XARMOD_MODE_SHARED) {
                $module_varstable = $tables['system/module_vars'];
            } elseif ($modBaseInfo['mode'] == XARMOD_MODE_PER_SITE) {
                $module_varstable = $tables['site/module_vars'];
            }

            $query = 'SELECT xar_name, xar_value FROM '.$module_varstable
                . ' WHERE xar_modid = ?';
            $result =& $dbconn->Execute($query, array($modBaseInfo['systemid']));
            if (!$result) return;

            while (!$result->EOF) {
                list($name, $value) = $result->fields;
                xarCore_SetCached('Mod.Variables.' . $modBaseInfo['name'], $name, $value);
                $result->MoveNext();
            }
            $result->Close();

            xarCore_SetCached('Mod.GetVarsByModule', $modBaseInfo['name'], true);
            break;
        case 'theme':
            // Takes the right table basing on theme mode
            if ($themeBaseInfo['mode'] == XARTHEME_MODE_SHARED) {
                $theme_varsTable = $tables['theme_vars'];
            } elseif ($themeBaseInfo['mode'] == XARTHEME_MODE_PER_SITE) {
                $theme_varsTable = $tables['site/theme_vars'];
            }

            $query = 'SELECT xar_name, xar_prime, xar_value, xar_description'
                . ' FROM '.$theme_varsTable.' WHERE xar_themeName = ?';
            $result =& $dbconn->Execute($query, array($themeName));
            if (!$result) return;

            $themevars = array();
            while (!$result->EOF) {
                list($name, $prime, $value, $description) = $result->fields;
                $themevars[] = array('name' => $name, 'prime' => $prime, 'value' => $value, 'description' => $description);
                xarCore_SetCached('Theme.Variables.' . $themeName, $name, $value);
                $result->MoveNext();
            }
            $result->Close();

            xarCore_SetCached('Theme.GetVarsByTheme', $themeName, true);
            break;
    }

    return true;
}

/**
 * Get all module variables with a particular name
 *
 * @author Michel Dalle
 * @access protected
 * @param name string
 * @return mixed true on success
 * @throws DATABASE_ERROR, BAD_PARAM
 * @todo <marco> #1 fetch from site table too ?
 * @todo <mrb> #2 fetch from site table too? (yes i know it's the same, just making you thing twice before changing this)
 */
function xarMod_getVarsByName($varName, $type = 'module')
{
    if (empty($varName)) {
        $msg = xarML('Empty Theme or Module variable name (#(1)).', '$varName');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch($type) {
    case 'module':
    default:

        // NOTE: Not trivial to determine whether we should fetch from system
        //       or site table because this spans all modules / themes
        // <mrb> the prefix thing should be rethought, it's not scalable (at least for sites)

        // Takes the right table basing on module mode
        $module_varstable = $tables['system/module_vars'];
        $module_table = $tables['modules'];

        $query = "SELECT mods.xar_name, vars.xar_value
                      FROM $module_table mods , $module_varstable vars
                      WHERE mods.xar_id = vars.xar_modid AND
                            vars.xar_name = ?";
        break;
    case 'theme':
        $theme_varsTable = $tables['system/theme_vars'];
        $query = "SELECT xar_themeName,
                             xar_value
                      FROM $theme_varsTable
                      WHERE xar_name = ?";
        break;
    }

    $result =& $dbconn->Execute($query,array($varName));
    if (!$result) return;

    // Add module variables to cache
    while (!$result->EOF) {
        list($name,$value) = $result->fields;
        xarCore_SetCached('Mod.Variables.' . $name, $varName, $value);
        $result->MoveNext();
    }

    $result->Close();
    switch($type) {
        case 'module':
            default:
            xarCore_SetCached('Mod.GetVarsByName', $varName, true);
            break;
        case 'theme':
            xarCore_SetCached('Theme.GetVarsByName', $varName, true);
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
 * @throws DATABASE_ERROR, BAD_PARAM, MODULE_NOT_EXIST
 */
function xarMod__loadDbInfo($modName, $modDir)
{
    static $loadedDbInfoCache = array();

    if (empty($modName)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modName');
        return;
    }
    if (empty($modDir)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'EMPTY_PARAM', 'modDir');
        return;
    }

    // Check to ensure we aren't doing this twice
    if (isset($loadedDbInfoCache[$modName])) {
        return true;
    }

    // Load the database definition if required
    $osxartablefile = "modules/$modDir/xartables.php";
    if (!file_exists($osxartablefile)) {
        $osxartablefile = 'modules/' . $modDir . '/pntables.php';
    }

    if (!file_exists($osxartablefile)) {
        return false;
    }
    include_once $osxartablefile;

    $tablefunc = $modName . '_' . 'xartables';
    $pntablefunc = $modName . '_' . 'pntables';

    if (function_exists($tablefunc)) {
        xarDB_importTables($tablefunc());
    } elseif (function_exists($pntablefunc)) {
        xarDB_importTables($pntablefunc());
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
 * @throws DATABASE_ERROR, MODULE_NOT_EXIST
 * @todo implement the xarMod__setState reciproke
 */
function xarMod_getState($modRegId, $modMode = XARMOD_MODE_PER_SITE, $type = 'module')
{
    if ($modRegId < 1) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modRegId');
        return;
    }
    //FIXME: what the heck this is doing in the interface?
    //Shouldnt this be a global of some sort, or function that returns this or whatever?
    if ($modMode != XARMOD_MODE_SHARED && $modMode != XARMOD_MODE_PER_SITE) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', 'modMode');
        return;
    }

    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    switch($type) {
        case 'module':
            default:
            if ($modMode == XARMOD_MODE_SHARED) {
                $module_statesTable = $tables['system/module_states'];
            } elseif ($modMode == XARMOD_MODE_PER_SITE) {
                $module_statesTable = $tables['site/module_states'];
            }

            $query = "SELECT xar_state FROM $module_statesTable
                      WHERE xar_regid = ?";
            break;
        case 'theme':
            if ($modMode == XARTHEME_MODE_SHARED) {
                $theme_statesTable = $tables['system/theme_states'];
            } else {
                $theme_statesTable = $tables['site/theme_states'];
            }

            $query = "SELECT xar_state FROM $theme_statesTable
                      WHERE xar_regid = ?";

            break;
    }

    $result =& $dbconn->Execute($query,array($modRegId));
    if (!$result) return;

    // the module is not in the table
    // set state to XARMOD_STATE_MISSING
    if (!$result->EOF) {
        list($modState) = $result->fields;
        $result->Close();
        return (int) $modState;
    } else {
        $result->Close();
        return (int) XARMOD_STATE_UNINITIALISED;
    }
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
 * @throws DATABASE_ERROR
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
    $query = "INSERT INTO $hookstable (
              xar_id,
              xar_object,
              xar_action,
              xar_tarea,
              xar_tmodule,
              xar_ttype,
              xar_tfunc)
              VALUES (?,?,?,?,?,?,?)";
    $seqId = $dbconn->GenId($hookstable);
    $bindvars = array($seqId,$hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

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
    // FIXME: <marco> BAD_PARAM?

    // Get database info
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $hookstable = $xartable['hooks'];

    // Remove hook
    $query = "DELETE FROM $hookstable
              WHERE xar_object = ?
              AND xar_action = ? AND xar_tarea = ? AND xar_tmodule = ?
              AND xar_ttype = ?  AND xar_tfunc = ?";
    $bindvars = array($hookObject,$hookAction,$hookArea,$hookModName,$hookModType,$hookFuncName);
    $result =& $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    return true;
}



?>
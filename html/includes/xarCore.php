<?php
/**
 * The Core
 *
 * @package core
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @author Marco Canini
 * @author Marcel van der Boom
 * @todo dependencies and runlevels!
 */

/**
 * Core version informations
 *
 * should be upgraded on each release for
 * better control on config settings
 *
 */
define('XARCORE_GENERATION',1);
define('XARCORE_VERSION_NUM', '1.2.0-b1');
define('XARCORE_VERSION_ID',  'Xaraya');
define('XARCORE_VERSION_SUB', 'adam_baum');
// define BlockLayout Version (added in 1.2.0)
define('XAR_BL_VERSION_NUM', '1.0.0');
/*
 * System dependencies for (optional) systems
 * FIXME: This diagram isn't correct (or at least not detailed enough)
 * ----------------------------------------------
 * | Name           | Depends on                |
 * ----------------------------------------------
 * | ADODB          | nothing                   |
 * | SESSION        | ADODB                     |
 * | CONFIGURATION  | ADODB                     |
 * | USER           | SESSION, ADODB            |
 * | BLOCKS         | CONFIGURATION, ADODB      |
 * | MODULES        | CONFIGURATION, ADODB      |
 * | EVENTS         | MODULES                   |
 * ----------------------------------------------
 *
 *
 *   ADODB              (00000001)
 *   |
 *   |- SESSION         (00000011)
 *   |  |
 *   |  |- USER         (00000111)
 *   |
 *   |- CONFIGURATION   (00001001)
 *      |
 *      |- BLOCKS       (00011001)
 *      |
 *      |- MODULES      (00101001)
 *
 */

/*
 * Optional systems defines that can be used as parameter for xarCoreInit
 * System dependancies are yet present in the define, so you don't
 * have to care of what for example the SESSION system depends on, if you
 * need it you just pass XARCORE_SYSTEM_SESSION to xarCoreInit and its
 * dependancies will be automatically resolved
 */

define('XARCORE_SYSTEM_NONE', 0);
define('XARCORE_SYSTEM_ADODB', 1);
define('XARCORE_SYSTEM_SESSION', 2 | XARCORE_SYSTEM_ADODB);
define('XARCORE_SYSTEM_USER', 4 | XARCORE_SYSTEM_SESSION);
define('XARCORE_SYSTEM_CONFIGURATION', 8 | XARCORE_SYSTEM_ADODB);
define('XARCORE_SYSTEM_BLOCKS', 16 | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_MODULES', 32 | XARCORE_SYSTEM_CONFIGURATION);
define('XARCORE_SYSTEM_ALL', 127); // bit OR of all optional systems (includes templates now)

define('XARCORE_BIT_ADODB', 1);
define('XARCORE_BIT_SESSION', 2);
define('XARCORE_BIT_USER', 4 );
define('XARCORE_BIT_CONFIGURATION', 8);
define('XARCORE_BIT_BLOCKS', 16);
define('XARCORE_BIT_MODULES', 32);

// Extra needed bit to figure out if this sub system was already loaded or not
define('XARCORE_BIT_TEMPLATE', 64);

/*
 * Debug flags
 */
define('XARDBG_ACTIVE'           , 1);
define('XARDBG_SQL'              , 2);
define('XARDBG_EXCEPTIONS'       , 4);
define('XARDBG_SHOW_PARAMS_IN_BT', 8);
define('XARDBG_INACTIVE'         ,16);
/*
 * xarInclude flags
 */
define('XAR_INCLUDE_ONCE'         , 1);
define('XAR_INCLUDE_MAY_NOT_EXIST', 2);

/*
 * Miscelaneous
 */
define('XARCORE_CONFIG_FILE', 'config.system.php');

/**
 * Load the Xaraya pre core early (in case we're not coming in via index.php)
 */
include_once(dirname(__FILE__).'/xarPreCore.php');

/**
 * Initializes the core engine
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @param integer whatToLoad What optional systems to load.
 * @return bool true
 * @todo <johnny> fix up sitetable prefix when we have a place to store it
 */
function xarCoreInit($whatToLoad = XARCORE_SYSTEM_ALL)
{
    static $current_load_level = XARCORE_SYSTEM_NONE;
    static $first_load = true;
    $new_load_level = $whatToLoad;

    // Make sure it only loads the current load level (or less than the current
    // load level) once.
    if ($whatToLoad <= $current_load_level) {
        if (!$first_load) {
            return true; // Does this ever happen? If so, we might consider an assert
        } else {
            $first_load = false;
        }
    } else {
        // if we are loading a load level higher than the
        // current one, make sure to XOR out everything
        // that we've already loaded
        $whatToLoad ^= $current_load_level;
    }

    /*
     * Start the different subsystems
     */

    /*
     * Load PHP Version Backwards Compatibility Library
     *
     */
    include 'includes/xarPHPCompat.php';
    xarPHPCompat::loadAll('includes/phpcompat');

    /**
     * At this point we should be able to catch all low level errors, so we can start the debugger
     * Set the types of debug you want to see by adding flags to the activation
     *
     * FLAGS:
     *
     * XARDBG_INACTIVE          disable  the debugger
     * XARDBG_ACTIVE            enable   the debugger
     * XARDBG_EXCEPTIONS        debug exceptions
     * XARDBG_SQL               debug SQL statements
     * XARDBG_SHOW_PARAMS_IN_BT show parameters in the backtrace
     *
     * Flags can be OR-ed together
     */
    xarCoreActivateDebugger(XARDBG_ACTIVE | XARDBG_EXCEPTIONS | XARDBG_SHOW_PARAMS_IN_BT);

    /*
     * If there happens something we want to be able to log it
     *
     */
    // {ML_dont_parse 'includes/xarLog.php'}
    include 'includes/xarLog.php';
    $systemArgs = array('loggerName' => xarCore_getSystemVar('Log.LoggerName', true),
                        'loggerArgs' => xarCore_getSystemVar('Log.LoggerArgs', true),
                        'level'      => xarCore_getSystemVar('Log.LogLevel', true));
    xarLog_init($systemArgs, $whatToLoad);

    /*
     * Start Exception Handling System
     *
     * Before we do anything make sure we can except out of code in a predictable matter
     *
     */
    include 'includes/xarException.php';
    $systemArgs = array('enablePHPErrorHandler' => xarCore_getSystemVar('Exception.EnablePHPErrorHandler'));
    xarError_init($systemArgs, $whatToLoad);


    /*
     * Start Database Connection Handling System
     *
     * Most of the stuff, except for logging, exception and system related things,
     * we want to do in the database, so initialize that as early as possible.
     * It think this is the earliest we can do
     *
     */
    if ($whatToLoad & XARCORE_SYSTEM_ADODB) { // yeah right, as if this is optional
        include 'includes/xarDB.php';

        // Decode encoded DB parameters
        $userName = xarCore_getSystemVar('DB.UserName');
        $password = xarCore_getSystemVar('DB.Password');
        if (xarCore_getSystemVar('DB.Encoded') == '1') {
            $userName = base64_decode($userName);
            $password  = base64_decode($password);
        }
        $systemArgs = array('userName' => $userName,
                            'password' => $password,
                            'databaseHost' => xarCore_getSystemVar('DB.Host'),
                            'databaseType' => xarCore_getSystemVar('DB.Type'),
                            'databaseName' => xarCore_getSystemVar('DB.Name'),
                            'persistent' => xarCore_getSystemVar('DB.Persistent',true),
                            'systemTablePrefix' => xarCore_getSystemVar('DB.TablePrefix'),
                            'siteTablePrefix' => xarCore_getSystemVar('DB.TablePrefix'));
        // Connect to database
        xarDB_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_ADODB;
    }

    /*
     * Start Event Messaging System
     *
     * The event messaging system can be initialized only after the db, but should
     * be as early as possible in place. This system is for *core* events
     *
     */
    // {ML_dont_parse 'includes/xarEvt.php'}
    include 'includes/xarEvt.php';
    $systemArgs = array('loadLevel' => $whatToLoad);
    xarEvt_init($systemArgs, $whatToLoad);


    /*
     * Start Configuration System
     *
     * Ok, we can  except, we can log our actions, we can access the db and we can
     * send events out of the core. It's time we start the configuration system, so we
     * can start configuring the framework
     *
     */
    if ($whatToLoad & XARCORE_SYSTEM_CONFIGURATION) {
        include 'includes/xarConfig.php';

        // Start Configuration Unit
        $systemArgs = array();
        xarConfig_init($systemArgs, $whatToLoad);

        // Start Variables utilities
        include 'includes/xarVar.php';
        xarVar_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_CONFIGURATION;
    }

    /**
     * Legacy systems
     *
     * Before anything fancy is loaded, let's start the legacy systems
     *
     */
    if (xarConfigGetVar('Site.Core.LoadLegacy') == true) {
        include 'includes/xarLegacy.php';
    }

    /*
     * At this point we haven't made any assumptions about architecture
     * except that we use a database as storage container.
     *
     */

    /*
     * Bring HTTP Protocol Server/Request/Response utilities into the story
     *
     */
    include 'includes/xarServer.php';
    $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                        'defaultModuleName'      => xarConfigGetVar('Site.Core.DefaultModuleName'),
                        'defaultModuleType'      => xarConfigGetVar('Site.Core.DefaultModuleType'),
                        'defaultModuleFunction'  => xarConfigGetVar('Site.Core.DefaultModuleFunction'),
                        'generateXMLURLs' => true);
    xarSerReqRes_init($systemArgs, $whatToLoad);


    /*
     * Bring Multi Language System online
     *
     */
    include 'includes/xarMLS.php';
    $systemArgs = array('MLSMode'             => xarConfigGetVar('Site.MLS.MLSMode'),
//                        'translationsBackend' => xarConfigGetVar('Site.MLS.TranslationsBackend'),
                        'translationsBackend' => 'xml2php',
                        'defaultLocale'       => xarConfigGetVar('Site.MLS.DefaultLocale'),
                        'allowedLocales'      => xarConfigGetVar('Site.MLS.AllowedLocales'),
                        'defaultTimeZone'     => xarConfigGetVar('Site.Core.TimeZone'),
                        'defaultTimeOffset'   => xarConfigGetVar('Site.MLS.DefaultTimeOffset'),
                        );
    xarMLS_init($systemArgs, $whatToLoad);



    /*
     * We deal with users through the sessions subsystem
     *
     */
    $anonuid = xarConfigGetVar('Site.User.AnonymousUID');
    // fall back to default uid 2 during installation (cfr. bootstrap function)
    $anonuid = !empty($anonuid) ? $anonuid : 2;
    define('_XAR_ID_UNREGISTERED', $anonuid);

    if ($whatToLoad & XARCORE_SYSTEM_SESSION) {
        include 'includes/xarSession.php';

        $systemArgs = array('securityLevel'     => xarConfigGetVar('Site.Session.SecurityLevel'),
                            'duration'          => xarConfigGetVar('Site.Session.Duration'),
                            'inactivityTimeout' => xarConfigGetVar('Site.Session.InactivityTimeout'),
                            'cookieName'        => xarConfigGetVar('Site.Session.CookieName'),
                            'cookiePath'        => xarConfigGetVar('Site.Session.CookiePath'),
                            'cookieDomain'      => xarConfigGetVar('Site.Session.CookieDomain'),
                            'refererCheck'      => xarConfigGetVar('Site.Session.RefererCheck'));
        xarSession_init($systemArgs, $whatToLoad);

        $whatToLoad ^= XARCORE_BIT_SESSION;
    }

    /**
     * Block subsystem
     *
     */
    // FIXME: This is wrong, should be part of templating
    //        it's a legacy thought, we don't need it anymore

    if ($whatToLoad & XARCORE_SYSTEM_BLOCKS) {
        include 'includes/xarBlocks.php';

        // Start Blocks Support Sytem
        $systemArgs = array();
        xarBlock_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_BLOCKS;
    }


    /**
     * Start Modules Subsystem
     *
     * @todo <mrb> why is this optional?
     * @todo <marco> Figure out how to dynamically compute generateXMLURLs argument based on browser request or XHTML site compliance. For now just pass true.
     * @todo <mrb> i thought it was configurable
     */
    if ($whatToLoad & XARCORE_SYSTEM_MODULES) {
        include 'includes/xarMod.php';
        $systemArgs = array('enableShortURLsSupport' => xarConfigGetVar('Site.Core.EnableShortURLsSupport'),
                            'generateXMLURLs' => true);
        xarMod_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_MODULES;
    }

    /**
     * We've got basically all we want, start the interface
     * Start BlockLayout Template Engine
     *
     */
    include 'includes/xarTemplate.php';
    $systemArgs = array(
        'enableTemplatesCaching' => xarConfigGetVar('Site.BL.CacheTemplates'),
        'themesBaseDirectory'    => xarConfigGetVar('Site.BL.ThemesDirectory'),
        'defaultThemeDir'        => xarModGetVar('themes','default'),
        'generateXMLURLs'      => true
    );
    xarTpl_init($systemArgs, $whatToLoad);
    $whatToLoad ^= XARCORE_BIT_TEMPLATE;


    /**
     * At last, we can give people access to the system.
     *
     * @todo <marcinmilan> review what pasts of the old user system need to be retained
     */
    if ($whatToLoad & XARCORE_SYSTEM_USER) {
        include 'includes/xarUser.php';
        include 'includes/xarSecurity.php';
        xarSecurity_init();
        // Start User System
        $systemArgs = array('authenticationModules' => xarConfigGetVar('Site.User.AuthenticationModules'));
        xarUser_init($systemArgs, $whatToLoad);
        $whatToLoad ^= XARCORE_BIT_USER;
    }

    // Make the current load level == the new load level
    $current_load_level = $new_load_level;

    // Core initialized register the shutdown function
    //register_shutdown_function('xarCore__shutdown_handler');
    return true;
}

/**
 * Default shutdown handler
 *
 *
 */
function xarCore__shutdown_handler()
{
    // Default shutdownhandler, nothing here yet,
    // but i think we could do something here with the
    // connection_aborted() function, signalling that
    // the user prematurely aborted. (by hitting stop or closing browser)
    // Also, the other subsystems can use a similar handler, for example to clean up
    // session tables or removing online status flags etc.
    // A carefully constructed combo with ignore_user_abort() and
    // a check afterward will get all requests atomic which might save
    // some headaches.

    // This handler is guaranteed to be registered as the last one, which
    // means that is also guaranteed to run last in the sequence of shutdown
    // handlers, the last statement in this function
    // is guaranteed to be the last statement of Xaraya ;-)
}

/**
 * Returns the relative path name for the var directory
 *
 * @author Marco Canini <marco@xaraya.com>
 * @access public
 * @return string the var directory path name
 * @todo   move the hardcoded stuff to something configurable
 */
function xarCoreGetVarDirPath()
{
    return xarPreCoreGetVarDirPath();
}

/**
 * Activates the debugger.
 *
 * @access public
 * @global integer xarDebug
 * @global integer xarDebug_sqlCalls
 * @global string xarDebug_startTime
 * @param integer flags bit mask for the debugger flags
 * @todo  a big part of this should be in the exception (error handling) subsystem.
 * @return void
 */
function xarCoreActivateDebugger($flags)
{
    $GLOBALS['xarDebug'] = $flags;
    if ($flags & XARDBG_INACTIVE) {
        // Turn off error reporting
        error_reporting(0);
        // Turn off assertion evaluation
        assert_options(ASSERT_ACTIVE, 0);
    } elseif ($flags & XARDBG_ACTIVE) {
        // See if config.system.php has info for us on the errorlevel, but dont break if it has not
        $errLevel = xarCore_getSystemVar('Exception.ErrorLevel',true);
        if(!isset($errLevel)) $errLevel = E_ALL;

        error_reporting($errLevel);
        // Activate assertions
        assert_options(ASSERT_ACTIVE,    1);    // Activate when debugging
        assert_options(ASSERT_WARNING,   1);    // Issue a php warning
        assert_options(ASSERT_BAIL,      0);    // Stop processing?
        assert_options(ASSERT_QUIET_EVAL,0);    // Quiet evaluation of assert condition?
        // Dependency! (move to xarException?)
        assert_options(ASSERT_CALLBACK,'xarException__assertErrorHandler'); // Call this function when the assert fails
        $GLOBALS['xarDebug_sqlCalls'] = 0;      // Set to 1 for inclusion of sql queries
        $lmtime = explode(' ', microtime());
        $GLOBALS['xarDebug_startTime'] = $lmtime[1] + $lmtime[0];
    }
}

/**
 * Check if the debugger is active
 *
 * @access public
 * @global integer xarDebug
 * @return bool true if the debugger is active, false otherwise
 */
function xarCoreIsDebuggerActive()
{
    if(isset($GLOBALS['xarDebug'])) {
        return $GLOBALS['xarDebug'] & XARDBG_ACTIVE;
    } else return false;

}

/**
 * Check for specified debugger flag.
 *
 * @access public
 * @param integer flag the debugger flag to check for activity
 * @return bool true if the flag is active, false otherwise
 */
function xarCoreIsDebugFlagSet($flag)
{
    return ($GLOBALS['xarDebug'] & XARDBG_ACTIVE) && ($GLOBALS['xarDebug'] & $flag);
}

/**
 * Gets a core system variable
 *
 * System variables are REQUIRED to be set, if they cannot be found
 * the system cannot continue. Only use variables for this which are
 * absolutely necessary to be set. Otherwise use other types of variables
 *
 * @access protected
 * @static systemVars array
 * @param string name name of core system variable to get
 * @param boolean returnNull if System variable doesn't exist return null
 * @return mixed The value of the specific var
 */
function xarCore_getSystemVar($name, $returnNull = false)
{
    static $systemVars = NULL;

    if (xarCore_IsCached('Core.getSystemVar', $name)) {
        return xarCore_GetCached('Core.getSystemVar', $name);
    }
    if (!isset($systemVars)) {
        $fileName = xarCoreGetVarDirPath() . '/' . XARCORE_CONFIG_FILE;
        if (!file_exists($fileName)) {
            xarCore_die("xarCore_getSystemVar: Configuration file not present: ".$fileName);
        }
        include $fileName;
        $systemVars = $systemConfiguration;
    }

    if (!isset($systemVars[$name])) {
        if($returnNull)
        {
            return null;
        } else {
            // FIXME: remove if/when there's some way to upgrade config.system.php or equivalent
            if ($name == 'DB.UseADODBCache') {
                $systemVars[$name] = false;
            } else {
                xarCore_die("xarCore_getSystemVar: Unknown system variable: ".$name);
            }
        }
    }

    xarCore_SetCached('Core.getSystemVar', $name, $systemVars[$name]);

    return $systemVars[$name];
}


/**
 * Load a file and capture any php errors
 *
 * @access public
 * @param  string $fileName name of the file to load
 * @param  bool   $flags    can this file only be loaded once, or multiple times? XAR_INCLUDE_ONCE and  XAR_INCLUDE_MAY_NOT_EXIST are the possible flags right now, INCLUDE_MAY_NOT_EXISTS makes the function succeed even in te absense of the file
 * @return bool   true if file was loaded successfully, false on error (NO exception)
 */
function xarInclude($fileName, $flags = XAR_INCLUDE_ONCE)
{
    // If the file isn't there return according to the flags
    if (!file_exists($fileName))
        return ($flags & XAR_INCLUDE_MAY_NOT_EXIST);

    //Commeting this to speed this function
    //Anyways the error_msg wasnt being used for anything.
    //I guess this doesnt work like this.
    //You would have to trap all the page output to get the PHP parse errors?!
    // Catch output, if any

//    ob_start();

    if ($flags & XAR_INCLUDE_ONCE) {
        $r = include_once($fileName);
    } else {
        $r = include($fileName);
    }

//    $error_msg = strip_tags(ob_get_contents());
//    ob_end_clean();

    if (empty($r) || !$r) {
        return false;
    }

    return true;
}

/**
 * Error function before Exceptions are loaded
 *
 * @access protected
 * @param string msg message to print as an error
 */
function xarCore_die($msg)
{
    static $dying = false;
    /*
     * Prolly paranoid now, but to prevent looping we keep track if we have already
     * been here.
     */
    if($dying) return;
    $dying = true;

    // This is allowed, in core itself
    // NOTE that this will never be translated
    if (xarCoreIsDebuggerActive()) {
        $msg = nl2br($msg);
$debug = <<<EOD
<br /><br />
<p align="center"><span style="color: blue">Technical information</span></p>
<p>Xaraya has failed to serve the request, and the failure could not be handled.</p>
<p>This is a bad sign and probably means that Xaraya is not configured properly.</p>
<p>The failure reason is: <span style="color: red">$msg</span></p>
EOD;
    } else {
       $debug = '';
    }
$errPage = <<<EOM
<html>
  <head>
    <title>Fatal Error</title>
  </head>
  <body>
    <p>A fatal error occurred while serving your request.</p>
    <p>We are sorry for this inconvenience.</p>
    <p>If this is the first time you see this message, you can try to access the site directly through index.php<br/>
    If you see this message every time you tried to access to this service, it is probable that our server
    is experiencing heavy problems, for this reason we ask you to retry in some hours.<br/>
    If you see this message for days, we ask you to report the unavailablity of service to our webmaster. Thanks.
    </p>
    $debug
  </body>
</html>
EOM;
    if (headers_sent() == false)
        header('HTTP/1.1 503 Service Unavailable');
    echo $errPage;
    // Sorry, this is the end, nothing can be trusted anymore.
    die();
}

/**
 * Check whether a certain API type is allowed
 *
 * Check whether an API type is allowed to load
 * normally the api types are 'user' and 'admin' but modules
 * may define other API types which do not fall in either of
 * those categories. (for example: visual or soap)
 * The list of API types is read from the Core configuration variable
 * Core.AllowedAPITypes.
 *
 * @author Marcel van der Boom marcel@hsdev.com
 * @access protected
 * @param  string apiType type of API to check whether allowed to load
 * @todo   See if we can get rid of this, nobody is using this
 * @return bool
 */
function xarCoreIsApiAllowed($apiType)
{
    // Testing for an empty API type just returns false
    if (empty($apiType)) return false;
    if (preg_match ("/api$/i", $apiType)) return false;

    // Dependency
    $allowed = xarConfigGetVar('System.Core.AllowedAPITypes');

    // If no API type restrictions are given, return true
    if (empty($allowed) || count($allowed) == 0) return true;
    return in_array($apiType,$allowed);
}

/**
* Get the value of a cached variable
 *
 * @access protected
 * @global xarCore_cacheCollection array
 * @param key string the key identifying the particular cache you want to access
 * @param name string the name of the variable in that particular cache
 * @return mixed value of the variable, or void if variable isn't cached
 */
function xarCore_IsCached($cacheKey, $name)
{
    if (!isset($GLOBALS['xarCore_cacheCollection'][$cacheKey])) {
        $GLOBALS['xarCore_cacheCollection'][$cacheKey] = array();
        return false;
    }
    return isset($GLOBALS['xarCore_cacheCollection'][$cacheKey][$name]);
}

/**
* Get the value of a cached variable
 *
 * @access protected
 * @global xarCore_cacheCollection array
 * @param key string the key identifying the particular cache you want to access
 * @param name string the name of the variable in that particular cache
 * @return mixed value of the variable, or void if variable isn't cached
 */
function xarCore_GetCached($cacheKey, $name)
{
    if (!isset($GLOBALS['xarCore_cacheCollection'][$cacheKey][$name])) {
        return;
    }
    return $GLOBALS['xarCore_cacheCollection'][$cacheKey][$name];
}

/**
* Set the value of a cached variable
 *
 * @access protected
 * @global xarCore_cacheCollection array
 * @param key string the key identifying the particular cache you want to access
 * @param name string the name of the variable in that particular cache
 * @param value string the new value for that variable
 * @return void
 */
function xarCore_SetCached($cacheKey, $name, $value)
{
    if (!isset($GLOBALS['xarCore_cacheCollection'][$cacheKey])) {
        $GLOBALS['xarCore_cacheCollection'][$cacheKey] = array();
    }
    $GLOBALS['xarCore_cacheCollection'][$cacheKey][$name] = $value;
}

/**
* Delete a cached variable
 *
 * @access protected
 * @global xarCore_cacheCollection array
 * @param string key the key identifying the particular cache you want to access
 * @param string name the name of the variable in that particular cache
 */
function xarCore_DelCached($cacheKey, $name)
{
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($GLOBALS['xarCore_cacheCollection'][$cacheKey][$name])) {
        unset($GLOBALS['xarCore_cacheCollection'][$cacheKey][$name]);
    }
    //This unsets the key that said that collection had already been retrieved

    //Seems to have caused a problem because of the expected behaviour of the old code
    //FIXME: Change how this works for a mainstream function, stop the hacks
    if (isset($GLOBALS['xarCore_cacheCollection'][$cacheKey][0])) {
        unset($GLOBALS['xarCore_cacheCollection'][$cacheKey][0]);
    }
}

/**
* Flush a particular cache (e.g. for session initialization)
 *
 * @access protected
 * @global xarCore_cacheCollection array
 * @param cacheKey the key identifying the particular cache you want to wipe out
 * @return void
 */
function xarCore_FlushCached($cacheKey)
{
    // TODO: check if we don't need to work with $GLOBALS here for some PHP ver
    if (isset($GLOBALS['xarCore_cacheCollection'][$cacheKey])) {
        unset($GLOBALS['xarCore_cacheCollection'][$cacheKey]);
    }
}

/**
* Checks if a certain function was disabled in php.ini
 *
 * xarCore.php function
 * @access public
 * @param string The function name; case-sensitive
 * @return bool true or false
 */
function xarFuncIsDisabled($funcName)
{
    static $disabled;

    if (!isset($disabled)) {
        // Fetch the disabled functions as an array.
        // White space is trimmed here too.
        $functions = preg_split('/[\s,]+/', trim(ini_get('disable_functions')));

        if ($functions[0] != '') {
            // Make the function names the keys.
            // Values will be 0, 1, 2 etc.
            $disabled = array_flip($functions);
        } else {
            $disabled = array();
        }
    }

    return (isset($disabled[$funcName]) ? true : false);
}
?>

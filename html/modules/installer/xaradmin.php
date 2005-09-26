<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */
if (!file_exists('install.php')) {xarCore_die(xarML('Already installed'));}

/**
 * Dead
 *
 * @access public
 * @returns array
 * @return an array of template values
 */
function installer_admin_main()
{
    $data['phase'] = 0;
    $data['phase_label'] = xarML('Welcome to Xaraya');
    return $data;
}

/**
 * Phase 1: Welcome (Set Language and Locale) Page
 *
 * @access private
 * @return data array of language values
 */
function installer_admin_phase1()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    // Get the installed locales
    $locales = xarMLSListSiteLocales();

    // Construct the array for the selectbox (iso3code, string in own locale)
    if(!empty($locales)) {
        $languages = array();
        foreach ($locales as $locale) {
            // Get the isocode and the description
            // Before we load the locale data, let's check if the locale is there
            
            // <marco> This check is really not necessary since available locales are
            // already determined from existing files. The relative code is in install.php
            //$fileName = xarCoreGetVarDirPath() . "/locales/$locale/locale.xml";
            //if(file_exists($fileName)) {
            $locale_data =& xarMLSLoadLocaleData($locale);
            $languages[$locale] = $locale_data['/language/display'];
            //}
        }
    }

    $data['install_language'] = $install_language;
    $data['languages'] = $languages;
    $data['phase'] = 1;
    $data['phase_label'] = xarML('Step One');

    return $data;
}

/**
 * Phase 2: Accept License Page
 *
 * @access private
 * @return array
 */
function installer_admin_phase2()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    $data['language'] = $install_language;
    $data['phase'] = 2;
    $data['phase_label'] = xarML('Step Two');

    return $data;
}

/**
 * Check whether directory permissions allow to write and read files inside it
 * 
 * @access private
 * @param string dirname directory name
 * @return bool true if directory is writable, readable and executable
 */
function check_dir($dirname)
{
    if (@touch($dirname . '/.check_dir')) {
        $fd = @fopen($dirname . '/.check_dir', 'r');
        if ($fd) {
            fclose($fd);
            unlink($dirname . '/.check_dir');
        } else {
            return false;
        }
    } else {
        return false;
    }
    return true;
}

/**
 * Phase 3: Check system settings
 *
 * @access private
 * @param agree string
 * @return array
 * @todo <johnny> make sure php version checking works with
 *       php versions that contain strings
 */
function installer_admin_phase3()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    if (!xarVarFetch('agree','regexp:(agree|disagree)',$agree)) return;

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        xarResponseRedirect('install.php?install_phase=2&install_language='.$install_language);
    }

    //Defaults
    $systemConfigIsWritable   = false;
    $cacheTemplatesIsWritable = false;
    $rssTemplatesIsWritable   = false;
    $metRequiredPHPVersion    = false;

    $systemVarDir             = xarCoreGetVarDirPath();
    $cacheDir                 = $systemVarDir . '/cache';
    $cacheTemplatesDir        = $systemVarDir . '/cache/templates';
    $rssTemplatesDir          = $systemVarDir . '/cache/rss';
    $adodbTemplatesDir        = $systemVarDir . '/cache/adodb';
    $systemConfigFile         = $systemVarDir . '/config.system.php';
    $phpLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/php';
    $xmlLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/xml';

    if (function_exists('version_compare')) {
        if (version_compare(PHP_VERSION,'4.1.2','>=')) $metRequiredPHPVersion = true;
    }

    $systemConfigIsWritable     = is_writable($systemConfigFile);
    $cacheIsWritable            = check_dir($cacheDir);
    $cacheTemplatesIsWritable   = (check_dir($cacheTemplatesDir) || @mkdir($cacheTemplatesDir, 0700));
    $rssTemplatesIsWritable     = (check_dir($rssTemplatesDir) || @mkdir($rssTemplatesDir, 0700));
    $adodbTemplatesIsWritable   = (check_dir($adodbTemplatesDir) || @mkdir($adodbTemplatesDir, 0700));
    $phpLanguageFilesIsWritable = xarMLS__iswritable($phpLanguageDir);
    $xmlLanguageFilesIsWritable = xarMLS__iswritable($xmlLanguageDir);
    $memLimit = trim(ini_get('memory_limit'));
    $memLimit = empty($memLimit) ? '8M' : $memLimit;
    $memVal = substr($memLimit,0,strlen($memLimit)-1);
    switch(strtolower($memLimit{strlen($memLimit)-1})) {
        case 'g': $memVal *= 1024;
        case 'm': $memVal *= 1024;
        case 'k': $memVal *= 1024;
    }

    // Extension Check
    $data['xmlextension']             = extension_loaded('xml');
    $data['mysqlextension']           = extension_loaded('mysql');
    $data['pgsqlextension']           = extension_loaded ('pgsql');
    $data['xsltextension']            = extension_loaded ('xslt');
    $data['ldapextension']            = extension_loaded ('ldap');
    $data['gdextension']              = extension_loaded ('gd');

    $data['metRequiredPHPVersion']    = $metRequiredPHPVersion;
    $data['phpVersion']               = PHP_VERSION;
    $data['cacheDir']                 = $cacheDir;
    $data['cacheIsWritable']          = $cacheIsWritable;
    $data['cacheTemplatesDir']        = $cacheTemplatesDir;
    $data['cacheTemplatesIsWritable'] = $cacheTemplatesIsWritable;
    $data['rssTemplatesDir']          = $rssTemplatesDir;
    $data['rssTemplatesIsWritable']   = $rssTemplatesIsWritable;
    $data['adodbTemplatesDir']        = $adodbTemplatesDir;
    $data['adodbTemplatesIsWritable'] = $adodbTemplatesIsWritable;
    $data['systemConfigFile']         = $systemConfigFile;
    $data['systemConfigIsWritable']   = $systemConfigIsWritable;
    $data['phpLanguageDir']             = $phpLanguageDir;
    $data['phpLanguageFilesIsWritable'] = $phpLanguageFilesIsWritable;
    $data['xmlLanguageDir']             = $xmlLanguageDir;
    $data['xmlLanguageFilesIsWritable'] = $xmlLanguageFilesIsWritable;
    $data['memory_limit']               = $memLimit;
    $data['metMinMemRequirement']       = $memVal >= 8 * 1024 * 1024;

    $data['language']    = $install_language;
    $data['phase']       = 3;
    $data['phase_label'] = xarML('Step Three');

    return $data;
}

/**
 * Phase 4: Database Settings Page
 *
 * @access private
 * @return array of default values for the database creation
 */
function installer_admin_phase4()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    // Get default values from config files
    $data['database_host']       = xarCore_getSystemVar('DB.Host');
    $data['database_username']   = xarCore_getSystemVar('DB.UserName');
    $data['database_password']   = '';//xarCore_getSystemvar('DB.Password');
    $data['database_name']       = xarCore_getSystemvar('DB.Name');
    $data['database_prefix']     = xarCore_getSystemvar('DB.TablePrefix');
    $data['database_type']       = xarCore_getSystemvar('DB.Type');
    // Supported  Databases:
    $data['database_types']      = array('mysql'    => array('name' => 'MySQL'   , 'available' => extension_loaded('mysql')),
                                         'postgres' => array('name' => 'Postgres', 'available' => extension_loaded('pgsql')),
                                         'sqlite'   => array('name' => 'SQLite'  , 'available' => extension_loaded('sqlite')),
                                         // use portable version of OCI8 driver to support ? bind variables
                                         'oci8po'   => array('name' => 'Oracle 9+ (not supported)'  , 'available' => extension_loaded('oci8')),
                                         'mssql'    => array('name' => 'MS SQL Server (not supported)' , 'available' => extension_loaded('mssql')),
                                        );

    $data['language'] = $install_language;
    $data['phase'] = 4;
    $data['phase_label'] = xarML('Step Four');

    return $data;
}

/**
 * Phase 5: Pre-Boot, Modify Configuration
 *
 * @access private
 * @param dbHost
 * @param dbName
 * @param dbUname
 * @param dbPass
 * @param dbPrefix
 * @param dbType
 * @param createDb
 * @todo better error checking on arguments
 */
function installer_admin_phase5()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarSetCached('installer','installing', true);

    // Get arguments
    if (!xarVarFetch('install_database_host','pre:trim:passthru:str',$dbHost)) return;
    if (!xarVarFetch('install_database_name','pre:trim:passthru:str',$dbName,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_username','pre:trim:passthru:str',$dbUname,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_password','pre:trim:passthru:str',$dbPass,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_prefix','pre:trim:passthru:str',$dbPrefix,'xar',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_type','str:1:',$dbType)) return;
    if (!xarVarFetch('install_create_database','checkbox',$createDB,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmDB','bool',$confirmDB,false,XARVAR_NOT_REQUIRED)) return;

    if ($dbName == '') {
        $msg = xarML('No database was specified');
        xarCore_die($msg);
        return;
    }

    // allow only a-z 0-9 and _ in table prefix
    if (!preg_match('/^\w*$/',$dbPrefix)) {
        $msg = xarML('Invalid character in table prefix');
        xarCore_die($msg);
        return;
    }
    // Save config data
    $config_args = array('dbHost'    => $dbHost,
                         'dbName'    => $dbName,
                         'dbUname'   => $dbUname,
                         'dbPass'    => $dbPass,
                         'dbPrefix'  => $dbPrefix,
                         'dbType'    => $dbType);

    if (!xarInstallAPIFunc('modifyconfig', $config_args)) {
        return;
    }

    //Do we already have a db?
    //TODO: rearrange the loading sequence so that I can use xar functions
    //rather than going directly to adodb
    // Load in ADODB
    // FIXME: This is also in xarDB init, does it need to be here?
    if (!defined('XAR_ADODB_DIR')) {
        define('XAR_ADODB_DIR','xaradodb');
    }
    include_once XAR_ADODB_DIR . '/adodb.inc.php';
    $ADODB_CACHE_DIR = xarCoreGetVarDirPath() . "/cache/adodb";

    // {ML_dont_parse 'includes/xarDB.php'}
    include_once 'includes/xarDB.php';

    // Check if there is a xar- version of the driver, and use it.
    // Note the driver we load does not affect the database type.
    if (xarDBdriverExists('xar' . $dbType, 'adodb')) {
        $dbDriver = 'xar' . $dbType;
    } else {
        $dbDriver = $dbType;
    }

    $dbconn = ADONewConnection($dbDriver);
    $dbExists = TRUE;

    // Not all Database Servers support selecting the specific db *after* connecting
    // so let's try connecting with the dbname first, and then without if that fails
    $dbConnected = @$dbconn->Connect($dbHost, $dbUname, $dbPass, $dbName);

    if (!$dbConnected) {
        // Couldn't connect to the specified dbName. Let's try connecting without dbName now
        // Need to reset dbconn prior to trying just a normal connection
        unset($dbconn);
        $dbconn = ADONewConnection($dbDriver);

        if ($dbConnected = @$dbconn->Connect($dbHost, $dbUname, $dbPass)) {
            $dbExists = FALSE;
        } else {
            $dbConnected = FALSE;
            $dbExists = FALSE;
        }
    }

    if (!$dbConnected) {
        $msg = xarML('Database connection failed. The information supplied was erroneous, such as a bad or missing password or wrong username.');
        xarCore_die($msg);
        return;
    }

    if (!$createDB && !$dbExists) {
        $msg = xarML('Database #(1) doesn\'t exist and it wasnt selected to be created.', $dbName);
        xarCore_die($msg);
        return;
    }

    $data['confirmDB']  = $confirmDB;
    if ($dbExists && !$confirmDB) {
        $data['dbHost']     = $dbHost;
        $data['dbName']     = $dbName;
        $data['dbUname']    = $dbUname;
        $data['dbPass']     = $dbPass;
        $data['dbPrefix']   = $dbPrefix;
        $data['dbType']     = $dbType;
        $data['install_create_database']      = $createDB;
        $data['language']    = $install_language;
        return $data;
    }

    // Create the database if necessary
    if ($createDB) {
        $data['confirmDB']  = true;
        //Let's pass all input variables thru the function argument or none, as all are stored in the system.config.php
        //Now we are passing all, let's see if we gain consistency by loading config.php already in this phase?
        //Probably there is already a core function that can make that for us...
        //the config.system.php is lazy loaded in xarCore_getSystemVar($name), which means we cant reload the values
        // in this phase... Not a big deal 'though.
        if ($dbExists) {
            if (!$dbconn->Execute('DROP DATABASE ' . $dbName)) return;
        }
        if (!xarInstallAPIFunc('createdb', $config_args)) {
            $msg = xarML('Could not create database (#(1)). Check if you already have a database by that name and remove it.', $dbName);
            xarCore_die($msg);
            return;
        }
    }
    else {
        $removetables = true;
    }

    // Start the database
    $systemArgs = array('userName' => $dbUname,
                        'password' => $dbPass,
                        'databaseHost' => $dbHost,
                        'databaseType' => $dbType,
                        'databaseName' => $dbName,
                        'systemTablePrefix' => $dbPrefix,
                        'siteTablePrefix' => $dbPrefix);
    // Connect to database
    $whatToLoad = XARCORE_SYSTEM_NONE;
    xarDB_init($systemArgs, $whatToLoad);

    // drop all the tables that have this prefix
    //TODO: in the future need to replace this with a check further down the road
    // for which modules are already installed
    if (isset($removetables) && $removetables) {
        $dbconn =& xarDBGetConn();
        $result = $dbconn->Execute($dbconn->metaTablesSQL);
        if(!$result) return;
        $tables = array();
        while(!$result->EOF) {
            list($table) = $result->fields;
            $parts = explode('_',$table);
            if ($parts[0] == $dbPrefix) $tables[] = $table;
            $result->MoveNext();
        }
        foreach ($tables as $table) {
            if (!$dbconn->Execute('DROP TABLE ' . $table)) return;
        }
    }

    // install the security stuff here, but disable the registerMask and
    // and xarSecurityCheck functions until we've finished the installation process

    include_once 'includes/xarSecurity.php';
    xarSecurity_init();

    // Load in modules/installer/xarinit.php and start the install
    // This effectively initializes the base module.
    if (!xarInstallAPIFunc('initialise',
                           array('directory' => 'installer',
                                 'initfunc'  => 'init'))) {
        return;
    }

    // If we are here, the base system has completed
    // We can now pass control to xaraya.
    include_once 'includes/xarConfig.php';
    xarConfig_init(array(),XARCORE_SYSTEM_ADODB);
    xarConfigSetVar('Site.MLS.DefaultLocale', $install_language);

    // Set the allowed locales to our "C" locale and the one used during installation
    // TODO: make this a bit more friendly.
    $necessaryLocale = array('en_US.utf-8');
    $install_locale  = array($install_language);
    $allowed_locales = array_merge($necessaryLocale, $install_locale);

    xarConfigSetVar('Site.MLS.AllowedLocales',$allowed_locales);    $data['language'] = $install_language;

    $data['phase'] = 5;
    $data['phase_label'] = xarML('Step Five');

    return $data;
}

/**
 * Bootstrap Xaraya
 *
 * @access private
 */
function installer_admin_bootstrap()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarSetCached('installer','installing', true);

    // create the default roles and privileges setup
    include 'modules/privileges/xarsetup.php';
    initializeSetup();

    // Set up default user properties, etc.

    // load modules into *_modules table
    if (!xarModAPIFunc('modules', 'admin', 'regenerate')) return;

    // Set the state and activate the following modules
    $modlist=array('roles','privileges','blocks','themes');
    foreach ($modlist as $mod) {
        // Set state to inactive
        $regid=xarModGetIDFromName($mod);
        if (isset($regid)) {
            if (!xarModAPIFunc('modules','admin','setstate',
                                array('regid'=> $regid, 'state'=> XARMOD_STATE_INACTIVE))) return;

            // Activate the module
            if (!xarModAPIFunc('modules','admin','activate', array('regid'=> $regid))) return;
        }
    }

    // load themes into *_themes table
    if (!xarModAPIFunc('themes', 'admin', 'regenerate')) {
        return NULL;
    }

    // Set the state and activate the following themes
    $themelist=array('print','rss','Xaraya_Classic');
    foreach ($themelist as $theme) {
        // Set state to inactive
        $regid=xarThemeGetIDFromName($theme);
        if (isset($regid)) {
            if (!xarModAPIFunc('themes','admin','setstate', array('regid'=> $regid,'state'=> XARTHEME_STATE_INACTIVE))){
                return;
            }
            // Activate the theme
            if (!xarModAPIFunc('themes','admin','activate', array('regid'=> $regid)))
            {
                return;
            }
        }
    }

    // Initialise and activate adminpanels, mail, dynamic data
    $modlist = array('adminpanels','mail', 'dynamicdata');
    foreach ($modlist as $mod) {
        // Initialise the module
        $regid = xarModGetIDFromName($mod);
        if (isset($regid)) {
            if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $regid))) return;
            // Activate the module
            if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $regid))) return;
        }
    }

    //initialise and activate base module by setting the states
    $baseId = xarModGetIDFromName('base');
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => $baseId, 'state' => XARMOD_STATE_INACTIVE))) return;
    // Set module state to active
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => $baseId, 'state' => XARMOD_STATE_ACTIVE))) return;

    // save the uids of the default roles for later
    $role = xarFindRole('Everybody');
    xarModSetVar('roles', 'everybody', $role->getID());
    $role = xarFindRole('Anonymous');
    xarConfigSetVar('Site.User.AnonymousUID', $role->getID());
    // set the current session information to the right anonymous uid
    xarSession_setUserInfo($role->getID(), 0);
    $role = xarFindRole('Admin');
    xarModSetVar('roles', 'admin', $role->getID());

    xarResponseRedirect(xarModURL('installer', 'admin', 'create_administrator',array('install_language' => $install_language)));
}

/**
 * Create default administrator and default blocks
 *
 * @access public
 * @param create
 * @return bool
 * @todo make confirm password work
 * @todo remove URL field from users table
 * @todo normalize user's table
 */
function installer_admin_create_administrator()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    xarVarSetCached('installer','installing', true);
    xarTplSetPageTemplateName('installer');

    $data['language'] = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Create Administrator');

    include_once 'modules/roles/xarroles.php';
    $role = xarFindRole('Admin');

    if (!xarVarFetch('create', 'isset', $create, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!$create) {
        // create a role from the data

        // assemble the template data
        $data['install_admin_username'] = $role->getUser();
        $data['install_admin_name']     = $role->getName();
        $data['install_admin_email']    = $role->getEmail();
        return $data;
    }

    if (!xarVarFetch('install_admin_username','str:1:100',$userName)) return;
    if (!xarVarFetch('install_admin_name','str:1:100',$name)) return;
    if (!xarVarFetch('install_admin_password','str:4:100',$pass)) return;
    if (!xarVarFetch('install_admin_password1','str:4:100',$pass1)) return;
    if (!xarVarFetch('install_admin_email','str:1:100',$email)) return;

    xarModSetVar('mail', 'adminname', $name);
    xarModSetVar('mail', 'adminmail', $email);
    xarModSetVar('themes', 'SiteCopyRight', '&copy; Copyright ' . date("Y") . ' ' . $name);

    if ($pass != $pass1) {
        $msg = xarML('The passwords do not match');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    if (empty($userName)) {
        $msg = xarML('You must provide a preferred username to continue.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;

    // check for spaces in the username
    } elseif (preg_match("/[[:space:]]/",$userName)) {
        $msg = xarML('There is a space in the username.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;

    // check the length of the username
    } elseif (strlen($userName) > 255) {
        $msg = xarML('Your username is too long.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;

    // check for spaces in the username (again ?)
    } elseif (strrpos($userName,' ') > 0) {
        $msg = xarML('There is a space in your username.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // assemble the args into an array for the role constructor
    $pargs = array('uid'   => $role->getID(),
                   'name'  => $name,
                   'type'  => 0,
                   'uname' => $userName,
                   'email' => $email,
                   'pass'  => $pass,
                   'state' => 3);

    xarModSetVar('roles', 'lastuser', $userName);
    xarModSetVar('roles', 'adminpass', $pass);

    // create a role from the data
    $role = new xarRole($pargs);

    //Try to update the role to the repository and bail if an error was thrown
    $modifiedrole = $role->update();
    if (!$modifiedrole) {return;}

    // Register Block types
    $blocks = array('finclude','html','menu','php','text','content');

    foreach ($blocks as $block) {
        if (!xarModAPIFunc('blocks', 'admin', 'register_block_type', array('modName'  => 'base', 'blockType'=> $block))) return;
    }

    if (xarVarIsCached('Mod.BaseInfos', 'blocks')) xarVarDelCached('Mod.BaseInfos', 'blocks');

    // Create default block groups/instances
    //                            name        template
    $default_blockgroups = array ('left'   => '',
                                  'right'  => 'right',
                                  'header' => 'header',
                                  'admin'  => '',
                                  'center' => 'center',
                                  'topnav' => 'topnav'
                                  );

    foreach ($default_blockgroups as $name => $template) {
        if(!xarModAPIFunc('blocks','user','groupgetinfo', array('name' => $name))) {
            // Not there yet
            if(!xarModAPIFunc('blocks','admin','create_group', array('name' => $name, 'template' => $template))) return;
        }
    }

    // Load up database
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'left'";

    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($leftBlockGroup) = $result->fields;

    $adminBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                    array('module'  => 'adminpanels',
                                          'type'    => 'adminmenu'));

    if (empty($adminBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }

    $adminBlockTypeId = $adminBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'adminpanel'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Admin',
                                 'name'     => 'adminpanel',
                                 'type'     => $adminBlockTypeId,
                                 'groups'   => array(array('gid'      => $leftBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    =>  2))) {
            return;
        }
    }

    $now = time();

    $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot .';
    $varshtml['expire'] = $now + 24000;
    $msg = serialize($varshtml);

    $htmlBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                 array('module'  => 'base',
                                       'type'    => 'html'));

    if (empty($htmlBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }

    $htmlBlockTypeId = $htmlBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'reminder'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Reminder',
                                 'name'     => 'reminder',
                                 'content'  => $msg,
                                 'type'     => $htmlBlockTypeId,
                                 'groups'   => array(array('gid'      => $leftBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }
    xarResponseRedirect(xarModURL('installer', 'admin', 'choose_configuration',array('install_language' => $install_language)));
}

/**
 * Choose the configuration to be installed
 *
 * @access public
 * @param create
 * @return bool
 */
function installer_admin_choose_configuration()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    $data['language'] = $install_language;
    $data['phase'] = 7;
    $data['phase_label'] = xarML('Choose your configuration');
    xarTplSetPageTemplateName('installer');

    //Get all modules in the filesystem
    $fileModules = xarModAPIFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Make sure all the core modules are here
    // Remove them from the list if name and regid coincide
    $awol = array();
    include 'modules/installer/xarconfigurations/coremoduleslist.php';
    foreach ($coremodules as $coremodule) {
        if (in_array($coremodule['name'],array_keys($fileModules))) {
            if ($coremodule['regid'] == $fileModules[$coremodule['name']]['regid'])
                unset($fileModules[$coremodule['name']]);
        }
        else $awol[] = $coremodule['name'];
    }

    if (count($awol) != 0) {
        $msg = xarML("Xaraya cannot install bcause the following core modules are missing or corrupted: #(1)",implode(', ', $awol));
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_NOT_EXIST',
                       new SystemException($msg));
        return;
    }

    $basedir = realpath('modules/installer/xarconfigurations');

    $files = array();
    if ($handle = opendir($basedir)) {
        while (false !== ($file = readdir($handle))) {
            if ($file != "." && $file != ".." && !is_dir($file)) $files[] = $file;
        }
        closedir($handle);
    }

    if (!isset($files) || count($files) < 1) {
        $data['warning'] = xarML('There are currently no configuration files available.');
        return $data;
    }

    xarModSetVar('installer','modulelist',serialize($fileModules));
    if (count($fileModules) == 0){
    // No non-core modules present. Show only the minimal configuration
        $names = array();
        include 'modules/installer/xarconfigurations/core.conf.php';
        $names[] = array('value' => 'modules/installer/xarconfigurations/core.conf.php',
                         'display'  => 'Core Xaraya install (aka minimal)',
                         'selected' => true);
    }
    // Add more criteria for filtering the configurations to be displayed here
    else {
    // Show all the configurations
        $names = array();
        foreach ($files as $file) {
            $pos = strrpos($file,'conf.php');
            if($pos == strlen($file)-8) {
                include $basedir . '/' . $file;
                $names[] = array('value' => $basedir . '/' . $file,
                                 'display' => $configuration_name,
                                 'selected' => count($names)==0);
            }
        }
    }
    $data['names'] = $names;

    return $data;
}

/**
 * Choose the configuration options
 *
 * @access public
 * @param create
 * @return bool
 */
function installer_admin_confirm_configuration()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    xarVarSetCached('installer','installing', true);
    xarTplSetPageTemplateName('installer');

    if(!xarVarFetch('configuration', 'isset', $configuration, NULL,  XARVAR_DONT_SET))  return;
    if(!isset($configuration)) {
        $msg = xarML("Please go back and select one of the available configurations.");
        xarErrorSet(XAR_USER_EXCEPTION, 'Please select a configuration', $msg);
        return;
    }

    //I am not sure if these should these break
    if(!xarVarFetch('confirmed',     'isset', $confirmed,     NULL, XARVAR_DONT_SET))   return;
    if(!xarVarFetch('chosen',        'isset', $chosen,        array(),  XARVAR_NOT_REQUIRED))  return;
    if(!xarVarFetch('options',       'isset', $options,       NULL, XARVAR_DONT_SET))   return;

    $data['language'] = $install_language;
    $data['phase'] = 8;
    $data['phase_label'] = xarML('Choose configuration options');

    include $configuration;
    $fileModules = unserialize(xarModGetVar('installer','modulelist'));
    $func = "installer_" . basename(strval($configuration),'.conf.php') . "_moduleoptions";
    $modules = $func();
    $availablemodules = $awolmodules = $installedmodules = array();
    foreach ($modules as $module) {
        if (in_array($module['name'],array_keys($fileModules))) {
            if ($module['regid'] == $fileModules[$module['name']]['regid']) {
                if (xarMod_getState($module['regid']) == XARMOD_STATE_ACTIVE ||
                xarMod_getState($module['regid']) == XARMOD_STATE_INACTIVE) {
                    $installedmodules[] = ucfirst($module['name']);
                }
                else {
                    $availablemodules[] = $module;
                }
                unset($fileModules[$module['name']]);
            }
        }
        else $awolmodules[] = ucfirst($module['name']);
    }

    $options2 = $options3 = array();
    foreach ($availablemodules as $availablemodule) {
//        if(xarMod_getState($availablemodule['regid']) != XARMOD_STATE_MISSING_FROM_UNINITIALISED) {
//            echo var_dump($availablemodule);exit;
            $options2[] = array(
                       'item' => $availablemodule['regid'],
                       'option' => 'true',
                       'comment' => xarML('Install the #(1) module.',ucfirst($availablemodule['name']))
                       );
//        }
    }
    if (!$confirmed) {

        $func = "installer_" . basename(strval($configuration),'.conf.php') . "_privilegeoptions";
        $data['options1'] = $func();
        $data['options2'] = $options2;
        $data['installed'] = implode(', ',$installedmodules);
        $data['missing'] = implode(', ',$awolmodules);
        $data['configuration'] = $configuration;
        return $data;
    }
    else {
        /*********************************************************************
        * Empty the privilege tables
        *********************************************************************/
        $dbconn =& xarDBGetConn();
        $sitePrefix = xarDBGetSiteTablePrefix();
        $query = "DELETE FROM " . $sitePrefix . '_privileges';
        if (!$dbconn->Execute($query)) return;
        $query = "DELETE FROM " . $sitePrefix . '_privmembers';
        if (!$dbconn->Execute($query)) return;
        $query = "DELETE FROM " . $sitePrefix . '_security_acl';
        if (!$dbconn->Execute($query)) return;

        /*********************************************************************
        * Enter some default privileges
        * Format is
        * register(Name,Realm,Module,Component,Instance,Level,Description)
        *********************************************************************/

        xarRegisterPrivilege('Administration','All','All','All','All','ACCESS_ADMIN',xarML('Admin access to all modules'));
        xarRegisterPrivilege('GeneralLock','All','empty','All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));
        xarRegisterPrivilege('LockMyself','All','roles','Roles','Myself','ACCESS_NONE',xarML('Deny access to Myself role'));
        xarRegisterPrivilege('LockEverybody','All','roles','Roles','Everybody','ACCESS_NONE',xarML('Deny access to Everybody role'));
        xarRegisterPrivilege('LockAnonymous','All','roles','Roles','Anonymous','ACCESS_NONE',xarML('Deny access to Anonymous role'));
        xarRegisterPrivilege('LockAdministrators','All','roles','Roles','Administrators','ACCESS_NONE',xarML('Deny access to Administrators role'));
        xarRegisterPrivilege('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
        xarRegisterPrivilege('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));

        /*********************************************************************
        * Arrange the  privileges in a hierarchy
        * Format is
        * makeEntry(Privilege)
        * makeMember(Child,Parent)
        *********************************************************************/

        xarMakePrivilegeRoot('Administration');
        xarMakePrivilegeRoot('GeneralLock');
        xarMakePrivilegeMember('LockMyself','GeneralLock');
        xarMakePrivilegeMember('LockEverybody','GeneralLock');
        xarMakePrivilegeMember('LockAnonymous','GeneralLock');
        xarMakePrivilegeMember('LockAdministrators','GeneralLock');
        xarMakePrivilegeMember('LockAdministration','GeneralLock');
        xarMakePrivilegeMember('LockGeneralLock','GeneralLock');

        /*********************************************************************
        * Assign the default privileges to groups/users
        * Format is
        * assign(Privilege,Role)
        *********************************************************************/

        xarAssignPrivilege('Administration','Administrators');
        xarAssignPrivilege('GeneralLock','Everybody');
        xarAssignPrivilege('GeneralLock','Administrators');
        xarAssignPrivilege('GeneralLock','Users');

        // disable caching of module state in xarMod.php
            $GLOBALS['xarMod_noCacheState'] = true;
            xarModAPIFunc('modules','admin','regenerate');

        // load the modules from the configuration
            foreach ($options2 as $module) {
                if(in_array($module['item'],$chosen)) {
                   $dependents = xarModAPIFunc('modules','admin','getalldependencies',array('regid'=>$module['item']));
                   if (count($dependents['unsatisfiable']) > 0) {
                        $msg = xarML("Cannot load because of unsatisfied dependencies. One or more of the following modules is missing: ");
                        foreach ($dependents['unsatisfiable'] as $dependent) {
                            $modname = isset($dependent['name']) ? $dependent['name'] : "Unknown";
                            $modid = isset($dependent['id']) ? $dependent['id'] : $dependent;
                            $msg .= $modname . " (ID: " . $modid . "), ";
                        }
                        $msg = trim($msg,', ') . ". " . xarML("Please check the listings at www.xaraya.com to identify any modules flagged as 'Unknown'.");
                        $msg .= " " . xarML('Add the missing module(s) to the modules directory and run the installer again.');
                        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_DEPENDENCY', $msg);
                        return;
                   }
                   xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$module['item']));
//                    xarModAPIFunc('modules','admin','activate',array('regid'=>$module['item']));
                }
            }
        $func = "installer_" . basename(strval($configuration),'.conf.php') . "_configuration_load";
        $func($chosen);
        $content['marker'] = '[x]';                                           // create the user menu
        $content['displaymodules'] = 1;
        $content['content'] = '';

        // Load up database
        $dbconn =& xarDBGetConn();
        $tables =& xarDBGetTables();

        $blockGroupsTable = $tables['block_groups'];

        $query = "SELECT    xar_id as id
                  FROM      $blockGroupsTable
                  WHERE     xar_name = 'left'";

        $result =& $dbconn->Execute($query);
        if (!$result) return;

        // Freak if we don't get one and only one result
        if ($result->PO_RecordCount() != 1) {
            $msg = xarML("Group 'left' not found.");
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                           new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
            return;
        }

        list ($leftBlockGroup) = $result->fields;

        $menuBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                     array('module'  => 'base',
                                           'type'=> 'menu'));

        if (empty($menuBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
            return;
        }

        $menuBlockTypeId = $menuBlockType['tid'];

        if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'mainmenu'))) {
            if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                          array('title' => 'Main Menu',
                                'name'  => 'mainmenu',
                                'type'  => $menuBlockTypeId,
                                'groups' => array(array('gid' => $leftBlockGroup,
                                                        'template' => '',)),
                                'template' => '',
                                'content' => serialize($content),
                                'state' => 2))) {
                return;
            }
        }

        xarResponseRedirect(xarModURL('installer', 'admin', 'cleanup'));
    }

}


function installer_admin_cleanup()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarTplSetPageTemplateName('installer');

    xarUserLogOut();
// log in admin user
    $uname = xarModGetVar('roles','lastuser');
    $pass = xarModGetVar('roles','adminpass');
    if (!xarUserLogIn($uname, $pass, 0)) {
        $msg = xarML('Cannot log in the default administrator. Check your setup.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException($msg));
        return false;
    }

    $remove = xarModDelVar('roles','adminpass');
    $remove = xarModDelVar('installer','modules');

    // Load up database
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'right'";

    // Check for db errors
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'right' not found.");
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($rightBlockGroup) = $result->fields;

    $loginBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                    array('module' => 'roles',
                                          'type'   => 'login'));

    if (empty($loginBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }

    $loginBlockTypeId = $loginBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'login'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Login',
                                 'name'     => 'login',
                                 'type'     => $loginBlockTypeId,
                                 'groups'    => array(array('gid'      => $rightBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }

    $query = "SELECT    xar_id as id
              FROM      $blockGroupsTable
              WHERE     xar_name = 'header'";

    // Check for db errors
    $result =& $dbconn->Execute($query);
    if (!$result) return;

    // Freak if we don't get one and only one result
    if ($result->PO_RecordCount() != 1) {
        $msg = xarML("Group 'header' not found.");
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                       new SystemException(__FILE__.'('.__LINE__.'): '.$msg));
        return;
    }

    list ($headerBlockGroup) = $result->fields;

    $metaBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                   array('module' => 'themes',
                                         'type'   => 'meta'));

    if (empty($metaBlockType) && xarCurrentErrorType() != XAR_NO_EXCEPTION) {
        return;
    }

    $metaBlockTypeId = $metaBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'meta'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Meta',
                                 'name'     => 'meta',
                                 'type'     => $metaBlockTypeId,
                                 'groups'    => array(array('gid'      => $headerBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
            return;
        }
    }

    xarModAPIFunc('adminpanels', 'admin', 'updatemenudb',array('force' => true));

    $data['language']    = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Step Six');
    $data['finalurl'] = xarModURL('installer', 'admin', 'finish');

    return $data;
}


function installer_admin_finish()
{
    xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true));
    xarResponseRedirect('index.php');
}
?>

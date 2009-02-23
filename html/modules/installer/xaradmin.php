<?php
/**
 * Installer
 *
 * @package Installer
 * @copyright (C) copyright-placeholder
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Installer
 * @link http://xaraya.com/index.php/release/200.html
 */

/* Do not allow this script to run if the install script has been removed.
 * This assumes the install.php and index.php are in the same directory.
 * @author Paul Rosania
 * @author Marcel van der Boom <marcel@hsdev.com>
 */
if (!file_exists('install.php')) { throw new Exception('Already installed');}

/* FOR UPGRADE: Add instruction text needed before upgrade for a specific upgrade to admin-upgrade1.xd
                Add upgrade code to installer_admin_upgrade2() function, currently sorted by version upgrade
                Any misc upgrade scripts not related to any specific version to installer_admin_upgrade3() eg flush cache
*/
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
            //$fileName = sys::varpath() . "/locales/$locale/locale.xml";
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
    xarVarFetch('retry','int:1',$data['retry'],NULL, XARVAR_NOT_REQUIRED);

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

    $retry=1;

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        xarResponseRedirect('install.php?install_phase=2&install_language='.$install_language.'&retry=1');
    }

    //Defaults
    $systemConfigIsWritable   = false;
    $cacheTemplatesIsWritable = false;
    $rssTemplatesIsWritable   = false;
    $metRequiredPHPVersion    = false;

    $systemVarDir             = sys::varpath();
    $cacheDir                 = $systemVarDir . XARCORE_CACHEDIR;
    $cacheTemplatesDir        = $systemVarDir . XARCORE_TPL_CACHEDIR;
    $rssTemplatesDir          = $systemVarDir . XARCORE_RSS_CACHEDIR;
    $systemConfigFile         = $systemVarDir . '/' . sys::CONFIG;
    $phpLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/php';
    $xmlLanguageDir           = $systemVarDir . '/locales/' . $install_language . '/xml';

    if (function_exists('version_compare')) {
        if (version_compare(PHP_VERSION,'5.2','>=')) $metRequiredPHPVersion = true;
    }

    $systemConfigIsWritable     = is_writable($systemConfigFile);
    $cacheIsWritable            = check_dir($cacheDir);
    $cacheTemplatesIsWritable   = (check_dir($cacheTemplatesDir) || @mkdir($cacheTemplatesDir, 0700));
    $rssTemplatesIsWritable     = (check_dir($rssTemplatesDir) || @mkdir($rssTemplatesDir, 0700));
    $phpLanguageFilesIsWritable = xarMLS__iswritable($phpLanguageDir);
    $xmlLanguageFilesIsWritable = xarMLS__iswritable($xmlLanguageDir);
    $maxexectime = trim(ini_get('max_execution_time'));
    $memLimit = trim(ini_get('memory_limit'));
    $memLimit = empty($memLimit) ? xarML('Undetermined') : $memLimit;
    $memVal = substr($memLimit,0,strlen($memLimit)-1);
    switch(strtolower($memLimit{strlen($memLimit)-1})) {
        case 'g': $memVal *= 1024;
        case 'm': $memVal *= 1024;
        case 'k': $memVal *= 1024;
    }

    // Extension Check
    $data['xmlextension']             = extension_loaded('xml');
    $data['mysqlextension']           = extension_loaded('mysql');
    $data['pgsqlextension']           = extension_loaded('pgsql');
    $data['sqliteextension']          = extension_loaded('sqlite');
    $data['pdosqliteextension']       = extension_loaded('pdo_sqlite');

    $data['metRequiredPHPVersion']    = $metRequiredPHPVersion;
    $data['phpVersion']               = PHP_VERSION;
    $data['cacheDir']                 = $cacheDir;
    $data['cacheIsWritable']          = $cacheIsWritable;
    $data['cacheTemplatesDir']        = $cacheTemplatesDir;
    $data['cacheTemplatesIsWritable'] = $cacheTemplatesIsWritable;
    $data['rssTemplatesDir']          = $rssTemplatesDir;
    $data['rssTemplatesIsWritable']   = $rssTemplatesIsWritable;
    $data['systemConfigFile']         = $systemConfigFile;
    $data['systemConfigIsWritable']   = $systemConfigIsWritable;
    $data['phpLanguageDir']             = $phpLanguageDir;
    $data['phpLanguageFilesIsWritable'] = $phpLanguageFilesIsWritable;
    $data['xmlLanguageDir']             = $xmlLanguageDir;
    $data['xmlLanguageFilesIsWritable'] = $xmlLanguageFilesIsWritable;
    $data['maxexectime']                = $maxexectime;
    $data['maxexectimepass']            = $maxexectime<=30;
    $data['memory_limit']               = $memLimit;
    $data['memory_warning']             = $memLimit == xarML('Undetermined');
    $data['metMinMemRequirement']       = $memVal >= 8 * 1024 * 1024 || $data['memory_warning'];

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
    $data['database_host']       = xarSystemVars::get(sys::CONFIG, 'DB.Host');
    $data['database_username']   = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
    $data['database_password']   = '';
    $data['database_name']       = xarSystemVars::get(sys::CONFIG, 'DB.Name');
    $data['database_prefix']     = xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix');
    $data['database_type']       = xarSystemVars::get(sys::CONFIG, 'DB.Type');
    // Supported  Databases:
    $data['database_types']      = array('mysql'       => array('name' => 'MySQL'   , 'available' => extension_loaded('mysql')),
                                         'postgres'    => array('name' => 'Postgres', 'available' => extension_loaded('pgsql')),
                                         'sqlite'      => array('name' => 'SQLite'  , 'available' => extension_loaded('sqlite')),
                                         //'pdosqlite'   => array('name' => 'PDO SQLite'  , 'available' => extension_loaded('pdo_sqlite')),
                                         // use portable version of OCI8 driver to support ? bind variables
                                         'oci8po'      => array('name' => 'Oracle 9+ (not supported)'  , 'available' => extension_loaded('oci8')),
                                         'mssql'       => array('name' => 'MS SQL Server (not supported)' , 'available' => extension_loaded('mssql')),
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
        throw new Exception($msg);
    }

    // allow only a-z 0-9 and _ in table prefix
    if (!preg_match('/^\w*$/',$dbPrefix)) {
        $msg = xarML('Invalid character in table prefix. Only use a-z, a _ and/or 0-9 in the prefix.');
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

    $init_args =  array('userName'     => $dbUname,
                        'password'     => $dbPass,
                        'databaseHost' => $dbHost,
                        'databaseType' => $dbType,
                        'databaseName' => $dbName,
                        'prefix'       => $dbPrefix,
                        'doConnect'    => false);

    sys::import('xaraya.database');
    xarDB_Init($init_args);

    // Not all Database Servers support selecting the specific db *after* connecting
    // so let's try connecting with the dbname first, and then without if that fails
    $dbExists = false;
    try {
      $dbconn = xarDBNewConn($init_args);
      $dbExists = true;
    } catch(Exception $e) {
      // Couldn't connect to the specified dbName
      // Let's try without db name
      try {
        $init_args['databaseName'] ='';
        $dbconn = xarDBNewConn($init_args);
      } catch(Exception $ex) {
        // It failed without dbname too
        $msg = xarML('Database connection failed. The information supplied was erroneous, such as a bad or missing password or wrong username.
                          The message was: ' . $ex->getMessage());
        throw new Exception($msg);
      }
    }

    if (!$createDB && !$dbExists) {
        $msg = xarML('Database #(1) doesn\'t exist and it wasn\'t selected to be created. <br />
        Please go back and either check the checkbox to create the database or choose a database that already exists.', $dbName);
        die($msg);
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
        // Gots to ask confirmation
        return $data;
    }

    sys::import('xaraya.tableddl');
    // Create the database if necessary
    if ($createDB) {
        $data['confirmDB']  = true;
        //Let's pass all input variables thru the function argument or none, as all are stored in the system.config.php
        //Now we are passing all, let's see if we gain consistency by loading config.php already in this phase?
        //Probably there is already a core function that can make that for us...
        //the config.system.php is lazy loaded in xarSystemVars::get(sys::CONFIG, $name), which means we cant reload the values
        // in this phase... Not a big deal 'though.
        if ($dbExists) {
            if (!$dbconn->Execute('DROP DATABASE ' . $dbName)) return;
        }
        if(!$dbconn->Execute(xarDBCreateDatabase($dbName,$dbType))) {
          //if (!xarInstallAPIFunc('createdb', $config_args)) {
          $msg = xarML('Could not create database (#(1)). Check if you already have a database by that name and remove it.', $dbName);
          throw new Exception($msg);
        }
    }
    else {
        $removetables = true;
    }

    // Re-init with the new values and connect
    $systemArgs = array('userName' => $dbUname,
                        'password' => $dbPass,
                        'databaseHost' => $dbHost,
                        'databaseType' => $dbType,
                        'databaseName' => $dbName,
                        'prefix' => $dbPrefix);
    // Connect to database
    xarDB_init($systemArgs);

    // drop all the tables that have this prefix
    //TODO: in the future need to replace this with a check further down the road
    // for which modules are already installed

    if (isset($removetables) && $removetables) {
        $dbconn = xarDB::getConn();
        $dbinfo = $dbconn->getDatabaseInfo();
        try {
            $dbconn->begin();
            foreach($dbinfo->getTables() as $tbl) {
                $table = $tbl->getName();
                if(strpos($table,'_') && (substr($table,0,strpos($table,'_')) == $dbPrefix)) {
                    // we have the same prefix.
                    try {
                        $sql = xarDBDropTable($table,$dbType);
                        $dbconn->Execute($sql);
                    } catch(SQLException $dropfail) {
                        // retry with drop view
                        // TODO: this should be transparent in the API
                        $ddl = "DROP VIEW $table";
                        $dbconn->Execute($ddl);
                    }
                }
            }
            $dbconn->commit();
        } catch (Exception $e) {
            // All other exceptions but the ones we already handled
            $dbconn->rollback();
            throw $e;
        }
    }

    // install the security stuff here, but disable the registerMask and
    // and xarSecurityCheck functions until we've finished the installation process
    sys::import('xaraya.security');
    sys::import('xaraya.modules');

    $modules = array('base','modules','roles','privileges');
    foreach ($modules as $module)
        if (!xarInstallAPIFunc('initialise', array('directory' => $module,'initfunc'  => 'init'))) return;;

    $prefix = xarDB::getPrefix();
    $modulesTable = $prefix .'_modules';
    $tables =& xarDB::getTables();

    $newModSql   = "INSERT INTO $modulesTable
                    (name, regid, directory,
                     version, class, category, admin_capable, user_capable, state)
                    VALUES (?,?,?,?,?,?,?,?,?)";
    $newStmt     = $dbconn->prepareStatement($newModSql);


    $modules = array('authsystem','roles','privileges','installer','blocks','themes');
    // Series of updates, begin transaction
    try {
        $dbconn->begin();
        foreach($modules as $index => $modName) {
            // Insert module
            $modversion=array();$bindvars = array();
            // NOTE: We can not use the sys::import here, since the variable scope is important.
            include_once "modules/$modName/xarversion.php";
            $bindvars = array($modName,
                              $modversion['id'],       // regid, from xarversion
                              $modName,
                              $modversion['version'],
                              $modversion['class'],
                              $modversion['category'],
                              isset($modversion['admin'])?$modversion['admin']:0,
                              isset($modversion['user'])?$modversion['user']:0,
                              3);
            $result = $newStmt->executeUpdate($bindvars);
            $newModId = $dbconn->getLastId($tables['modules']);
        }
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    $modules = array('blocks','authsystem','themes');
    foreach ($modules as $module)
        if (!xarInstallAPIFunc('initialise', array('directory' => $module,'initfunc'  => 'init'))) return;;

    if (!xarInstallAPIFunc('initialise', array('directory'=>'authsystem', 'initfunc'=>'activate'))) return;;

    // TODO: move this to some common place in Xaraya ?
    // Register BL user tags
    // Include a JavaScript file in a page
    xarTplRegisterTag('base', 'base-include-javascript', array(),'base_javascriptapi_handlemodulejavascript');
    // Render JavaScript in a page
    xarTplRegisterTag('base', 'base-render-javascript', array(),'base_javascriptapi_handlerenderjavascript');

    // TODO: is this is correct place for a default value for a modvar?
    xarModVars::set('base', 'AlternatePageTemplate', 'homepage');

    // If we are here, the base system has completed
    // We can now pass control to xaraya.
    sys::import('xaraya.variables');

    $a = array();
    xarVar_init($a);
    xarConfigVars::set(null, 'Site.MLS.DefaultLocale', $install_language);

    // Set the allowed locales to our "C" locale and the one used during installation
    // TODO: make this a bit more friendly.
    $necessaryLocale = array('en_US.utf-8');
    $install_locale  = array($install_language);
    $allowed_locales = array_merge($necessaryLocale, $install_locale);

    xarConfigVars::set(null, 'Site.MLS.AllowedLocales',$allowed_locales);    $data['language'] = $install_language;

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

    // load modules into *_modules table
    if (!xarModAPIFunc('modules', 'admin', 'regenerate'))
        throw new Exception("regenerating module list failed");

     // Initialise and activate dynamic data
    $modlist = array('dynamicdata');
    foreach ($modlist as $mod) {
        // Initialise the module
        $regid = xarModGetIDFromName($mod);
        if (isset($regid)) {
            if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $regid)))
                 throw new Exception("Initalising module with regid : $regid failed");
            // Activate the module
            if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $regid)))
                throw new Exception("Activating module with regid: $regid failed");
        }
    }

# --------------------------------------------------------
# Create DD configuration and sample objects
#
    $objects = array(
                   'configurations',
                   'dynamicdata_tablefields',
                   'sample',
                     );

    if(!xarModAPIFunc('modules','admin','standardinstall',array('module' => 'dynamicdata', 'objects' => $objects))) return;

# --------------------------------------------------------
# Create wrapper DD overlay objects for the modules and roles modules
#
    $objects = array(
                   'modules',
//                   'modules_hooks',
//                   'modules_modvars',
                     );

    if(!xarModAPIFunc('modules','admin','standardinstall',array('module' => 'modules', 'objects' => $objects))) return;

    $objects = array(
                   'roles_roles',
                   'roles_users',
                   'roles_groups',
                     );

    if(!xarModAPIFunc('modules','admin','standardinstall',array('module' => 'roles', 'objects' => $objects))) return;

    // create the default roles and privileges setup
    sys::import('modules.privileges.xarsetup');
    initializeSetup();

    // Set up default user properties, etc.

    // load modules into *_modules table
    if (!xarModAPIFunc('modules', 'admin', 'regenerate')) return;


    $regid = xarModGetIDFromName('authsystem');
    if (empty($regid)) {
        die(xarML('I cannot load the Authsystem module. Please make it available and reinstall'));
    }

    // Set the state and activate the following modules
    // jojodee - Modules, authsystem, base, installer, blocks and themes are already activated in base init
    // We run them through roles and privileges as special cases that need an 'activate' phase. Others don't.
   $modlist = array('roles','privileges');
    foreach ($modlist as $mod) {
        // Set state to inactive first
        $regid=xarModGetIDFromName($mod);
        if (!xarModAPIFunc('modules','admin','setstate',
                           array('regid'=> $regid, 'state'=> XARMOD_STATE_INACTIVE)))
            throw new Exception("setting state of $regid failed");//return;

        // Activate the module
        if (!xarModAPIFunc('modules','admin','activate',
                           array('regid'=> $regid)))
            throw new Exception("activation of $regid failed");//return;
    }

    // load themes into *_themes table
    if (!xarModAPIFunc('themes', 'admin', 'regenerate')) {
        throw new Exception("themes regeneration failed");
    }

    // Set the state and activate the following themes
    $themelist = array('print','rss','Xaraya_Classic');
    foreach ($themelist as $theme) {
        // Set state to inactive
        $regid = xarThemeGetIDFromName($theme);
        if (isset($regid)) {
            if (!xarModAPIFunc('themes','admin','setstate', array('regid'=> $regid,'state'=> XARTHEME_STATE_INACTIVE))){
                throw new Exception("Setting state of theme with regid: $regid failed");
            }
            // Activate the theme
            if (!xarModAPIFunc('themes','admin','activate', array('regid'=> $regid)))
            {
                throw new Exception("Activation of theme with regid: $regid failed");
            }
        }
    }

    // Initialise and activate mail
    $regid = xarModGetIDFromName('mail');
    if (!xarModAPIFunc('modules', 'admin', 'initialise', array('regid' => $regid)))
        throw new Exception("Initalising module with regid : $regid failed");
    // Activate the module
    if (!xarModAPIFunc('modules', 'admin', 'activate', array('regid' => $regid)))
        throw new Exception("Activating module with regid: $regid failed");

    //initialise and activate base module by setting the states
    $baseId = xarModGetIDFromName('base');
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => $baseId, 'state' => XARMOD_STATE_INACTIVE)))
        throw new Exception("Setting state for module with regid: $baseId failed");
    // Set module state to active
    if (!xarModAPIFunc('modules', 'admin', 'setstate', array('regid' => $baseId, 'state' => XARMOD_STATE_ACTIVE)))
        throw new Exception("Activating base $baseId module failed");

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

    sys::import('modules.roles.class.roles');
    $role = xarRoles::ufindRole('admin');

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

    xarModVars::set('mail', 'adminname', $name);
    xarModVars::set('mail', 'adminmail', $email);
    xarModVars::set('themes', 'SiteCopyRight', '&copy; Copyright ' . date("Y") . ' ' . $name);

    if ($pass != $pass1) {
        $msg = xarML('The passwords do not match');
        throw new Exception($msg);
    }

    if (empty($userName)) {
        $msg = xarML('You must provide a preferred username to continue.');
        throw new Exception($msg);
    }
    // check for spaces in the username
    if (preg_match("/[[:space:]]/",$userName)) {
        $msg = xarML('There is a space in the username.');
        throw new Exception($msg);
    }
    // check the length of the username
    if (strlen($userName) > 255) {
        $msg = xarML('Your username is too long.');
        throw new Exception($msg);
    }

    // assemble the args into an array for the role constructor
    $args =  array('itemid'     => $role->getID(),
                   'name'       => $name,
                   'uname'      => $userName,
                   'email'      => $email,
                   // CHECKME: can we transform this in the dproperty
                   'password'   => $pass,
                   'state'      => ROLES_STATE_ACTIVE);

    xarModVars::set('roles', 'lastuser', $userName);
    xarModVars::set('roles', 'adminpass', $pass);// <-- come again? why store the pass?

    //Try to update the role to the repository and bail if an error was thrown
    $modifiedrole = $role->updateItem($args);
    if (!$modifiedrole) {return;}

    // Register Block types from modules installed before block apis (base)
    $blocks = array('adminmenu','waitingcontent','finclude','html','menu','php','text','content');

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
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();

    $blockGroupsTable = $tables['block_groups'];

    $query = "SELECT    id as id
              FROM      $blockGroupsTable
              WHERE     name = ?";
    $result = $dbconn->Execute($query,array('left'));

    // Freak if we don't get one and only one result
    if ($result->getRecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        throw new Exception($msg);
    }

    list ($leftBlockGroup) = $result->fields;

    $adminBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                    array('module'  => 'base',
                                          'type'    => 'adminmenu'));

    $adminBlockTypeId = $adminBlockType['tid'];
    assert('is_numeric($adminBlockTypeId);');
    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'adminpanel'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Admin',
                                 'name'     => 'adminpanel',
                                 'type'     => $adminBlockTypeId,
                                 'groups'   => array(array('id'      => $leftBlockGroup,
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

    $htmlBlockTypeId = $htmlBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'reminder'))) {
        if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Reminder',
                                 'name'     => 'reminder',
                                 'content'  => $msg,
                                 'type'     => $htmlBlockTypeId,
                                 'groups'   => array(array('id'      => $leftBlockGroup,
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
        $msg = xarML("Xaraya cannot install because the following core modules are missing or corrupted: #(1)",implode(', ', $awol));
        throw new Exception($msg);
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

    xarModVars::set('installer','modulelist',serialize($fileModules));
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
        throw new Exception($msg);
    }

    //I am not sure if these should these break
    if(!xarVarFetch('confirmed',     'isset', $confirmed,     NULL, XARVAR_DONT_SET))   return;
    if(!xarVarFetch('chosen',        'isset', $chosen,        array(),  XARVAR_NOT_REQUIRED))  return;
    if(!xarVarFetch('options',       'isset', $options,       NULL, XARVAR_DONT_SET))   return;

    $data['language'] = $install_language;
    $data['phase'] = 8;
    $data['phase_label'] = xarML('Choose configuration options');

    include $configuration;
    $fileModules = unserialize(xarModVars::get('installer','modulelist'));
    $func = "installer_" . basename(strval($configuration),'.conf.php') . "_moduleoptions";
    $modules = $func();
    $availablemodules = $awolmodules = $installedmodules = array();
    foreach ($modules as $module) {
        if (in_array($module['name'],array_keys($fileModules))) {
            if ($module['regid'] == $fileModules[$module['name']]['regid']) {
                $modInfo = xarModGetInfo($module['regid']);
                if ($modInfo['state'] == XARMOD_STATE_ACTIVE ||
                    $modInfo['state'] == XARMOD_STATE_INACTIVE) {
                    $installedmodules[] = ucfirst($module['name']);
                } else {
                    $availablemodules[] = $module;
                }
                unset($fileModules[$module['name']]);
            }
        }
        else $awolmodules[] = ucfirst($module['name']);
    }

    $options2 = $options3 = array();
    foreach ($availablemodules as $availablemodule) {
        $options2[] = array(
                            'item' => $availablemodule['regid'],
                            'option' => 'true',
                            'comment' => xarML('Install the #(1) module.',ucfirst($availablemodule['name']))
                            );
    }
    if (!$confirmed) {

        $func = "installer_" . basename(strval($configuration),'.conf.php') . "_privilegeoptions";
        $data['options1'] = $func();
        $data['options2'] = $options2;
        $data['options3'] = $options3;
        $data['installed'] = implode(', ',$installedmodules);
        $data['missing'] = implode(', ',$awolmodules);
        $data['configuration'] = $configuration;
        return $data;
    } else {

        /*********************************************************************
        * Enter some default privileges
        * Format is
        * register(Name,Realm,Module,Component,Instance,Level,Description)
        *********************************************************************/

        xarRegisterPrivilege('Administration','All','All','All','All','ACCESS_ADMIN',xarML('Admin access to all modules'));
        xarRegisterPrivilege('GeneralLock','All',null,'All','All','ACCESS_NONE',xarML('A container privilege for denying access to certain roles'));
        xarRegisterPrivilege('LockMyself','All','roles','Roles','Myself','ACCESS_NONE',xarML('Deny access to Myself role'));
        xarRegisterPrivilege('LockEverybody','All','roles','Roles','Everybody','ACCESS_NONE',xarML('Deny access to Everybody role'));
        xarRegisterPrivilege('LockAnonymous','All','roles','Roles','Anonymous','ACCESS_NONE',xarML('Deny access to Anonymous role'));
        xarRegisterPrivilege('LockAdministrators','All','roles','Roles','Administrators','ACCESS_NONE',xarML('Deny access to Administrators role'));
        xarRegisterPrivilege('LockAdministration','All','privileges','Privileges','Administration','ACCESS_NONE',xarML('Deny access to Administration privilege'));
        xarRegisterPrivilege('LockGeneralLock','All','privileges','Privileges','GeneralLock','ACCESS_NONE',xarML('Deny access to GeneralLock privilege'));

        /*********************************************************************
        * Arrange the  privileges in a hierarchy
        * Format is
        * xarMakePrivilegeMember(Child,Parent)
        *********************************************************************/

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
                    throw new Exception($msg);
                }
                xarModAPIFunc('modules','admin','installwithdependencies',array('regid'=>$module['item']));
            }
        }
        $func = "installer_" . basename(strval($configuration),'.conf.php') . "_configuration_load";
        $func($chosen);
        $content['marker'] = '[x]';                                           // create the user menu
        $content['displaymodules'] = 'All';
        $content['modulelist'] = '';
        $content['content'] = '';

        // Load up database
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        $blockGroupsTable = $tables['block_groups'];

        $query = "SELECT    id as id
                  FROM      $blockGroupsTable
                  WHERE     name = ?";

        $result =& $dbconn->Execute($query,array('left'));

        // Freak if we don't get one and only one result
        if ($result->getRecordCount() != 1) {
            $msg = xarML("Group 'left' not found.");
            throw new Exception($msg);
        }

        list ($leftBlockGroup) = $result->fields;

        $menuBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                     array('module'  => 'base',
                                           'type'=> 'menu'));


        $menuBlockTypeId = $menuBlockType['tid'];

        if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'mainmenu'))) {
            if (!xarModAPIFunc('blocks', 'admin', 'create_instance',
                          array('title' => 'Main Menu',
                                'name'  => 'mainmenu',
                                'type'  => $menuBlockTypeId,
                                'groups' => array(array('id' => $leftBlockGroup,
                                                        'template' => '',)),
                                'template' => '',
                                'content' => serialize($content),
                                'state' => 2))) {
                return;
            }
        }
     //TODO: Check why this var is being reset to null in sqlite install - reset here for now to be sure
     //xarModVars::set('roles', 'defaultauthmodule', xarModGetIDFromName('authsystem'));

        xarResponseRedirect(xarModURL('installer', 'admin', 'cleanup'));
    }

}


function installer_admin_cleanup()
{
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarTplSetPageTemplateName('installer');

    xarUserLogOut();
    // log in admin user
    $uname = xarModVars::get('roles','lastuser');
    $pass = xarModVars::get('roles','adminpass');

    if (!xarUserLogIn($uname, $pass, 0)) {
        $msg = xarML('Cannot log in the default administrator. Check your setup.');
        throw new Exception($msg);
    }


//    xarModVars::delete('roles','adminpass');
//    xarModVars::delete('installer','modules');

    // Load up database
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();

    $blockGroupsTable = $tables['block_groups'];

    // Prepare getting one blockgroup
    $query = "SELECT    id as id
              FROM      $blockGroupsTable
              WHERE     name = ?";
    $stmt = $dbconn->prepareStatement($query);

    // Execute for the right blockgroup
    $result = $stmt->executeQuery(array('right'));

    // Freak if we don't get one and only one result
    if ($result->getRecordCount() != 1) {
        $msg = xarML("Group 'right' not found.");
        throw new Exception($msg);
    }
    $result->next();
    list ($rightBlockGroup) = $result->fields;

    $loginBlockTypeId = xarModAPIFunc('blocks','admin','register_block_type',
                    array('modName' => 'authsystem', 'blockType' => 'login'));
    if (empty($loginBlockTypeId)) {
        return;
    }

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'login'))) {
        if (xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Login',
                                 'name'     => 'login',
                                 'type'     => $loginBlockTypeId,
                                 'groups'    => array(array('id'     => $rightBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
        } else {
            throw new Exception('Could not create login block');
        }
    } else {
        throw new Exception('Login block created too early?');
    }

    // Same query, but for header group.
    $result = $stmt->executeQuery(array('header'));

    xarLogMessage("Selected the header block group", XARLOG_LEVEL_ERROR);
    // Freak if we don't get one and only one result
    if ($result->getRecordCount() != 1) {
        $msg = xarML("Group 'header' not found.");
        throw new Exception($msg);
    }

    $result->next();
    list ($headerBlockGroup) = $result->fields;

    $metaBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                   array('module' => 'themes',
                                         'type'   => 'meta'));

    $metaBlockTypeId = $metaBlockType['tid'];

    if (!xarModAPIFunc('blocks', 'user', 'get', array('name'  => 'meta'))) {
        if (xarModAPIFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Meta',
                                 'name'     => 'meta',
                                 'type'     => $metaBlockTypeId,
                                 'groups'    => array(array('id'      => $headerBlockGroup,
                                                           'template' => '')),
                                 'template' => '',
                                 'state'    => 2))) {
        } else {
            throw new Exception('Could not create meta block');
        }
    }

    xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true));

    $data['language']    = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Step Six');
    $data['finalurl'] = xarModURL('installer', 'admin', 'finish');

    return $data;
}


function installer_admin_finish()
{
    xarVarFetch('returnurl', 'str', $returnurl, 'site', XARVAR_NOT_REQUIRED);

# --------------------------------------------------------
# Create wrapper DD overlay objects for the privileges modules
#
    $objects = array(
                   'privileges_baseprivileges',
                   'privileges_privileges',
                     );

    if(!xarModAPIFunc('modules','admin','standardinstall',array('module' => 'privileges', 'objects' => $objects))) return;

    $machinetz = date_default_timezone_get();
    xarConfigVars::set(null, 'System.Core.TimeZone', $machinetz);
    xarConfigVars::set(null, 'Site.Core.TimeZone', $machinetz);

    switch ($returnurl) {
        case ('modules'):
            xarResponseRedirect(xarModURL('modules','admin','list'));
        case ('blocks'):
            xarResponseRedirect(xarModURL('blocks','admin','view_instances'));
        case ('site'):
        default:
            xarResponseRedirect('index.php');
    }
    return true;
}

function installer_admin_upgrade1()
{
    $data['xarProduct'] = xarConfigVars::get(null, 'System.Core.VersionId');
    $data['xarVersion'] = xarConfigVars::get(null, 'System.Core.VersionNum');
    $data['xarRelease'] = xarConfigVars::get(null, 'System.Core.VersionSub');
    $data['descr'] = xarML('Now preparing to run an upgrade from prior #(1) Version <strong>#(2)</strong> (release #(3))
                    to #(4) version <strong>#(5)</strong> (release #(6))',
                    $data['xarProduct'],$data['xarVersion'],$data['xarRelease'],
                    xarCore::VERSION_ID, xarCore::VERSION_NUM, xarCore::VERSION_SUB);
        $data['$title'] = xarML('Xaraya Upgrade');

    if (xarCore::VERSION_NUM == $data['xarVersion']) {
        $data['alreadydone']=xarML('You have already upgraded to #(1). The upgrade script only needs to run once.', $data['xarVersion']);
    }else{
        $data['alreadydone']='';
    }

    if ($data['xarVersion'] < '1.0') {
        $data['downloadit']="<a href=\"http://www.xaraya.com/index.php/docs/75\">Xaraya</a>";
        $data['versionlow']=
        xarML('<p>Your current site is <strong>Xaraya Version #(1)</strong>.
                You must have at least Xaraya Version <strong>1.0.0</strong> installed before continuing to upgrade to the latest release.</p>
               <p>Please download the latest Xaraya Core 1.0.x version at #(2) and upgrade your current site before continuing.</p>', $data['xarVersion'], $data['downloadit']);
    }else{
        $data['versionlow']='';
    }
    $data['phase'] = 1;
    $data['phase_label'] = xarML('Step One');

    return $data;
}

/**
 * Upgrades necessary since Version 1.0.0 RC1
 * Arranged by versions
 */
function installer_admin_upgrade2()
{
     $thisdata['finishearly']=0;
     $thisdata['xarProduct'] = xarConfigVars::get(null, 'System.Core.VersionId');
     $thisdata['xarVersion'] = xarConfigVars::get(null, 'System.Core.VersionNum');
     $thisdata['xarRelease'] = xarConfigVars::get(null, 'System.Core.VersionSub');

     //Load this early
     sys::import('xaraya.tableddl');

     $prefix = xarDB::getPrefix();
     $dbconn =& xarDB::getConn();
/**
 * Version 1.0 Release Upgrades
 * Version 1.0 Release candidate upgrades are also included here
 *             to ensure any version 1.0 installs are upgraded appropriately
 */

    $content = "<p><strong>Checking Site Configuration Variables Structure</strong></p>";

    $cookiename = xarConfigVars::get(null, 'Site.Session.CookieName');
    if (!isset($cookiename)) {
        xarConfigVars::set(null, 'Site.Session.CookieName', '');
        $content .= "<p>Site.Session.CookieName incorrect, attempting to set.... done!</p>";
    }
    $cookiepath = xarConfigVars::get(null, 'Site.Session.CookiePath');
    if (!isset($cookiepath)) {
        xarConfigVars::set(null, 'Site.Session.CookiePath', '');
        $content .= "<p>Site.Session.CookiePath incorrect, attempting to set.... done!</p>";
    }
    $cookiedomain = xarConfigVars::get(null, 'Site.Session.CookieDomain');
    if (!isset($cookiedomain)) {
        xarConfigVars::set(null, 'Site.Session.CookieDomain', '');
        $content .= "<p>Site.Session.CookieDomain incorrect, attempting to set.... done!</p>";
    }
    $referercheck = xarConfigVars::get(null, 'Site.Session.RefererCheck');
    if (!isset($referercheck)) {
        xarConfigVars::set(null, 'Site.Session.RefererCheck', '');
        $content .= "<p>Site.Session.RefererCheck incorrect, attempting to set.... done!</p>";
    }

    // after 0911, make sure CSS class lib is deployed and css tags are registered
    $content .= "<p><strong>Making sure CSS tags are registered</strong></p>";
    if(!xarModAPIFunc('themes', 'css', 'registercsstags')) {
        $content .= "<p>FAILED to register CSS tags</p>";
    } else {
        $content .= "<p>CSS tags registered successfully, css subsystem is ready to be deployed.</p>";
    }

    // Set the fallback locale for logged in users to be empty
    // If there is not modvar/moduservar the locale falls back to the session, 
    // and then to the default site locale
    xarModVars::set('roles', 'locale', '');

  $content .= "<p><strong>Checking <strong>include/properties</strong> directory for moved DD properties</strong></p>";
    //From 1.0.0rc2 propsinplace was merged and dd propertie began to move to respective modules
    //Check they don't still exisit in the includes directory  bug 4371
    // set the array of properties that have moved
    $ddmoved=array(
        array('Dynamic_AIM_Property.php',1,'Roles'),
        array('Dynamic_Affero_Property.php',1,'Roles'),
        array('Dynamic_Array_Property.php',1,'Base'),
        array('Dynamic_Categories_Property.php',0,'Categories'),
        array('Dynamic_CheckboxList_Property.php',1,'Base'),
        array('Dynamic_CheckboxMask_Property.php',1,'Base'),
        array('Dynamic_Checkbox_Property.php',1,'Base'),
        array('Dynamic_Combo_Property.php',1,'Base'),
        array('Dynamic_CommentsNumberOf_Property.php',0,'Comments'),
        array('Dynamic_Comments_Property.php',0,'Comments'),
        array('Dynamic_CountryList_Property.php',1,'Base'),
        array('Dynamic_DateFormat_Property.php',1,'Base'),
        array('Dynamic_Email_Property.php',1,'Roles'),
        array('Dynamic_ExtendedDate_Property.php',1,'Base'),
        array('Dynamic_FileUpload_Property.php',1,'Roles'),
        array('Dynamic_FloatBox_Property.php',1,'Roles'),
        array('Dynamic_HTMLArea_Property.php',0,'HTMLArea'),
        array('Dynamic_HTMLPage_Property.php',1,'Base'),
        array('Dynamic_HitCount_Property.php',0,'HitCount'),
        array('Dynamic_ICQ_Property.php',1,'Roles'),
        array('Dynamic_ImageList_Property.php',1,'Roles'),
        array('Dynamic_Image_Property.php',1,'Roles'),
        array('Dynamic_LanguageList_Property.php',1,'Base'),
        array('Dynamic_LogLevel_Property.php',0,'Logconfig'),
        array('Dynamic_MSN_Property.php',1,'Roles'),
        array('Dynamic_MultiSelect_Property.php',1,'Base'),
        array('Dynamic_NumberBox_Property.php',1,'Base'),
        array('Dynamic_NumberList_Property.php',1,'Base'),
        array('Dynamic_PassBox_Property.php',1,'Base'),
        array('Dynamic_PayPalCart_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalDonate_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalNow_Property.php',0,'Paypalsetup'),
        array('Dynamic_PayPalSubscription_Property.php',0,'Paypalsetup'),
        array('Dynamic_RadioButtons_Property.php',1,'Base'),
        array('Dynamic_Rating_Property.php',0,'Ratings'),
        array('Dynamic_Select_Property.php',0,'Base'),
        array('Dynamic_SendToFriend_Property.php',0,'Recommend'),
        array('Dynamic_StateList_Property.php',1,'Base'),
        array('Dynamic_StaticText_Property.php',1,'Base'),
        array('Dynamic_Status_Property.php',0,'Articles'),
        array('Dynamic_TextArea_Property.php',1,'Base'),
        array('Dynamic_TextBox_Property.php',1,'Base'),
        array('Dynamic_TextUpload_Property.php',1,'Base'),
        array('Dynamic_TinyMCE_Property.php',0,'TinyMCE'),
        array('Dynamic_URLIcon_Property.php',1,'Base'),
        array('Dynamic_URLTitle_Property.php',1,'Base'),
        array('Dynamic_URL_Property.php',1,'Roles'),
        array('Dynamic_Upload_Property.php',0,'Uploads'),
        array('Dynamic_Yahoo_Property.php',1,'Roles'),
        array('Dynamic_Calendar_Property.php',1,'Base'),
        array('Dynamic_TColorPicker_Property.php',1,'Base'),
        array('Dynamic_TimeZone_Property.php',1,'Base'),
        array('Dynamic_Module_Property.php',1,'Modules'),
        array('Dynamic_GroupList_Property.php',1,'Roles'),
        array('Dynamic_UserList_Property.php',1,'Roles'),
        array('Dynamic_Username_Property.php',1,'Roles'),
        array('Dynamic_DataSource_Property.php',1,'DynamicData'),
        array('Dynamic_FieldStatus_Property.php',1,'DynamicData'),
        array('Dynamic_FieldType_Property.php',1,'DynamicData'),
        array('Dynamic_Hidden_Property.php',1,'Base'),
        array('Dynamic_ItemID_Property.php',1,'DynamicData'),
        array('Dynamic_ItemType_Property.php',1,'DynamicData'),
        array('Dynamic_DataObject_Property.php',1,'DynamicData'),
        array('Dynamic_SubForm_Property.php',1,'DynamicData'),
        array('Dynamic_Validation_Property.php',1,'DynamicData')
    );
    //set the array to hold properties that have not moved and should do!
    $ddtomove=array();

    //Check the files in the includes/properties dir against the initial array
    $oldpropdir='includes/properties';
    $var = is_dir($oldpropdir);
    $handle=opendir($oldpropdir);
    $skip_array = array('.','..','SCCS','index.htm','index.html');

    if ($var) {
             while (false !== ($file = readdir($handle))) {
                  // check the  dd file array and add to the ddtomove array if the file exists
                  if (!in_array($file,$skip_array))  {

                     foreach ($ddmoved as $key=>$propname) {
                          if ($file == $ddmoved[$key][0]){
                            $ddtomove[]=$ddmoved[$key];
                           }
                    }
                  }
            }
            closedir($handle);
    }
    if (is_array($ddtomove) && !empty($ddtomove[0])){

        $content .= "<h3 style=\"font:size:large;color:red; font-weigh:bold;\">WARNING!</h3><p>The following DD property files exist in your Xaraya <strong>includes/properties</strong> directory.</p>";
        $content .= "<p>Please delete each of the following and ONLY the following from your <strong>includes/properties</strong> directory as they have now been moved to the relevant module in core, or the 3rd party module concerned.</p>";
        $content .= "<p>Once you have removed the duplicated property files from <strong>includes/properties</strong> please re-run upgrade.php.</p>";

        foreach ($ddtomove as $ddkey=>$ddpropname) {
             if ($ddtomove[$ddkey][1] == 1) {
                $content .= "<p><strong>".$ddtomove[$ddkey][0]."</strong> exits. Please remove it from includes/properties.</p>";
             }else{
                $content .= "<p><strong>".$ddtomove[$ddkey][0]."</strong> is a ".$ddtomove[$ddkey][2]." module property. Please remove it from includes/properties. IF you have ".$ddtomove[$ddkey][2]." installed, check you have the property in the <strong>".strtolower($ddtomove[$ddkey][2])."/xarproperties</strong> directory else upgrade your ".$ddtomove[$ddkey][2]." module.</p>";
             }
        }

        $content .= "<p>REMEMBER! Run upgrade.php again when you delete the above properties from the includes/properties directory.</p>";

        unset($ddtomove);
        $thisdata['content']=$content;
        $thisdata['finishearly']=1;
       return $thisdata;
       // return;
     }else{
         $content .= "<p>Done! All properties have been checked and verified for location!</p>";
    }

/* End Version 1.0.0 Release Updates */

/** Version 1.0.1 Release Upgrades : NONE */

/* Version 1.0.2 Release Upgrades : NONE */

/* Version 1.1.0 Release Upgrades */

    // Set any empty modvars.
    $content .= "<p><strong>Checking Module and Config Variables</strong></p>";

    $modvars[] = array(array('name'    =>  'inheritdeny',
                             'module'  =>  'privileges',
                             'set'     =>  true),
                       array('name'    =>  'tester',
                             'module'  =>  'privileges',
                             'set'     =>  0),
                       array('name'    =>  'test',
                             'module'  =>  'privileges',
                             'set'     =>  false),
                       array('name'    =>  'testdeny',
                             'module'  =>  'privileges',
                             'set'     =>  false),
                       array('name'    =>  'testmask',
                             'module'  =>  'privileges',
                             'set'     =>  'All'),
                       array('name'    =>  'realmvalue',
                             'module'  =>  'privileges',
                             'set'     =>  'none'),
                       array('name'    =>  'realmcomparison',
                             'module'  =>  'privileges',
                             'set'     =>  'exact'),
                       array('name'    =>  'suppresssending',
                             'module'  =>  'mail',
                             'set'     =>  'false'),
                       array('name'    =>  'redirectsending',
                             'module'  =>  'mail',
                             'set'     =>  'exact'),
                       array('name'    =>  'redirectaddress',
                             'module'  =>  'privileges',
                             'set'     =>  ''),
                       array('name'    =>  'displayrolelist',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'usereditaccount',
                             'module'  =>  'roles',
                             'set'     =>  'true'),
                        array('name'    => 'userlastlogin',
                             'module'  =>  'roles',
                             'set'     =>  ''),
                        array('name'    => 'allowuserhomeedit',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'setuserhome',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'setprimaryparent',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'setpasswordupdate',
                             'module'  =>  'roles',
                             'set'     =>  'false'),
                        array('name'    => 'setuserlastlogin',
                             'module'  =>  'roles',
                             'set'     =>  'false')
                          );
    foreach($modvars as $modvar){
        foreach($modvar as $var){
            $currentvar = xarModVars::get("$var[module]", "$var[name]");
            if (isset($currentvar)){
                if (isset($var['override'])) {
                    xarModVars::set($var['module'], $var['name'], $var['set']);
                    $content .= "<p>$var[module] -> $var[name] has been overridden, proceeding to next check</p>";
                }
                else $content .= "<p>$var[module] -> $var[name] is set, proceeding to next check</p>";
            } else {
                xarModVars::set($var['module'], $var['name'], $var['set']);
                $content .= "<p>$var[module] -> $var[name] empty, attempting to set.... done!</p>";
            }
        }
    }

/*      // Check the installed privs and masks.
    $content .= "<p><strong>Checking Privilege Structure</strong></p>";

    $upgrade['priv_masks'] = xarMaskExists('ViewPrivileges','privileges','Realm');
    if (!$upgrade['priv_masks']) {
        $content .= "<p>Privileges realm Masks do not exist, attempting to create... done! </p>";

        // create a couple of new masks
        xarRegisterMask('ViewPrivileges','All','privileges','Realm','All','ACCESS_OVERVIEW');
        xarRegisterMask('ReadPrivilege','All','privileges','Realm','All','ACCESS_READ');
        xarRegisterMask('EditPrivilege','All','privileges','Realm','All','ACCESS_EDIT');
        xarRegisterMask('AddPrivilege','All','privileges','Realm','All','ACCESS_ADD');
        xarRegisterMask('DeletePrivilege','All','privileges','Realm','All','ACCESS_DELETE');
    } else {
        $content .= "<p>Privileges realm masks have been created previously, moving to next check. </p>";
    }
*/
    $content .= "<p><strong>Updating Roles and Authsystem for changes in User Login and Authentication</strong></p>";

    //Check for allow registration in existing Roles module
    $allowregistration =xarModVars::get('roles','allowregistration');
    if (isset($allowregistration) && ($allowregistration==1)) {
        //We need to tell user about the new Registration module - let's just warn them for now
        if (!xarModIsAvailable('registration')){
            $content .= "<h2 style=\"color:red;\">WARNING!</h2><p>Your setup indicates you allow User Registration on your site.</p>";
            $content .= "<p>Handling of User Registration has changed in this version. Please install and activate the <strong>Registration</strong> module to continue User Registration on your site.</p>";
            $content .= "<p>You should also remove any existing login blocks and install the Registration module Login block if you wish to include a Registration link in the block.</p>";
        }
    }

    //we need to check the login block is the Authsystem login block, not the Roles
    //As the block is the same we could just change the type id of any login block type.
    $blocktypeTable = $prefix . '_block_types';
    $blockinstanceTable = $prefix .'_block_instances';
    $blockproblem=array();
    $info = xarMod::getBaseInfo('roles');
    $sysid = $info['systemid'];
       //Get the block type id of the existing block type
        $query = "SELECT id,
                         type,
                         modid
                         FROM $blocktypeTable
                 WHERE type=? and modid=?";
        $result =& $dbconn->Execute($query,array('login',$sysid));
        list($blockid,$blocktype,$module)= $result->fields;
        $blocktype = array('id' => $blockid,
                           'blocktype' => $blocktype,
                           'modid'=> $modid);

        if (is_array($blocktype) && $blocktype['modid']==$sysid) {

            $blockid=$blocktype['id'];
            //set the module to authsystem and it can be used for the existing block instance
            $query = "UPDATE $blocktypeTable
                      SET modid = ?
                      WHERE id=?";
            $bindvars=array('authsystem',$blockid);
            $result =& $dbconn->Execute($query,$bindvars);

        }


    // Define and setup privs that may not be registered
    if (!xarPrivExists('AdminAuthsystem')) {
        xarRegisterPrivilege('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
    }
    if (!xarPrivExists('ViewAuthsystem')) {
        xarRegisterPrivilege('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    }
    xarUnregisterMask('ViewLogin');
    xarRegisterMask('ViewLogin','All','authsystem','Block','login:Login:All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystemBlocks','All','authsystem','Block','All','ACCESS_OVERVIEW');
    xarRegisterMask('ViewAuthsystem','All','authsystem','All','All','ACCESS_OVERVIEW');
    xarRegisterMask('EditAuthsystem','All','authsystem','All','All','ACCESS_EDIT');
    xarRegisterMask('AdminAuthsystem','All','authsystem','All','All','ACCESS_ADMIN');
     //Register a mask to maintain backward compatibility - this mask is used a lot as a hack for admin perm check in themes
    xarRegisterMask('AdminPanel','All','base','All','All','ACCESS_ADMIN');
    // Test for existance of privilege already assigned to priv group
    // If not add it
    $thispriv= xarPrivileges::findPrivilege('ViewAuthsystem');
    $parents= $thispriv->getparents();
    $casual=false;
    $readcore=false;
    foreach ($parents as $parent) {
         if ($parent->getName() == 'CasualAccess') $casual=true;
         if ($parent->getName() == 'ReadNonCore') $readcore=true;
    }
    if (xarPrivExists('CasualAccess') && !$casual)  {
       xarMakePrivilegeMember('ViewAuthsystem','CasualAccess');
    }elseif (xarPrivExists('ReadNonCore') && !$readcore) {
        xarMakePrivilegeMember('ViewAuthsystem','ReadNonCore');
    }

      // Define Module vars
     xarModVars::set('authsystem', 'lockouttime', 15);
    xarModVars::set('authsystem', 'lockouttries', 3);
    xarModVars::set('authsystem', 'uselockout', false);
    xarModVars::set('roles', 'defaultauthmodule', 'authsystem');


    $content .= "<p><strong>Removing Adminpanels module and move functions to other  modules</strong></p>";
    // Adminpanels module overviews modvar is deprecated
    // Move off Adminpanels dashboard modvar to Themes module

    //Check that we have waiting content hooks activated for Articles and adminpanels
    //if so set them now for Base
    if (xarModIsHooked('articles','adminpanels')) {
        //set it to Base now
         xarModAPIFunc('modules','admin','enablehooks',
                       array('callerModName' => 'base', 'hookModName' => 'articles'));
    }
    //Safest way is to just set the dash off for now
    xarModVars::set('themes','usedashboard',false);
    xarModVars::set('themes','dashtemplate','admin');

    $table_name['admin_menu']=$prefix . '_admin_menu';
    $upgrade['admin_menu'] = xarModAPIFunc('installer',
                                                'admin',
                                                'CheckTableExists',
                                                array('table_name' => $table_name['admin_menu']));
    //Let's remove the now unused admin menu table
    if ($upgrade['admin_menu']) {
        $adminmenuTable = $prefix .'_admin_menu';
        $query = xarDBDropTable($adminmenuTable);
        $result = &$dbconn->Execute($query);
     }

    //We need to upgrade the blocks, and as the block is the same we could just change the type id of any login.
    $blocktypeTable = $prefix .'_block_types';
    $blockinstanceTable = $prefix .'_block_instances';
    $newblocks=array('waitingcontent','adminmenu');
    $blockproblem=array();
    foreach ($newblocks as $newblock) {
        // We don't need to register new block = just change the existing block

        $info = xarMod::getBaseInfo('adminpanels');
        $sysid = $info['systemid'];
        //Get the ID of the old block type
        $query = "SELECT id,
                         type,
                         module
                         FROM $blocktypeTable
                 WHERE type=? and modid=?";
        $result =& $dbconn->Execute($query,array($newblock,$sysid));

        if ($result) {
            list($blockid,$blocktype,$module)= $result->fields;
            //update the module name in the block with that id to 'base'
            $blocktype = array('id' => $blockid,
                           'blocktype' => $blocktype,
                           'modid'=> $modid);

            if (is_array($blocktype) && $blocktype['modid']==$sysid) {
               $blockid=$blocktype['id'];
               //set the module to base
               $query = "UPDATE $blocktypeTable
                         SET modid = ?
                         WHERE id=?";
               $bindvars=array('base',$blockid);
               $result =& $dbconn->Execute($query,$bindvars);


               if (($newblock='waitingcontent') && isset($blockid)) {
                   //We need to disable existing hooks and enable new ones - but which :)
                   $hookTable = $prefix .'_hooks';
                   $query = "UPDATE $hookTable
                             SET smodule = 'base'
                             WHERE action=? AND smodule=?";
                    $bindvars = array('base','waitingcontent','adminpanels');
                    //? no execute here?
               }
            }
            //Remove the original block
            if (!xarModAPIFunc('blocks','admin','unregister_block_type',
                       array('modName'  => 'adminpanels',
                             'blockType'=> $newblock))) {
              $blockproblem[]=1;
            }

        }
      }
    if (count($blockproblem) >0) {
        $content .= "<p><span style=\"color:red;\">WARNING!</span> There was a problem in updating Waiting Content and Adminpanels menu block to Base blocks. Please check!</p>";
    }else {
        $content .= "<p>Done! Waiting content and Admin Menu block updated in Base module!</p>";
    }

    $content .= "<p>Removing unused adminpanel module variables</p>";
    $delmodvars[] = array(array('name'    =>  'showlogout',
                               'module'  =>  'adminpanels'),
                         array('name'    =>  'dashboard',
                               'module'  =>  'adminpanels'),
                         array('name'    =>  'overview',
                               'module'  =>  'adminpanels'),
                         array('name'    =>  'menustyle',
                               'module'  =>  'adminpanels')
                         );

     foreach($delmodvars as $delmodvar){
        foreach($delmodvar as $var){
            $currentvar = xarModVars::get("$var[module]", "$var[name]");
            if (!isset($currentvar)){
                $content .= "<p>$var[module] -> $var[name] is deleted, proceeding to next check</p>";
            } else {
                xarModVars::delete($var['module'], $var['name']);
                $content .= "<p>$var[module] -> $var[name] has value, attempting to delete.... done!</p>";
            }
        }
    }

    // Remove Masks and Instances
    xarRemoveMasks('adminpanels');
    xarRemoveInstances('adminpanels');

    //Remove the Adminpanel module entry
    $aperror=0;
    $moduleTable = $prefix .'_modules';
    $moduleStatesTable=$prefix .'_module_states';
    $adminpanels='adminpanels';
    $query = "SELECT name,
                     regid
              FROM $moduleTable
              WHERE name = ?";
    $result = &$dbconn->Execute($query,array($adminpanels));
    list($name, $adminregid) = $result->fields;
    if (!$result) $aperror=1;
    if (isset($adminregid) and $aperror<=0) {
        $query = "DELETE FROM $moduleTable WHERE regid = ?";
        $result = &$dbconn->Execute($query,array($adminregid));
        if (!$result) $aperror=1;
        $query = "DELETE FROM $moduleStatesTable WHERE regid = ?";
        $result = &$dbconn->Execute($query,array($adminregid));
        if (!$result) $aperror=1;
    }
    if ($aperror<=0) {
          $content .= "<p>Done! Adminpanel module has been removed!</p>";
    }else {
         $content .= "<p><span style=\"color:red;\">WARNING!</span> There was a problem removing Adminpanel module from the module listing.You may wish to remove it manually from your module listing after you log in.</p>";
    }

/* End of Version 1.1.0 Release Upgrades */

/* Version 1.1.x Release Upgrades */
    xarModVars::set('themes', 'adminpagemenu', 1); //New variables to switch admin in page menus (tabs) on and off
    xarModVars::set('privileges', 'inheritdeny', true); //Was not set in privileges activation in 1.1, isrequired, maybe missing in new installs
    xarModVars::set('roles', 'requirevalidation', true); //reuse this older var for user email changes, this validation is separate to registration validation
/* End of Version 1.1.1 Release Upgrades */
/* Version 1.1.2 Release Upgrades */
    //Module Upgrades should take care of most
    //Need to convert privileges but only if we decide to update the current Blocks module functions' privilege checks

    //We are allowing setting var that is reliably referenced for the xarMLS calculations (instead of using a variably named DD property which was the case)
    // This var becomes one of the roles 'duv' modvars
    xarModVars::set('roles', 'setusertimezone',false); //new modvar - let's make sure it's set
    xarModVars::delete('roles', 'settimezone');//this is no longer used, be more explicit and user setusertimezone
    xarModVars::set('roles', 'usertimezone',''); //new modvar - initialize it
    xarModVars::set('roles','allowemail', false); //old modvar returns. Let's make sure it's set false as it allows users to send emails

    //Ensure that registration module is set as default if it is installed,
    // if it is active and the default is currently not set
    $defaultregmodule = xarModVars::get('roles','defaultregmodule');
    if (empty($defaultregmodule)) {
        if (xarModIsAvailable('registration')) {
            xarModVars::set('roles','defaultregmodule', 'registration');
        }
    }

    // Ensure base timesince tag handler is added
    xarTplUnregisterTag('base-timesince');
    xarTplRegisterTag('base', 'base-timesince', array(),
                      'base_userapi_handletimesincetag');

/* End 1.1.2 Release Upgrades */

    $thisdata['content']=$content;
    $thisdata['phase'] = 2;
    $thisdata['phase_label'] = xarML('Step Two');

    return $thisdata;
}
// Miscellaneous upgrade functions that always run each upgrade
// Version independent
function installer_admin_upgrade3()
{
    $content='';
    $thisdata['xarProduct'] = xarConfigVars::get(null, 'System.Core.VersionId');
    $thisdata['xarVersion'] = xarConfigVars::get(null, 'System.Core.VersionNum');
    $thisdata['xarRelease'] = xarConfigVars::get(null, 'System.Core.VersionSub');
    $content='';

    // Set Config Vars - add those that need to be set each upgrade here.
    $roleanon = xarFindRole('Anonymous');
    $configvars[] = array(
                           array('name'    =>  'System.Core.VersionNum',
                                 'set'     =>  xarCore::VERSION_NUM));
    $content .=  "<h3><strong>Updating Required Configuration Variables</strong></h3>";
    foreach($configvars as $configvar){
        foreach($configvar as $var){
            $currentvar = xarConfigVars::get(null, "$var[name]");
            if ($currentvar == $var['set']){
                $content .= "<p>$var[name] is set, proceeding to next check</p>";
            } else {
                xarConfigVars::set(null, $var['name'], $var['set']);
                $content .= "<p>$var[name] incorrect, attempting to set.... done!</p>";
            }
        }
    }
  // Bug 630, let's throw the reminder back up after upgrade.
    if (!xarModAPIFunc('blocks', 'user', 'get', array('name' => 'reminder'))) {
        $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot.';
        $varshtml['expire'] = time() + 7*24*60*60; // 7 days

        $htmlBlockType = xarModAPIFunc(
            'blocks', 'user', 'getblocktype',
            array('module' => 'base', 'type' => 'html')
        );

        if (empty($htmlBlockType)) {
            return;
        }

        // Get the first available group ID, and assume that will be
        // visible to the administrator.
        $allgroups = xarModAPIFunc(
            'blocks', 'user', 'getallgroups',
            array('order' => 'id')
        );
        $topgroup = array_shift($allgroups);

        if (!xarModAPIFunc(
            'blocks', 'admin', 'create_instance',
            array(
                'title'    => 'Reminder',
                'name'     => 'reminder',
                'content'  => $varshtml,
                'type'     => $htmlBlockType['tid'],
                'groups'   => array(array('id' => $topgroup['id'])),
                'state'    => 2))) {
            return;
        }
    } // End bug 630


    // Flush the property cache, so on upgrade all proptypes
    // are properly set in the database.
    $content .=  "<h3><strong>Flushing the property cache</strong></h3>";
    if(!xarModAPIFunc('dynamicdata','admin','importpropertytypes', array('flush' => true))) {
        $content .=  "<p>WARNING: Flushing property cache failed</p>";
    } else {
        $content .=  "<p>Success! Flushing property cache complete</p>";
    }

    $thisdata['content']=$content;
    $thisdata['phase'] = 3;
    $thisdata['phase_label'] = xarML('Step Three');

    return $thisdata;
}
function installer_admin_upgrade4()
{
    $content='';
    $thisdata['xarProduct'] = xarConfigVars::get(null, 'System.Core.VersionId');
    $thisdata['xarVersion'] = xarConfigVars::get(null, 'System.Core.VersionNum');
    $thisdata['xarRelease'] = xarConfigVars::get(null, 'System.Core.VersionSub');
    $thisdata['content']=$content;
    $thisdata['phase'] = 4;
    $thisdata['phase_label'] = xarML('Step Four');

    return $thisdata;
}
?>

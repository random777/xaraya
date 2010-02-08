<?php
/**
 * Installer
 *
 * @package Installer
 * @copyright see the html/credits.html file in this release
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


// TODO: move this to some place central
define('PHP_REQUIRED_VERSION', '5.3.0');
define('MYSQL_REQUIRED_VERSION', '5.0.0');

/**
 * Phase 1: Welcome (Set Language and Locale) Page
 *
 * @access private
 * @return data array of language values
 */
function installer_admin_phase1()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    if (!xarVarFetch('agree','regexp:(agree|disagree)',$agree)) return;

    $retry=1;

    if ($agree != 'agree') {
        // didn't agree to license, don't install
        xarResponse::redirect('install.php?install_phase=2&install_language='.$install_language.'&retry=1');
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
        if (version_compare(PHP_VERSION,PHP_REQUIRED_VERSION,'>=')) $metRequiredPHPVersion = true;
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
    $data['xslextension']             = extension_loaded('xsl');
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
    
    // We only check this extension if MySQL is loaded
    if ($data['mysqlextension']) {
        $data['mysql_required_version']     = MYSQL_REQUIRED_VERSION;
        ob_start();
        phpinfo(INFO_MODULES);
        $info = ob_get_contents();
        ob_end_clean();
        $info = stristr($info, 'Client API version');
        preg_match('/[1-9].[0-9].[1-9][0-9]/', $info, $match);
        $data['mysql_version_ok'] = version_compare($match[0],MYSQL_REQUIRED_VERSION,'ge');
        $data['mysql_version']          = $match[0];
    }

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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    // Get default values from config files
    $data['database_host']       = xarSystemVars::get(sys::CONFIG, 'DB.Host');
    $data['database_username']   = xarSystemVars::get(sys::CONFIG, 'DB.UserName');
    $data['database_password']   = '';
    $data['database_name']       = xarSystemVars::get(sys::CONFIG, 'DB.Name');
    $data['database_prefix']     = xarSystemVars::get(sys::CONFIG, 'DB.TablePrefix');
    $data['database_type']       = xarSystemVars::get(sys::CONFIG, 'DB.Type');
    $data['database_charset']    = xarSystemVars::get(sys::CONFIG, 'DB.Charset');

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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarSetCached('installer','installing', true);

    // Get arguments
    if (!xarVarFetch('install_database_host','pre:trim:passthru:str',$dbHost)) return;
    if (!xarVarFetch('install_database_name','pre:trim:passthru:str',$dbName,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_username','pre:trim:passthru:str',$dbUname,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_password','pre:trim:passthru:str',$dbPass,'',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_prefix','pre:trim:passthru:str',$dbPrefix,'xar',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_charset','pre:trim:passthru:str',$dbCharset,'utf8',XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('install_database_type','str:1:',$dbType)) return;
    if (!xarVarFetch('install_create_database','checkbox',$createDB,false,XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('confirmDB','bool',$confirmDB,false,XARVAR_NOT_REQUIRED)) return;

    if ($dbHost == 'localhost') {
        $dbHost = '127.0.0.1';
    }
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
                         'dbType'    => $dbType,
                         'dbCharset' => $dbCharset);
    //  Write the config
    xarInstallAPIFunc('modifyconfig', $config_args);

    $init_args =  array('userName'           => $dbUname,
                        'password'           => $dbPass,
                        'databaseHost'       => $dbHost,
                        'databaseType'       => $dbType,
                        'databaseName'       => $dbName,
                        'databaseCharset'    => $dbCharset,
                        'prefix'             => $dbPrefix,
                        'doConnect'          => false);

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

    if ($dbType == 'mysql') {
        $tokens = explode('.',mysql_get_server_info());
        $data['version'] = $tokens[0] ."." . $tokens[1] . ".0";
        $data['required_version'] = MYSQL_REQUIRED_VERSION;
        $mysql_version_ok = version_compare($data['version'],$data['required_version'],'ge');
        if (!$mysql_version_ok) {
            $data['layout'] = 'bad_version';
            return xarTplModule('installer','admin','check_database',$data);
        }
    }
    
    if (!$createDB && !$dbExists) {
        $data['dbName'] = $dbName;
        $data['layout'] = 'not_found';
        return xarTplModule('installer','admin','check_database',$data);
    }

    $data['confirmDB']  = $confirmDB;
    if ($dbExists && !$confirmDB) {
        $data['dbHost']     = $dbHost;
        $data['dbName']     = $dbName;
        $data['dbUname']    = $dbUname;
        $data['dbPass']     = $dbPass;
        $data['dbPrefix']   = $dbPrefix;
        $data['dbType']     = $dbType;
        $data['dbCharset']  = $dbCharset;
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
        if(!$dbconn->Execute(xarDBCreateDatabase($dbName,$dbType,$dbCharset))) {
          //if (!xarInstallAPIFunc('createdb', $config_args)) {
          $msg = xarML('Could not create database (#(1)). Check if you already have a database by that name and remove it.', $dbName);
          throw new Exception($msg);
        }
    } else {
        $removetables = true;
    }

    // Re-init with the new values and connect
    $systemArgs = array('userName'           => $dbUname,
                        'password'           => $dbPass,
                        'databaseHost'       => $dbHost,
                        'databaseType'       => $dbType,
                        'databaseName'       => $dbName,
                        'databaseCharset'    => $dbCharset,
                        'prefix'             => $dbPrefix);
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
    sys::import('xaraya.hooks');

    // 1. Load base and modules module
    $modules = array('base','modules');
    foreach ($modules as $module) {
        if (!xarInstallAPIFunc('initialise', array('directory' => $module,'initfunc'  => 'init'))) return;
    }

    // 2. Load the definitions of all the modules in the modules table
    $prefix = xarDB::getPrefix();
    $modulesTable = $prefix .'_modules';
    $tables =& xarDB::getTables();

    $newModSql   = "INSERT INTO $modulesTable
                    (name, regid, directory,
                     version, class, category, admin_capable, user_capable, state)
                    VALUES (?,?,?,?,?,?,?,?,?)";
    $newStmt     = $dbconn->prepareStatement($newModSql);

    $modules = array('authsystem','roles','privileges','installer','blocks','themes','dynamicdata','mail');
    // Series of updates, begin transaction
    try {
        $dbconn->begin();
        foreach($modules as $index => $modName) {
            // Insert module
            $modversion=array();$bindvars = array();
            // NOTE: We can not use the sys::import here, since the variable scope is important.
            include_once sys::code() . "modules/$modName/xarversion.php";
            $bindvars = array($modName,
                              $modversion['id'],       // regid, from xarversion
                              $modName,
                              $modversion['version'],
                              $modversion['class'],
                              $modversion['category'],
                              isset($modversion['admin']) ? $modversion['admin']:false,
                              isset($modversion['user'])  ? $modversion['user']:false,
                              3);
            $result = $newStmt->executeUpdate($bindvars);
            $newModId = $dbconn->getLastId($tables['modules']);
        }
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // 3. Initialize all the modules we haven't yet
    $modules = array('privileges','roles','blocks','authsystem','themes','dynamicdata','mail');
    foreach ($modules as $module) {
        try {
            sys::import('modules.' . $module . '.xartables');
            $tablefunc = $module . '_xartables';
            if (function_exists($tablefunc)) xarDB::importTables($tablefunc());
        } catch (Exception $e) {}
        if (!xarInstallAPIFunc('initialise', array('directory' => $module, 'initfunc'  => 'init'))) return;
    }

    if (!xarInstallAPIFunc('initialise', array('directory'=>'authsystem', 'initfunc'=>'activate'))) return;
    if (!xarInstallAPIFunc('initialise', array('directory'=>'privileges', 'initfunc'=>'activate'))) return;
    if (!xarInstallAPIFunc('initialise', array('directory'=>'mail', 'initfunc'=>'activate'))) return;

    // create the default masks and privilege instances
    sys::import('modules.privileges.xarsetup');
    initializeSetup();

    // TODO: is this is correct place for a default value for a modvar?
    xarModVars::set('base', 'AlternatePageTemplate', 'homepage');

    // If we are here, the base system has completed
    // We can now pass control to xaraya.
    sys::import('xaraya.variables');

    $a = array();
    xarVar_init($a);
    xarConfigVars::set(null, 'System.ModuleAliases',array());
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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarVarSetCached('installer','installing', true);

    // load modules into *_modules table
//    if (!xarMod::apiFunc('modules', 'admin', 'regenerate'))
//        throw new Exception("regenerating module list failed");

# --------------------------------------------------------
# Create DD configuration and sample objects
#
    $objects = array(
                   'configurations',
                   'sample',
                   'dynamicdata_tablefields',
                   'module_settings',
                     );

    if(!xarMod::apiFunc('modules','admin','standardinstall',array('module' => 'dynamicdata', 'objects' => $objects))) return;
# --------------------------------------------------------
# Create wrapper DD overlay objects for the modules and roles modules
#
    $objects = array(
                   'modules',
//                   'modules_hooks',
//                   'modules_modvars',
                     );
    if(!xarMod::apiFunc('modules','admin','standardinstall',array('module' => 'modules', 'objects' => $objects))) return;

    $objects = array(
                   'roles_roles',
                   'roles_users',
                   'roles_groups',
                   'roles_user_settings',
                     );

    if(!xarMod::apiFunc('modules','admin','standardinstall',array('module' => 'roles', 'objects' => $objects))) return;

    $objects = array('themes_user_settings');

    if(!xarMod::apiFunc('modules','admin','standardinstall',array('module' => 'themes', 'objects' => $objects))) return;

# --------------------------------------------------------
# Set up the standard module variables for the core modules
# Never use createItem with modvar storage. Instead, you update itemid == 0
#
    $modules = array(
                        'authsystem',
                        'blocks',
                        'base',
                        'dynamicdata',
                        'mail',
                        'modules',
                        'privileges',
                        'roles',
                        'themes',
                    );

    foreach ($modules as $module) {
        $data['module_settings'] = xarMod::apiFunc('base','admin','getmodulesettings',array('module' => $module));
        $data['module_settings']->initialize();
    }

   $modlist = array('roles');
    foreach ($modlist as $mod) {
        $regid=xarMod::getRegID($mod);
        if (!xarMod::apiFunc('modules','admin','activate',
                           array('regid'=> $regid)))
            throw new Exception("activation of $regid failed");//return;
    }

    // load modules into *_modules table
    if (!xarMod::apiFunc('modules', 'admin', 'regenerate')) return;

    // load themes into *_themes table
    if (!xarMod::apiFunc('themes', 'admin', 'regenerate')) {
        throw new Exception("themes regeneration failed");
    }

    // Set the state and activate the following themes
    $themelist = array('print','rss','default');
    foreach ($themelist as $theme) {
        // Set state to inactive
        $regid = xarThemeGetIDFromName($theme);
        if (isset($regid)) {
            if (!xarMod::apiFunc('themes','admin','setstate', array('regid'=> $regid,'state'=> XARTHEME_STATE_INACTIVE))){
                throw new Exception("Setting state of theme with regid: $regid failed");
            }
            // Activate the theme
            if (!xarMod::apiFunc('themes','admin','activate', array('regid'=> $regid)))
            {
                throw new Exception("Activation of theme with regid: $regid failed");
            }
        }
    }

    xarResponse::redirect(xarModURL('installer', 'admin', 'create_administrator',array('install_language' => $install_language)));
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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    xarVarSetCached('installer','installing', true);
    xarTplSetThemeName('installer');

    $data['language'] = $install_language;
    $data['phase'] = 6;
    $data['phase_label'] = xarML('Create Administrator');

    sys::import('modules.roles.class.roles');
    $data['admin'] = xarRoles::getRole((int)xarModVars::get('roles','admin'));
    $data['properties'] = $data['admin']->getProperties();

    if (!xarVarFetch('create', 'isset', $create, FALSE, XARVAR_NOT_REQUIRED)) return;
    if (!$create) {
        return $data;
    }

    // Set up some custom validation checks and messages
    $data['admin']->properties['name']->validation_min_length = 4;
    $data['admin']->properties['name']->validation_min_length_invalid = xarML('The display name must be at least 4 characters long');
    $data['admin']->properties['uname']->validation_min_length = 4;
    $data['admin']->properties['uname']->validation_min_length_invalid = xarML('The user name must be at least 4 characters long');
    $data['admin']->properties['password']->validation_min_length = 4;
    $data['admin']->properties['password']->validation_min_length_invalid = xarML('The password must be at least 4 characters long');
    $data['admin']->properties['password']->validation_password_confirm = 1;
    $data['admin']->properties['email']->validation_min_length = 1;
    $data['admin']->properties['email']->validation_min_length_invalid = xarML('An email address must be entered');

    $isvalid = $data['admin']->checkInput();
    if (!$isvalid) {
        return xarTplModule('installer','admin','create_administrator',$data);
    }

    xarModVars::set('mail', 'adminname', $data['admin']->properties['name']->value);
    xarModVars::set('mail', 'adminmail', $data['admin']->properties['email']->value);
    xarModVars::set('themes', 'SiteCopyRight', '&copy; Copyright ' . date("Y") . ' ' . $data['admin']->properties['name']->value);
    xarModVars::set('roles', 'lastuser', $data['admin']->properties['uname']->value);
    xarModVars::set('roles', 'adminpass', $data['admin']->properties['password']->password);

// CHECKME: misc. undefined module variables
    xarModVars::set('themes', 'var_dump', false);
    xarModVars::set('base', 'releasenumber', 10);
    xarModVars::set('base', 'AlternatePageTemplateName', '');
    xarModVars::set('base', 'UseAlternatePageTemplate', false);
    xarModVars::set('base', 'editor', 'none');
    xarModVars::set('base', 'proxyhost', '');
    xarModVars::set('base', 'proxyport', 0);

    //Try to update the role to the repository and bail if an error was thrown
    $itemid = $data['admin']->updateItem();
    if (!$itemid) {return;}

    // Register Block types from modules installed before block apis (base)
    $blocks = array('wrapper', 'adminmenu','waitingcontent','finclude','html','menu','php','text','content');

    foreach ($blocks as $block) {
        if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type', array('modName'  => 'base', 'blockType'=> $block))) return;
    }

    if (xarVarIsCached('Mod.BaseInfos', 'blocks')) xarVarDelCached('Mod.BaseInfos', 'blocks');

    // Create default block groups/instances
    //                            name        template
    $default_blockgroups = array ('left'   => null,
                                  'right'  => 'right',
                                  'header' => 'header',
                                  'admin'  => null,
                                  'center' => 'center',
                                  'topnav' => 'topnav'
                                  );

    $wrapperBlockType = xarModAPIFunc('blocks', 'user', 'getblocktype',
                                    array('module'  => 'base',
                                          'type'    => 'wrapper'));

    $wrapperBlockTypeID = $wrapperBlockType['tid'];
    assert('is_numeric($wrapperBlockTypeID);');

    foreach ($default_blockgroups as $name => $template) {
        if(!xarMod::apiFunc('blocks','user','groupgetinfo', array('name' => $name))) {
            // Not there yet
            if(!xarMod::apiFunc('blocks','admin','create_instance', array('name' => $name, 'template' => $template,
                'type' => $wrapperBlockTypeID, 'state' => 2
            ))) return;
        }
    }

    // Load up database
    $dbconn = xarDB::getConn();
    $tables = xarDB::getTables();

    $blockGroupsTable = $tables['block_instances'];

    $query = "SELECT    id as id
              FROM      $blockGroupsTable
              WHERE     name = ?";
    $result = $dbconn->Execute($query,array('admin'));

    // Freak if we don't get one and only one result
    if ($result->getRecordCount() != 1) {
        $msg = xarML("Group 'left' not found.");
        throw new Exception($msg);
    }

    list ($leftBlockGroup) = $result->fields;

    $adminBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                    array('module'  => 'base',
                                          'type'    => 'adminmenu'));

    $adminBlockTypeId = $adminBlockType['tid'];
    assert('is_numeric($adminBlockTypeId);');
    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'adminpanel'))) {
        if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Admin',
                                 'name'     => 'adminpanel',
                                 'type'     => $adminBlockTypeId,
                                 'groups'   => array(array('id'      => $leftBlockGroup)),
                                 'state'    =>  2))) {
            return;
        }
    }
    xarResponse::redirect(xarModURL('installer', 'admin', 'choose_configuration',array('install_language' => $install_language)));
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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    $data['language'] = $install_language;
    $data['phase'] = 7;
    $data['phase_label'] = xarML('Choose your configuration');
    xarTplSetThemeName('installer');

    //Get all modules in the filesystem
    $fileModules = xarMod::apiFunc('modules','admin','getfilemodules');
    if (!isset($fileModules)) return;

    // Make sure all the core modules are here
    // Remove them from the list if name and regid coincide
    $awol = array();
    include sys::code() . 'modules/installer/xarconfigurations/coremoduleslist.php';
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

    $basedir = realpath(sys::code() . 'modules/installer/xarconfigurations');

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
        include sys::code() . 'modules/installer/xarconfigurations/core.conf.php';
        $names[] = array('value' => sys::code() . 'modules/installer/xarconfigurations/core.conf.php',
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
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);

    xarVarSetCached('installer','installing', true);
    xarTplSetThemeName('installer');

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
                $modInfo = xarMod::getInfo($module['regid']);
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
        xarMod::apiFunc('modules','admin','regenerate');

        sys::import('modules.modules.class.installer');
        $installer = Installer::getInstance();    
        // load the modules from the configuration
        foreach ($options2 as $module) {
            if(in_array($module['item'],$chosen)) {
                $dependencies = $installer->getalldependencies($module['item']);
                if (count($dependencies['unsatisfiable']) > 0) {
                    $msg = xarML("Cannot load because of unsatisfied dependencies. One or more of the following modules is missing: ");
                    foreach ($dependencies['unsatisfiable'] as $dependent) {
                        $modname = isset($dependent['name']) ? $dependent['name'] : "Unknown";
                        $modid = isset($dependent['id']) ? $dependent['id'] : $dependent;
                        $msg .= $modname . " (ID: " . $modid . "), ";
                    }
                    $msg = trim($msg,', ') . ". " . xarML("Please check the listings at www.xaraya.com to identify any modules flagged as 'Unknown'.");
                    $msg .= " " . xarML('Add the missing module(s) to the modules directory and run the installer again.');
                    throw new Exception($msg);
                }
                xarMod::apiFunc('modules','admin','installwithdependencies',array('regid'=>$module['item']));
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

        $blockGroupsTable = $tables['block_instances'];

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

        $menuBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                     array('module'  => 'base',
                                           'type'=> 'menu'));


        $menuBlockTypeId = $menuBlockType['tid'];

        if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'mainmenu'))) {
            if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                          array('title' => 'Main Menu',
                                'name'  => 'mainmenu',
                                'type'  => $menuBlockTypeId,
                                'groups' => array(array('id' => $leftBlockGroup,)),
                                'content' => serialize($content),
                                'state' => 2))) {
                return;
            }
        }
     //TODO: Check why this var is being reset to null in sqlite install - reset here for now to be sure
     //xarModVars::set('roles', 'defaultauthmodule', xarMod::getRegID('authsystem'));

        xarResponse::redirect(xarModURL('installer', 'admin', 'security'));
        return true;
    }

}

function installer_admin_security()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarTplSetThemeName('installer');
    $data['language']    = $install_language;
    $data['phase'] = 9;
    $data['phase_label'] = xarML('Security Considerations');

    return $data;
}

function installer_admin_cleanup()
{
    if (!file_exists('install.php')) { throw new Exception('Already installed');}
    xarVarFetch('install_language','str::',$install_language, 'en_US.utf-8', XARVAR_NOT_REQUIRED);
    xarTplSetThemeName('installer');

    xarVarFetch('remove', 'checkbox', $remove, false, XARVAR_NOT_REQUIRED);
    xarVarFetch('rename', 'checkbox', $rename, false, XARVAR_NOT_REQUIRED);
    xarVarFetch('newname', 'str', $newname, '', XARVAR_NOT_REQUIRED);

    if ($remove) {
        unlink('install.php');
    } elseif ($rename) {
        if (empty($newname)) {
            unlink('install.php');
        } else {
            rename('install.php',$newname . '.php');
        }
    }

    // Install script is still there. Create a reminder block
    if (file_exists('install.php')) {
        // Load up database
        $dbconn = xarDB::getConn();
        $tables = xarDB::getTables();

        $blockTable = $tables['block_instances'];

        $query = "SELECT    id as id
                  FROM      $blockTable
                  WHERE     name = ?";

        $result =& $dbconn->Execute($query,array('left'));

        // Freak if we don't get one and only one result
        if ($result->getRecordCount() != 1) {
            $msg = xarML("Group 'left' not found.");
            throw new Exception($msg);
        }

        list ($leftBlockGroup) = $result->fields;
        $now = time();

//        $varshtml['html_content'] = 'Please delete install.php and upgrade.php from your webroot.';
        $varshtml['html_content'] = 'Please delete install.php from your webroot.';
        $varshtml['expire'] = $now + 259200;
        $msg = serialize($varshtml);

        $htmlBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                     array('module'  => 'base',
                                           'type'    => 'html'));

        $htmlBlockTypeId = $htmlBlockType['tid'];

        if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'reminder'))) {
            if (!xarMod::apiFunc('blocks', 'admin', 'create_instance',
                               array('title'    => 'Reminder',
                                     'name'     => 'reminder',
                                     'content'  => $msg,
                                     'type'     => $htmlBlockTypeId,
                                     'groups'   => array(array('id'      => $leftBlockGroup,)),
                                     'state'    => 2))) {
                return;
            }
        }
    }

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

    $blockGroupsTable = $tables['block_instances'];

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

    $loginBlockTypeId = xarMod::apiFunc('blocks','admin','register_block_type',
                    array('modName' => 'authsystem', 'blockType' => 'login'));
    if (empty($loginBlockTypeId)) {
        return;
    }

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'login'))) {
        if (xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Login',
                                 'name'     => 'login',
                                 'type'     => $loginBlockTypeId,
                                 'groups'    => array(array('id'     => $rightBlockGroup)),
                                 'state'    => 2))) {
        } else {
            throw new Exception('Could not create login block');
        }
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

    $metaBlockType = xarMod::apiFunc('blocks', 'user', 'getblocktype',
                                   array('module' => 'themes',
                                         'type'   => 'meta'));

    $metaBlockTypeId = $metaBlockType['tid'];

    if (!xarMod::apiFunc('blocks', 'user', 'get', array('name'  => 'meta'))) {
        if (xarMod::apiFunc('blocks', 'admin', 'create_instance',
                           array('title'    => 'Meta',
                                 'name'     => 'meta',
                                 'type'     => $metaBlockTypeId,
                                 'groups'    => array(array('id'      => $headerBlockGroup)),
                                 'state'    => 2))) {
        } else {
            throw new Exception('Could not create meta block');
        }
    }

    xarMod::apiFunc('dynamicdata','admin','importpropertytypes', array('flush' => true));

    $data['language']    = $install_language;
    $data['phase'] = 10;
    $data['phase_label'] = xarML('Step Ten');
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

    if(!xarMod::apiFunc('modules','admin','standardinstall',array('module' => 'privileges', 'objects' => $objects))) return;

    // Default for the site time zone is the system time zone
    xarConfigVars::set(null, 'Site.Core.TimeZone', xarSystemVars::get(sys::CONFIG, 'SystemTimeZone'));

    switch ($returnurl) {
        case ('base'):
            xarResponse::redirect(xarModURL('base','admin','modifyconfig'));
        case ('modules'):
            xarResponse::redirect(xarModURL('modules','admin','list'));
        case ('blocks'):
            xarResponse::redirect(xarModURL('blocks','admin','view_instances'));
        case ('site'):
        default:
            xarResponse::redirect('index.php');
    }
    return true;
}

?>

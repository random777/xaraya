<?php
/**
 * Base Module Initialisation
 * @package modules
 * @copyright (C) 2005-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marcel van der Boom
 */

/**
 * Load Table Maintainance API
 */
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the base module
 *
 * @return bool
 * @raise DATABASE_ERROR
 */
function base_init()
{
    // Get database information
    $dbconn =& xarDBGetConn();
    $tables =& xarDBGetTables();

    $systemPrefix = xarDBGetSystemTablePrefix();


    // We want all this to succeed or fail completely
    try {
        $dbconn->begin();

        /*********************************************************************
         * Here we create non module associated tables
         *
         * prefix_config_vars   - system configuration variables
         * prefix_session_info  - Session table
         * prefix_template_tags - module template tag registry
         *********************************************************************/
        $sessionInfoTable = $systemPrefix . '_session_info';
        /*********************************************************************
         * CREATE TABLE xar_session_info (
         *  xar_sessid varchar(32) NOT NULL default '',
         *  xar_ipaddr varchar(20) NOT NULL default '',
         *  xar_firstused int(11) NOT NULL default '0',
         *  xar_lastused int(11) NOT NULL default '0',
         *  xar_uid int(11) NOT NULL default '0',
         *  xar_vars blob,
         *  xar_remembersess int(1) default '0',
         *  PRIMARY KEY  (xar_sessid)
         * )
         *********************************************************************/
        $fields = array(
                        'xar_sessid'       => array('type'=>'varchar','size'=>32,'null'=>false,'primary_key'=>true),
                        'xar_ipaddr'       => array('type'=>'varchar','size'=>20,'null'=>false),
                        'xar_firstused'    => array('type'=>'integer','null'=>false,'default'=>'0'),
                        'xar_lastused'     => array('type'=>'integer','null'=>false,'default'=>'0'),
                        'xar_uid'          => array('type'=>'integer','null'=>false,'default'=>'0'),
                        'xar_vars'         => array('type'=>'blob', 'null' => true),
                        'xar_remembersess' => array('type'=>'integer','size'=>'tiny','default'=>'0')
                        );

        $query = xarDBCreateTable($sessionInfoTable,$fields);
        $dbconn->Execute($query);

        $index = array('name'   => 'i_'.$systemPrefix.'_session_uid',
                       'fields' => array('xar_uid'),
                       'unique' => false);
        $query = xarDBCreateIndex($sessionInfoTable,$index);
        $dbconn->Execute($query);

        $index = array('name'   => 'i_'.$systemPrefix.'_session_lastused',
                       'fields' => array('xar_lastused'),
                       'unique' => false);

        $query = xarDBCreateIndex($sessionInfoTable,$index);
        $dbconn->Execute($query);

        /*********************************************************************
         * Here we install the configuration table and set some default
         * configuration variables
         *********************************************************************/
        $configVarsTable  = $systemPrefix . '_config_vars';
        /*********************************************************************
         * CREATE TABLE xar_config_vars (
         *  xar_id int(11) unsigned NOT NULL auto_increment,
         *  xar_name varchar(64) NOT NULL default '',
         *  xar_value longtext,
         *  PRIMARY KEY  (xar_id),
         *  KEY xar_name (xar_name)
         * )
         *********************************************************************/

        $fields = array(
                        'xar_id'    => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
                        'xar_name'  => array('type'=>'varchar','size'=>64,'null'=>false),
                        'xar_value' => array('type'=>'text','size'=>'long')
                        );

        $query = xarDBCreateTable($configVarsTable,$fields);
        $dbconn->Execute($query);

        // config var name should be unique
        $index = array('name'   => 'i_'.$systemPrefix.'_config_name',
                       'fields' => array('xar_name'),
                       'unique' => true);

        $query = xarDBCreateIndex($configVarsTable,$index);
        $dbconn->Execute($query);

        include_once 'includes/xarConfig.php';

        // Start Configuration Unit
        $systemArgs = array();
        // change this loadlevel to the proper level
        $whatToLoad = XARCORE_SYSTEM_DATABASE;
        xarConfig_init($systemArgs, $whatToLoad);
        // Start Variable Utils
        xarVar_init($systemArgs, $whatToLoad);

        $allowableHTML = array (
                                '!--'=>2, 'a'=>2, 'b'=>2, 'blockquote'=>2,'br'=>2, 'center'=>2,
                                'div'=>2, 'em'=>2, 'font'=>0, 'hr'=>2, 'i'=>2, 'img'=>0, 'li'=>2,
                                'marquee'=>0, 'ol'=>2, 'p'=>2, 'pre'=> 2, 'span'=>0,'strong'=>2,
                                'tt'=>2, 'ul'=>2, 'table'=>2, 'td'=>2, 'th'=>2, 'tr'=> 2);

        xarConfigSetVar('Site.Core.AllowableHTML',$allowableHTML);
        /****************************************************************
         * Set System Configuration Variables
         *****************************************************************/
        xarConfigSetVar('System.Core.TimeZone', '');
        xarConfigSetVar('System.Core.VersionNum', XARCORE_VERSION_NUM);
        xarConfigSetVar('System.Core.VersionId', XARCORE_VERSION_ID);
        xarConfigSetVar('System.Core.VersionSub', XARCORE_VERSION_SUB);
        $allowedAPITypes = array();
        xarConfigSetVar('System.Core.AllowedAPITypes',$allowedAPITypes);
        /*****************************************************************
         * Set site configuration variables
         ******************************************************************/
        xarConfigSetVar('Site.BL.ThemesDirectory','themes');
        xarConfigSetVar('Site.BL.CacheTemplates',true);
        xarConfigSetVar('Site.Core.FixHTMLEntities',true);
        xarConfigSetVar('Site.Core.TimeZone', '');
        xarConfigSetVar('Site.Core.EnableShortURLsSupport', false);
        // when installing via https, we assume that we want to support that :)
        $HTTPS = xarServerGetVar('HTTPS');
    /* jojodee - monitor this fix.
       Localized fix for installer where HTTPS shows incorrectly as being on in
       some environments. Fix is ok as long as we dont access directly
       outside of installer. Consider setting config vars at later point rather than here.
    */
    $REQ_URI = parse_url(xarServerGetVar('HTTP_REFERER'));
        // IIS seems to set HTTPS = off for some reason (cfr. xarServerGetProtocol)
        if (!empty($HTTPS) && $HTTPS != 'off' && $REQ_URI['scheme'] == 'https') {
            xarConfigSetVar('Site.Core.EnableSecureServer', true);
        } else {
            xarConfigSetVar('Site.Core.EnableSecureServer', false);
        }

        xarConfigSetVar('Site.Core.DefaultModuleName', 'base');
        xarConfigSetVar('Site.Core.DefaultModuleType', 'user');
        xarConfigSetVar('Site.Core.DefaultModuleFunction', 'main');
        xarConfigSetVar('Site.Core.LoadLegacy', false);
        xarConfigSetVar('Site.Session.SecurityLevel', 'Medium');
        xarConfigSetVar('Site.Session.Duration', 7);
        xarConfigSetVar('Site.Session.InactivityTimeout', 90);
        // use current defaults in includes/xarSession.php
        xarConfigSetVar('Site.Session.CookieName', '');
        xarConfigSetVar('Site.Session.CookiePath', '');
        xarConfigSetVar('Site.Session.CookieDomain', '');
        xarConfigSetVar('Site.Session.RefererCheck', '');
        xarConfigSetVar('Site.MLS.TranslationsBackend', 'xml2php');
        // FIXME: <marco> Temporary config vars, ask them at install time
        xarConfigSetVar('Site.MLS.MLSMode', 'SINGLE');

        // The installer should now set the default locale based on the
        // chose language, let's make sure that is true
        if(!xarConfigGetVar('Site.MLS.DefaultLocale')) {
            xarConfigSetVar('Site.MLS.DefaultLocale', 'en_US.utf-8');
            $allowedLocales = array('en_US.utf-8');
            xarConfigSetVar('Site.MLS.AllowedLocales', $allowedLocales);
        }
        // Minimal information for timezone offset handling (see also Site.Core.TimeZone)
        xarConfigSetVar('Site.MLS.DefaultTimeOffset', 0);

        $authModules = array('authsystem');
        xarConfigSetVar('Site.User.AuthenticationModules',$authModules);

        $templateTagsTable = $systemPrefix . '_template_tags';
        /*********************************************************************
         * CREATE TABLE xar_template_tags (
         *  xar_id int(11) NOT NULL auto_increment,
         *  xar_name varchar(255) NOT NULL default '',
         *  xar_modid int(11) default 0,
         *  xar_handler varchar(255) NOT NULL default '',
         *  xar_data text,
         *  PRIMARY KEY  (xar_id)
         * )
         *********************************************************************/
        $fields = array(
                        'xar_id'      => array('type'=>'integer','null'=>false,'increment'=>true,'primary_key'=>true),
                        'xar_name'    => array('type'=>'varchar','size'=>255,'null'=>false),
                        'xar_modid'   => array('type'=>'integer','null'=>false,'default'=>'0'),
                        'xar_handler' => array('type'=>'varchar','size'=>255,'null'=>false),
                        'xar_data'    => array('type'=>'text')
                        );
        $query = xarDBCreateTable($templateTagsTable,$fields);
        $dbconn->Execute($query);


        // {ML_dont_parse 'includes/xarMod.php'}
        include_once 'includes/xarMod.php';

        // Start Modules Support
        $systemArgs = array('enableShortURLsSupport' => false,
                            'generateXMLURLs' => false);
        xarMod_init($systemArgs, $whatToLoad);

        /**************************************************************
         * Install modules table and insert the modules module
         **************************************************************/
        xarInstallAPIFunc('initialise', array('directory' => 'modules', 'initfunc'  => 'init'));
        
        /****************************************************************
         * Install roles module and set up default roles
         ****************************************************************/
        xarInstallAPIFunc('initialise', array('directory' => 'roles','initfunc'  => 'init'));

        /**************************************************************
         * Install privileges module and setup default privileges
         **************************************************************/
        xarInstallAPIFunc('initialise', array('directory' => 'privileges','initfunc'  => 'init'));

        $modulesTable = $systemPrefix .'_modules';

        $newModSql   = "INSERT INTO $modulesTable 
                        (xar_id, xar_name, xar_regid, xar_directory, 
                         xar_version, xar_mode, xar_class, xar_category, xar_admin_capable, xar_user_capable, xar_state)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?)";
        $newStmt     = $dbconn->prepareStatement($newModSql);


        $modules = array('authsystem','base','installer','blocks','themes');
        foreach($modules as $index => $modName) {
            // Insert module
            $id = $dbconn->GenId($modulesTable);
            $modversion=array();$bindvars = array();
            include_once "modules/$modName/xarversion.php";
            $bindvars = array($id,                     // system id, generated
                              $modName,         
                              $modversion['id'],       // regid, from xarversion
                              $modName,
                              $modversion['version'],
                              1,
                              $modversion['class'],
                              $modversion['category'],
                              isset($modversion['admin'])?$modversion['admin']:0,
                              isset($modversion['user'])?$modversion['user']:0,
                              3);
            $result = $newStmt->executeUpdate($bindvars);
            $newModId = $dbconn->PO_Insert_ID($tables['modules'], 'xar_id');
        }


        /**************************************************************
         * Install the blocks module
         **************************************************************/
        // FIXME: the installation of the blocks module depends on the modules module
        // to be present, doh !
        xarInstallAPIFunc('initialise', array('directory'=>'blocks', 'initfunc'=>'init'));

        /**************************************************************
         * Install the themes module
         **************************************************************/
        xarInstallAPIFunc('initialise', array('directory'=>'themes', 'initfunc'=>'init'));

        // Fill language list(?)

        // TODO: move this to some common place in Xaraya ?
        // Register BL user tags
        // Include a JavaScript file in a page
        xarTplRegisterTag('base', 'base-include-javascript', array(),'base_javascriptapi_handlemodulejavascript');
        // Render JavaScript in a page
        xarTplRegisterTag('base', 'base-render-javascript', array(),'base_javascriptapi_handlerenderjavascript');

        // TODO: is this is correct place for a default value for a modvar?
        xarModSetVar('base', 'AlternatePageTemplate', 'homepage');

        // We're done, commit all
        $dbconn->commit();
    } catch (Exception $e) {
        // Damn
        $dbconn->rollback();
        throw $e;
    }
    // Initialisation successful
    return true;
}

/**
 * Upgrade the base module from an old version
 *
 * @param oldVersion
 * @returns bool
 */
function base_upgrade($oldVersion)
{
    switch($oldVersion) {
    case '0.1':
        // compatability upgrade, nothing to be done
        break;
    }
    return true;
}

/**
 * Delete the base module
 *
 * @param none
 * @returns bool
 */
function base_delete()
{
  //this module cannot be removed
  return false;
}

?>
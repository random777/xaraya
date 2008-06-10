<?php
/**
 * Base Module Initialisation
 *
 * @package modules
 * @copyright (C) 2005-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage base
 * @link http://xaraya.com/index.php/release/68.html
 * @author Marcel van der Boom
 */

/**
 * Load Table Maintainance API
 */
sys::import('xaraya.tableddl');
/**
 * Initialise the base module
 *
 * @return bool
 * @throws DATABASE_ERROR
 */
function base_init()
{
    $dbconn = xarDB::getConn();
    $tables =& xarDB::getTables();

    $prefix = xarDB::getPrefix();

    // Creating the first part inside a transaction
    try {
        $dbconn->begin();

        /*********************************************************************
         * Here we create non module associated tables
         *
         * prefix_session_info  - Session table
         * prefix_module_vars   - system configuration variables
         *********************************************************************/
        $sessionInfoTable = $prefix . '_session_info';

        sys::import('xaraya.installer');
        Installer::createTable('schema', 'base');

        // Let's commit this, since we're gonna do some other stuff
        $dbconn->commit();
    } catch (Exception $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Start Configuration Unit
    sys::import('xaraya.variables');
    $systemArgs = array();
    xarVar_init($systemArgs);

    $allowableHTML = array (
                            '!--'=>2, 'a'=>2, 'b'=>2, 'blockquote'=>2,'br'=>2, 'center'=>2,
                            'div'=>2, 'em'=>2, 'font'=>0, 'hr'=>2, 'i'=>2, 'img'=>0, 'li'=>2,
                            'marquee'=>0, 'ol'=>2, 'p'=>2, 'pre'=> 2, 'span'=>0,'strong'=>2,
                            'tt'=>2, 'ul'=>2, 'table'=>2, 'td'=>2, 'th'=>2, 'tr'=> 2);

    xarConfigVars::set(null, 'Site.Core.AllowableHTML',$allowableHTML);
    /****************************************************************
     * Set System Configuration Variables
     *****************************************************************/
    xarConfigVars::set(null, 'System.Core.TimeZone', 'Etc/UTC');
    xarConfigVars::set(null, 'System.Core.VersionNum', xarCore::VERSION_NUM);
    xarConfigVars::set(null, 'System.Core.VersionId', xarCore::VERSION_ID);
    xarConfigVars::set(null, 'System.Core.VersionSub', xarCore::VERSION_SUB);
    $allowedAPITypes = array();
    /*****************************************************************
     * Set site configuration variables
     ******************************************************************/
    xarConfigVars::set(null, 'Site.BL.CacheTemplates',true);
    xarConfigVars::set(null, 'Site.BL.CompilerVersion','XAR_BL_USE_XSLT');
    xarConfigVars::set(null, 'Site.Core.FixHTMLEntities',true);
    xarConfigVars::set(null, 'Site.Core.TimeZone', 'Etc/UTC');
    xarConfigVars::set(null, 'Site.Core.EnableShortURLsSupport', false);
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
        xarConfigVars::set(null, 'Site.Core.EnableSecureServer', true);
    } else {
        xarConfigVars::set(null, 'Site.Core.EnableSecureServer', false);
    }

    xarConfigVars::set(null, 'Site.Core.LoadLegacy', false);
    xarConfigVars::set(null, 'Site.Session.SecurityLevel', 'Medium');
    xarConfigVars::set(null, 'Site.Session.Duration', 7);
    xarConfigVars::set(null, 'Site.Session.InactivityTimeout', 90);
    // use current defaults in includes/xarSession.php
    xarConfigVars::set(null, 'Site.Session.CookieName', '');
    xarConfigVars::set(null, 'Site.Session.CookiePath', '');
    xarConfigVars::set(null, 'Site.Session.CookieDomain', '');
    xarConfigVars::set(null, 'Site.Session.RefererCheck', '');
    xarConfigVars::set(null, 'Site.MLS.TranslationsBackend', 'xml2php');
    // FIXME: <marco> Temporary config vars, ask them at install time
    xarConfigVars::set(null, 'Site.MLS.MLSMode', 'SINGLE');

    // The installer should now set the default locale based on the
    // chosen language, let's make sure that is true
    if(!xarConfigVars::get(null, 'Site.MLS.DefaultLocale')) {
        xarConfigVars::set(null, 'Site.MLS.DefaultLocale', 'en_US.utf-8');
        $allowedLocales = array('en_US.utf-8');
        xarConfigVars::set(null, 'Site.MLS.AllowedLocales', $allowedLocales);
    }
    // Minimal information for timezone offset handling (see also Site.Core.TimeZone)
    xarConfigVars::set(null, 'Site.MLS.DefaultTimeOffset', 0);

    $authModules = array('authsystem');
    xarConfigVars::set(null, 'Site.User.AuthenticationModules',$authModules);

    // Start Modules Support
    $systemArgs = array('enableShortURLsSupport' => false,
                        'generateXMLURLs' => false);
    xarMod::init($systemArgs);

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
 * @return bool false, as this module cannot be removed
 */
function base_delete()
{
  //this module cannot be removed
  return false;
}

?>

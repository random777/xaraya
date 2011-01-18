<?php
/**
 * File: $Id$
 *
 * Themes administration
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage themes
 * @author Marty Vance
 */
// Load Table Maintainance API
xarDBLoadTableMaintenanceAPI();

/**
 * Initialise the themes module
 *
 * @param none $
 * @returns bool
 * @raise DATABASE_ERROR
 */
function themes_init()
{
    // Get database information
    $dbconn =& xarDB::getConn();
    $tables =& xarDB::getTables();

    $sitePrefix = xarDBGetSiteTablePrefix();
    $systemPrefix = xarDBGetSystemTablePrefix();

    $tables['themes'] = $systemPrefix . '_themes';
    $tables['theme_states'] = $sitePrefix . '_theme_states';
    $tables['theme_vars'] = $sitePrefix . '_theme_vars';
    // Create tables
    /**
     * Here we create all the tables for the theme system
     *
     * prefix_themes       - basic theme info
     * prefix_theme_states - table to hold states for unshared themes
     * prefix_theme_vars   - theme variables table
     */
    // prefix_themes
    /**
     * CREATE TABLE xar_themes (
     *   xar_id int(11) NOT NULL auto_increment,
     *   xar_name varchar(64) NOT NULL default '',
     *   xar_regid int(10) unsigned NOT NULL default '0',
     *   xar_directory varchar(64) NOT NULL default '',
     *   xar_mode smallint(6) NOT NULL default '1',
     *   xar_author varchar(64) NOT NULL default '',
     *   xar_homepage varchar(64) NOT NULL default '',
     *   xar_email varchar(64) NOT NULL default '',
     *   xar_description varchar(255) NOT NULL default '',
     *   xar_contactinfo varchar(255) NOT NULL default '',
     *   xar_publishdate varchar(32) NOT NULL default '',
     *   xar_license varchar(255) NOT NULL default '',
     *   xar_version varchar(10) NOT NULL default '',
     *   xar_xaraya_version varchar(10) NOT NULL default '',
     *   xar_bl_version varchar(10) NOT NULL default '',
     *   xar_class int(10) unsigned NOT NULL default '0',
     *   PRIMARY KEY  (xar_id)
     * )TYPE=MyISAM;
     */
    $fields = array('xar_id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
        'xar_name' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_regid' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0'),
        'xar_directory' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_mode' => array('type' => 'integer', 'null' => false, 'default' => '1'),
        'xar_author' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_homepage' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_email' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_description' => array('type' => 'varchar', 'size' => 255, 'null' => false),
        'xar_contactinfo' => array('type' => 'varchar', 'size' => 255, 'null' => false),
        'xar_publishdate' => array('type' => 'varchar', 'size' => 32, 'null' => false),
        'xar_license' => array('type' => 'varchar', 'size' => 255, 'null' => false),
        'xar_version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
        'xar_xaraya_version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
        'xar_bl_version' => array('type' => 'varchar', 'size' => 10, 'null' => false),
        'xar_class' => array('type' => 'integer', 'unsigned' => true, 'null' => false, 'default' => '0')
        );

    $query = xarDBCreateTable($tables['themes'], $fields);
    $result =& $dbconn->Execute($query);
    if(!$result) return;

    // prefix_theme_states
    /**
     * CREATE TABLE xar_theme_states (
     *   xar_regid int(10) unsigned NOT NULL default '0',
     *   xar_state int(11) NOT NULL default '1'
     * ) TYPE=MyISAM;
     */
    $fields = array('xar_regid' => array('type' => 'integer', 'null' => false, 'unsigned' => true, 'primary_key' => false),
        'xar_state' => array('type' => 'integer', 'null' => false, 'default' => '0')
        );

    $query = xarDBCreateTable($tables['theme_states'], $fields);
    $res = &$dbconn->Execute($query);
    if (!$res) return;
    // prefix_theme_vars
    /**
     * CREATE TABLE xar_theme_vars (
     *   xar_id int(11) NOT NULL auto_increment,
     *   xar_themeName varchar(64) NOT NULL default '',
     *   xar_name varchar(64) NOT NULL default '',
     *   xar_prime int(1) NOT NULL default 1,
     *   xar_description varchar(255) NOT NULL default '',
     *   xar_value longtext,
     *   PRIMARY KEY  (xar_id)
     * ) TYPE=MyISAM;
     */
    $fields = array('xar_id' => array('type' => 'integer', 'null' => false, 'increment' => true, 'primary_key' => true),
        'xar_themeName' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_name' => array('type' => 'varchar', 'size' => 64, 'null' => false),
        'xar_prime' => array('type' => 'integer', 'null' => false, 'default' => 1),
        'xar_description' => array('type' => 'varchar', 'size' => 255, 'null' => false),
        'xar_value' => array('type' => 'text', 'size' => 'longtext')
        );

    $query = xarDBCreateTable($tables['theme_vars'], $fields);

    $res = &$dbconn->Execute($query);
    if (!$res) return;

    xarModSetVar('themes', 'default', 'installer');
    xarModSetVar('themes', 'selsort', 'nameasc');

    // Register Custom BlockLayout tags
    // Include a StyleSheet file in a page
    xarTplRegisterTag(
        'themes', 'themes-include-stylesheet', array(),
        'themes_stylesheetapi_handleincludestylesheet'
    );
    // Render StyleSheet in a page
    xarTplRegisterTag(
        'themes', 'themes-render-stylesheet', array(),
        'themes_stylesheetapi_handlerenderstylesheet'
    );

    // Initialisation successful
    return true;
}

function themes_activate()
{
    if (file_exists(xarConfigGetVar('Site.BL.ThemesDirectory').'/Xaraya_Classic/xartheme.php')) {
        xarModSetVar('themes', 'default', 'Xaraya_Classic');
    }

    if (!xarModRegisterHook('item', 'usermenu', 'GUI',
            'themes', 'user', 'usermenu')) {
        return false;
    }
    // make sure we dont miss empty variables (which were not passed thru)
    if (empty($selstyle)) $selstyle = 'plain';
    if (empty($selfilter)) $selfilter = XARMOD_STATE_ANY;
    if (empty($hidecore)) $hidecore = 0;
    if (empty($selsort)) $selsort = 'namedesc';

    xarModSetVar('themes', 'hidecore', $hidecore);
    xarModSetVar('themes', 'selstyle', $selstyle);
    xarModSetVar('themes', 'selfilter', $selfilter);
    xarModSetVar('themes', 'selsort', $selsort);

    xarModSetVar('themes', 'SiteName', 'Your Site Name');
    xarModSetVar('themes', 'SiteSlogan', 'Your Site Slogan');
    xarModSetVar('themes', 'SiteCopyRight', '&copy; Copyright 2003 ');
    xarModSetVar('themes', 'SiteTitleSeparator', ' :: ');
    xarModSetVar('themes', 'SiteTitleOrder', 'default');
    xarModSetVar('themes', 'SiteFooter', '<a href="http://www.xaraya.com"><img src="modules/base/xarimages/xaraya.gif" alt="Powered by Xaraya" style="border:0px;" /></a>');
    xarModSetVar('themes', 'ShowPHPCommentBlockInTemplates', 0);
    xarModSetVar('themes', 'ShowTemplates', 0);
    // Register blocks
    if(!xarMod::apiFunc('blocks','admin','block_type_exists',array('modName' => 'themes','blockType' => 'meta'))) {
        if (!xarMod::apiFunc('blocks',
                           'admin',
                           'register_block_type',
                           array('modName' => 'themes',
                                 'blockType' => 'meta'))) return;
    }
    // Register blocks
    if(!xarMod::apiFunc('blocks','admin','block_type_exists',array('modName' => 'themes','blockType' => 'syndicate'))) {
        if (!xarMod::apiFunc('blocks',
                           'admin',
                           'register_block_type',
                           array('modName' => 'themes',
                                 'blockType' => 'syndicate'))) return;
    }
    return true;
}

/**
 * Upgrade the themes theme from an old version
 *
 * @param oldversion $ the old version to upgrade from
 * @returns bool
 */
function themes_upgrade($oldversion)
{
    // Upgrade dependent on old version number
    switch ($oldversion) {
        case 1.0:

            if (!xarModRegisterHook('item', 'usermenu', 'GUI', 'themes', 'user', 'usermenu')) {
                return false;
            }

        case 1.1:

            if (!xarMod::apiFunc('blocks', 'admin', 'register_block_type',
                array('modName' => 'themes', 'blockType' => 'meta'))) return;
        case 1.2:

    }
    // Update successful
    return true;
}

/**
 * Delete the themes theme
 *
 * @param none $
 * @returns bool
 */
function themes_delete()
{
    // this module cannot be removed
    return false;
}

?>

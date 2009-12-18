<?php
/**
 * Checks new themes
 *
 * @package modules
 * @copyright (C) 2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Checks for new themes, adding them to the database if any are found
 *
 * @author Chris Powis
 * @param none
 * @return bool null on exceptions, true on sucess to update
 * @throws NO_PERMISSION
 */
function themes_adminapi_checknew()
{
    static $check = false;

    //Now with dependency checking, this function may be called multiple times
    //Let's check if it already return ok and stop the processing here
    if ($check) {return true;}

    // Security Check
    // need to specify the module because this function is called by the installer module
    if(!xarSecurityCheck('AdminTheme',1,'All','All','themes')) return;

    //Get all modules in the filesystem
    $fileThemes = xarModAPIFunc('themes','admin','getfilethemes');
    if (!isset($fileThemes)) return;

    // Get all modules in DB
    $dbThemes = xarModAPIFunc('themes','admin','getdbthemes');
    if (!isset($dbThemes)) return;

    //Setup database object for theme insertion
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // See if we have gained any themes since last generation
    foreach ($fileThemes as $name => $themeInfo) {
        foreach ($dbThemes as $dbtheme) {
            // Bail if 2 themes have the same regid but not the same name
            if(($themeInfo['regid'] == $dbtheme['regid']) && ($themeInfo['name'] != $dbtheme['name'])) {
                $msg = xarML('The same registered ID (#(1)) was found belonging to a #(2) theme in the file system and a registered #(3) theme in the database. Please correct this and regenerate the list.', $dbtheme['regid'], $themeInfo['name'], $dbtheme['name']);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
            // Bail if 2 themes have the same name but not the same regid
            if(($themeInfo['name'] == $dbtheme['name']) && ($themeInfo['regid'] != $dbtheme['regid'])) {
                $msg = xarML('The theme #(1) is found with two different registered IDs, #(2)  in the file system and #(3) in the database. Please correct this and regenerate the list.', $themeInfo['name'], $themeInfo['regid'], $dbtheme['regid']);
                xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM',
                               new SystemException($msg));
                return;
            }
        }
        if (empty($dbThemes[$name])) {
            // New theme

            if (empty($themeInfo['xar_version'])){
                $themeInfo['xar_version'] = '1.0.0';
            }
            if ($themeInfo['bl_version'] == '1.0') {
                $themeInfo['bl_version'] = '1.0.0';
            }

            $themeId = $dbconn->GenId($xartable['themes']);
            $sql = "INSERT INTO $xartable[themes]
                      (xar_id, xar_name, xar_regid, xar_directory,
                       xar_author, xar_homepage, xar_email, xar_description,
                       xar_contactinfo, xar_publishdate, xar_license,
                       xar_version, xar_xaraya_version, xar_bl_version,
                       xar_class)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $bindvars = array($themeId,$themeInfo['name'],$themeInfo['regid'],
                              $themeInfo['directory'],$themeInfo['author'],
                              $themeInfo['homepage'],$themeInfo['email'],
                              $themeInfo['description'],$themeInfo['contact_info'],
                              $themeInfo['publish_date'],$themeInfo['license'],
                              $themeInfo['version'],$themeInfo['xar_version'],
                              $themeInfo['bl_version'],$themeInfo['class']);
            $result = $dbconn->Execute($sql,$bindvars);
            if (!$result) return;

            if (!xarModAPIFunc('themes',
                                'admin',
                                'setstate',
                                array('regid' => $themeInfo['regid'],
                                      'state' => XARTHEME_STATE_UNINITIALISED))) return;
        }
    }

    $check = true;
    return true;
}
?>

<?php
/**
 * Get all themes in the database
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Get all themes in the database
 *
 * @author Marty Vance
 * @param none
 * @return array of themes in the database
 */
function themes_adminapi_getdbthemes()
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    $dbThemes = array();

    // Get all themes in DB
    $sql = "SELECT xar_regid
              FROM $xartable[themes]";
    $result = $dbconn->Execute($sql);
    if (!$result) return;
    if (!$result) {
        $msg = 'Could not get any themes';
        xarSessionSetVar('errormsg',xarML($msg));
        return false;
    }

    while(!$result->EOF) {
        list($themeRegId) = $result->fields;
        //Get Theme Info
        $themeInfo = xarThemeGetInfo($themeRegId);
        if (!isset($themeInfo)) return;

        $name = $themeInfo['name'];
        //Push it into array (should we change to index by regid instead?)
        $dbThemes[$name] = array('name'    => $name,
                                  'regid'   => $themeRegId,
                                  'version' => $themeInfo['version'],
                                  'mode'    => $themeInfo['mode'],
                                  'state'   => $themeInfo['state']);
        $result->MoveNext();
    }
    $result->Close();

    return $dbThemes;
}

?>
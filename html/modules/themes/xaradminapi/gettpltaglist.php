<?php
/**
 * Get registered template tags
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 */
/**
 * Get registered template tags
 *
 * @author Marty Vance
 * @param none
 * @returns array
 * @return array of themes in the database
 * @Author Simon Wunderlin <sw@telemedia.ch>
 */
function themes_adminapi_gettpltaglist($args)
{
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
        
        extract($args);

    $aTplTags = array();

    // Get all registered tags from the DB
    $bindvars = array();
    $sSql = "SELECT xar_id, xar_name, xar_module
              FROM $xartable[template_tags] WHERE 1=1 ";
        if (isset($module) && trim($module) != '') {
            $sSql .= " AND xar_module = ?";
            $bindvars[] = $module;
        }
        if (isset($id) && trim($id) != '') {
            $sSql .= " AND xar_id = ? ";
            $bindvars[] = $id;
        }
        
    $oResult = $dbconn->Execute($sSql,$bindvars);
    if (!$oResult) return;
    if (!$oResult) {
        $sMsg = 'Could not get any Tags';
        xarSessionSetVar('errormsg',xarML($sMsg));
        return false;
    }

    while(!$oResult->EOF) {
            $aTplTags[] = array(
                    'id'      => $oResult->fields[0], 
                    'name'    => $oResult->fields[1], 
                    'module'  => $oResult->fields[2]
                );
        
        $oResult->MoveNext();
    }
    $oResult->Close();

    return $aTplTags;
}

?>
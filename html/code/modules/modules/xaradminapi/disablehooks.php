<?php
/**
 * @package Xaraya eXtensible Management System
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Disable hooks between a caller module and a hook module
 * Note : generic hooks will not be disabled if a specific item type is given
 *
 * @author Xaraya Development Team
 * @param $args['callerModName'] caller module
 * @param $args['callerItemType'] optional item type for the caller module
 * @param $args['hookModName'] hook module
 * @returns bool
 * @return true if successfull
 * @throws BAD_PARAM
 */
function modules_adminapi_disablehooks($args)
{
    // Security Check (called by other modules, so we can't use one this here)
    //    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName)) throw new EmptyParameterException('callerModName');
    if (empty($hookModName))  throw new EmptyParameterException('hookModName');

    if (empty($callerItemType)) {
        $callerItemType = '';
    }

    // Rename operation
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();

    // Delete hooks regardless
    // New query: select on the mod id's instead of their names
    // optionally: get the ids first and then use the select
    // better: construct a join with the modules table but not possible for postgres for example
    $smodInfo = xarMod_GetBaseInfo($callerModName);
    $smodId = $smodInfo['systemid'];
    $tmodInfo = xarMod_GetBaseInfo($hookModName);
    $tmodId = $tmodInfo['systemid'];
    $sql = "DELETE FROM $xartable[hooks] WHERE s_module_id = ? AND s_type = ? AND t_module_id = ?";
    $stmt = $dbconn->prepareStatement($sql);

    try {
        $dbconn->begin();
        $bindvars = array($smodId,$callerItemType,$tmodId);
        $stmt->executeUpdate($bindvars);
        $dbconn->commit();
    } catch (SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    return true;
}

?>

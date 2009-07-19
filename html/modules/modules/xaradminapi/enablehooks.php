<?php
/**
 * Enable hooks between a caller module and a hook module
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Enable hooks between a caller module and a hook module
 * Note : hooks will be enabled for all item types if no specific item type is given
 *
 * @author Xaraya Development Team
 * @param $args['callerModName'] caller module
 * @param $args['callerItemType'] optional item type for the caller module
 * @param $args['hookModName'] hook module
 * @returns bool
 * @return true if successfull
 * @throws BAD_PARAM
 */
function modules_adminapi_enablehooks($args)
{
// Security Check (called by other modules, so we can't use one this here)
//    if(!xarSecurityCheck('AdminModules')) return;

    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($callerModName) || empty($hookModName)) {
        $msg = xarML('callerModName or hookModName');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
        return;
    }
    if (empty($callerItemType)) {
        $callerItemType = '';
    }

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // get all available/currently enabled hooks for this module
    $sql = "SELECT xar_id, xar_object, xar_action, xar_smodule, xar_stype,
                    xar_tarea, xar_tmodule, xar_ttype, xar_tfunc, xar_order
                FROM $xartable[hooks]
                WHERE xar_smodule = ? OR xar_smodule = ''
                ORDER BY xar_smodule, xar_order";

    $bindvars = array($callerModName);
    $result =& $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    $enabledhooks = array();
    $availablehooks = array();
    for (; !$result->EOF; $result->MoveNext()) {
        list($hookid, $hookobject, $hookaction, $hooksmodule, $hookstype,
             $hooktarea, $hooktmodule, $hookttype, $hooktfunc, $hookorder) = $result->fields;

        if ($hooksmodule == '') {
            // we only want $hookModName
            if($hooktmodule == $hookModName) {
                if(!isset($availablehooks[$hooktmodule])) {
                    $availablehooks[$hooktmodule] = array();
                }
                $availablehooks[$hooktmodule][$hookid] = array('id' => $hookid, 'object' => $hookobject,
                    'action' => $hookaction, 'smodule' => trim($hooksmodule), 'stype' => $hookstype,
                    'tarea' => $hooktarea, 'tmodule' => $hooktmodule, 'ttype' => $hookttype,
                    'tfunc' => $hooktfunc, 'order' => $hookorder);
            }
        } else {
            if(!isset($enabledhooks[$hooktmodule])) {
                $enabledhooks[$hooktmodule] = array();
            }
            $enabledhooks[$hooktmodule][$hooksmodule][$hookstype] = $hookorder;
        }
    }
    $result->Close();

    // check for invalid module name
    if (!isset($availablehooks[$hookModName])) {
        $msg = xarML('invalid hookModName');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', $msg);
        return;
    }

    // reduce available hooks down to only what we want
    $thook = $availablehooks[$hookModName];

    // check for duplicates, return true if found
    // otherwise, get order from existing hooks
    if (isset($enabledhooks[$hookModName][$callerModName][$callerItemType])) {
        return true;
    }

    // find next hook order if not set
    if (!isset($nextorder)) {
        $sql = "SELECT xar_order
                    FROM $xartable[hooks]
                    WHERE xar_smodule = ?
                    ORDER BY xar_order LIMIT 1";

        $result =& $dbconn->Execute($sql,array($callerModName));
        if (!$result) return;

        for (; !$result->EOF; $result->MoveNext()) {
            list($nextorder) = $result->fields;
        }
        if (!isset($nextorder)) {
            $nextorder = 1;
        } else {
            $nextorder++;
        }
    }

    // loop through target hook, insert what we need
    foreach($thook as $targetmod => $targethook) {
        $sql = "INSERT INTO $xartable[hooks] (
                      xar_id, xar_object, xar_action, xar_smodule, xar_stype,
                      xar_tarea, xar_tmodule, xar_ttype, xar_tfunc, xar_order)
                    VALUES (?,?,?,?,?,?,?,?,?,?)";
        $bindvars = array($dbconn->GenId($xartable['hooks']),
                        'object' => $targethook['object'],
                        'action' => $targethook['action'],
                        'smodule' => $callerModName,
                        'stype' => $callerItemType,
                        'tarea' => $targethook['tarea'],
                        'tmodule' => $hookModName,
                        'ttype' => $targethook['ttype'],
                        'tfunc' => $targethook['tfunc'],
                        'order' => $nextorder);
        $result =& $dbconn->Execute($sql,$bindvars);
        if (!$result) return;
    }

    $result->Close();

    return true;
}

?>
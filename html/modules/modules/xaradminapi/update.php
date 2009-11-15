<?php
/**
 * Update module information
 *
 * @package modules
 * @copyright (C) 2005-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Modules module
 */
/**
 * Update module information
 * @param $args['regid'] the id number of the module to update
 * @param $args['displayname'] the new display name of the module
 * @param $args['description'] the new description of the module
 * @return bool true on success, false on failure
 */
function modules_adminapi_update($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($regid)) {
        $msg = xarML('Empty regid (#(1)).', $regid);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }
    if (!isset($hookorder)) {
        $hookorder = array();
    } elseif (!is_array($hookorder)) {
        $hookorder = explode(';', ';'.$hookorder);
        $hookorder = array_slice($hookorder, 1, count($hookorder), true);
        $hookorder = array_flip($hookorder);
    }
    $hookindex = count($hookorder) + 1;

    // Security Check
    if(!xarSecurityCheck('AdminModules',0,'All',array('All','All',$regid))) return;

    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();

    // Get module name
    $modinfo = xarModGetInfo($regid);
    $modname = $modinfo['name'];

    $enabledhooks = array();
    $availablehooks = array();
    $formhooks = array();

    // get all available/enabled hooks for this module
    $sql = "SELECT xar_id, xar_object, xar_action, xar_smodule, xar_stype,
                    xar_tarea, xar_tmodule, xar_ttype, xar_tfunc, xar_order
                FROM $xartable[hooks]
                WHERE xar_smodule = ? OR xar_smodule = ''
                ORDER BY xar_smodule, xar_order";

    $bindvars = array($modname);
    $result =& $dbconn->Execute($sql,$bindvars);
    if (!$result) return;

    for (; !$result->EOF; $result->MoveNext()) {
        list($hookid, $hookobject, $hookaction, $hooksmodule, $hookstype,
             $hooktarea, $hooktmodule, $hookttype, $hooktfunc, $thookorder) = $result->fields;

        if (!xarVarFetch("hooks_$hooktmodule", 'isset', $hookvalue,  NULL, XARVAR_DONT_SET)) {return;}
        if(is_array($hookvalue)) {
            // if 0 is checked, we don't need the rest
            if (isset($hookvalue[0])) {
                $hookvalue = array(1);
            }
            // more than one itemtype, unset 0
            if (count($hookvalue) > 1 && isset($hookvalue[0])) {
                unset($hookvalue[0]);
            }
            $formhooks[$hooktmodule] = $hookvalue;
        }
        unset($hookvalue);

        if ($hooksmodule == '') {
            if(!isset($availablehooks[$hooktmodule])) {
                $availablehooks[$hooktmodule] = array();
            }
            $availablehooks[$hooktmodule][$hookid] = array('id' => $hookid, 'object' => $hookobject,
                'action' => $hookaction, 'smodule' => trim($hooksmodule), 'stype' => $hookstype,
                'tarea' => $hooktarea, 'tmodule' => $hooktmodule, 'ttype' => $hookttype,
                'tfunc' => $hooktfunc, 'order' => $thookorder);
        } else {
            if(!isset($enabledhooks[$hooktmodule])) {
                $enabledhooks[$hooktmodule] = array();
            }
            $enabledhooks[$hooktmodule][] = $modname . ':' . ($hookstype == '' ? 0 : $hookstype) . ':' . $hooktmodule;
        }
    }
    $result->Close();

    ksort($availablehooks);
    ksort($enabledhooks);
    ksort($formhooks);

    $deletehooks = array();
    // loop through available hooks
    foreach ($availablehooks as $hooktmodule => $thook) {
        if(!isset($formhooks[$hooktmodule])) {
            // hook disabled for all itemtypes
            // queue for deletion if needed, by 'smodule:stype:tmodule'
            if (isset($enabledhooks[$hooktmodule])) {
                foreach ($enabledhooks[$hooktmodule] as $dhook) {
                    if (!in_array($dhook, $deletehooks)) {
                        $deletehooks[] = $dhook;
                    }
                }
            }
            unset($enabledhooks[$hooktmodule]);
            unset($hookorder[$hooktmodule]);
        } else {
            // hook order
            if (isset($hookorder[$hooktmodule])) {
                $hookneworder = $hookorder[$hooktmodule];
            } else {
                // add newly enabled hooks at the end of the order
                $hookindex = count($hookorder) + 1;
                $hookorder[$hooktmodule] = $hookindex;
                $hookneworder = $hookorder[$hooktmodule];
            }

            // loop through existing enabled hooks for itemtype hooks to remove
            if (isset($enabledhooks[$hooktmodule])) {
                foreach ($enabledhooks[$hooktmodule] as $ehook) {
                    list($esmod, $estype, $etmod) = explode(':', $ehook);
                    if (!isset($formhooks[$hooktmodule][$estype]) && !in_array("$esmod:$estype:$etmod", $deletehooks)) {
                        $deletehooks[] = $esmod . ':' . $estype . ':' . $etmod;
                    }
                }
            }

            // Insert hooks if required, or queue for deletion
            foreach (array_keys($formhooks[$hooktmodule]) as $itemtype) {
                // not already enabled, insert
                if(!isset($enabledhooks[$hooktmodule]) || !in_array("$modname:$itemtype:$hooktmodule", $enabledhooks[$hooktmodule])) {
                    if ($itemtype == 0) $itemtype = '';
                    foreach($thook as $hookinstance) {
                        $sql = "INSERT INTO $xartable[hooks] (
                              xar_id, xar_object, xar_action, xar_smodule,
                              xar_stype, xar_tarea, xar_tmodule, xar_ttype, xar_tfunc, xar_order)
                              VALUES (?,?,?,?,?,?,?,?,?,?)";
                        $bindvars = array($dbconn->GenId($xartable['hooks']),
                                          $hookinstance['object'],
                                          $hookinstance['action'],
                                          $modinfo['name'],
                                          $itemtype,
                                          $hookinstance['tarea'],
                                          $hookinstance['tmodule'],
                                          $hookinstance['ttype'],
                                          $hookinstance['tfunc'],
                                          $hookneworder);
                        $subresult =& $dbconn->Execute($sql,$bindvars);
                        if (!$subresult) return;
                    }
                }
            }
        }
    }

    // update hook order
    $order = 1;
    foreach ($hookorder as $tmod => $torder) {
        $sql = "UPDATE $xartable[hooks] SET xar_order = ? 
                WHERE xar_smodule = ? AND xar_tmodule = ?";
        $bindvars = array($order, $modname, $tmod);
        $result =& $dbconn->Execute($sql,$bindvars);
        if (!$result) return;
        $order++;
    }

    // delete queued hooks, if any
    if(count($deletehooks) > 0) {
        foreach ($deletehooks as $del) {
            list($dsmod, $dstype, $dtmod) = explode(':', $del);
            $sql = "DELETE FROM $xartable[hooks] WHERE ";
            if ($dstype == '' || $dstype == 0) {
                $sql .= "xar_smodule = ? AND xar_tmodule = ? and xar_stype = ?";
                $bindvars = array($dsmod, $dtmod, '');
            } else {
                $sql .= "xar_smodule = ? AND xar_tmodule = ? AND xar_stype = ?";
                $bindvars = array($dsmod, $dtmod, $dstype);
            }
            $result =& $dbconn->Execute($sql,$bindvars);
            if (!$result) return;
        }
    }

    $result->Close();

    return true;
}

?>

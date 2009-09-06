<?php
/**
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage roles
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Clear sessions 
 * 
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['id']
 * @return true on success, false otherwise
 * @todo Move this to sessions subsystem, doesnt belong here.
 */
function roles_adminapi_clearsessions($spared)
{
    if(!isset($spared)) throw new EmptyParameterException('spared');

    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $sessionstable = $xartable['session_info'];

    $query = "SELECT id, role_id FROM $sessionstable";
    $result = $dbconn->executeQuery($query);

    // Prepare query outside the loop
    $sql = "DELETE FROM $sessionstable WHERE id = ?";
    $stmt = $dbconn->prepareStatement($sql);
    try {
        $dbconn->begin();
        while ($result->next()) {
            list($thissession, $thisid) = $result->fields;
            foreach ($spared as $id) {
                $thisrole = Roles_Roles::get($thisid);
                $thatrole = Roles_Roles::get($id);
                if (!$thisid == $id && !$thisrole->isParent($thatrole)) {
                    $stmt->executeUpdate(array($thisid));
                    break;
                }
            }
        }
        $dbconn->commit();
    } catch(SQLException $e) {
        $dbconn->rollback();
        throw $e;
    }

    // Security Check
    if(!xarSecurityCheck('EditRole')) return;


    return true;
}

?>

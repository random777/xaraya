<?php
/**
 * Get a specific user by any of his attributes
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * get a specific user by any of his attributes
 * uname, id and email are guaranteed to be unique,
 * otherwise the first hit will be returned
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['id'] id of user to get
 * @param $args['uname'] user name of user to get
 * @param $args['name'] name of user to get
 * @param $args['email'] email of user to get
 * @returns array
 * @return user array, or false on failure
 */
function roles_userapi_get($args)
{
    // Get arguments from argument array
    extract($args);
    if (empty($id) && empty($name) && empty($uname) && empty($email)) {
        throw new EmptyParameterException('id or name or uname or email');
    } elseif (!empty($id) && !is_numeric($id)) {
        throw new VariableValidationException(array('id',$id,'numeric'));
    }
    if ((empty($itemid) && !empty($id))) {
        $itemid = $id;
    }
    
    $dbconn = xarDB::getConn();
    $xartable = xarDB::getTables();
    $rolestable = $xartable['roles'];

    // Get user
    $query = "SELECT id, uname, name, itemtype, email, pass, date_reg, valcode, state FROM $rolestable";
    $bindvars = array();
    $cnt = 0;
    
    $queryWhere = " WHERE ";
    
    if (!empty($id) && is_numeric($id)) {
        $cnt++;
        $queryWhere .= " id = ? ";
        $bindvars[] = (int)$id;
    }
    if (!empty($name)) {        
        if($cnt == 1){
            $queryWhere .= "  AND name = ? ";
        }
        else{
            $queryWhere .= " name = ?"; 
        }
        $cnt++;
        $bindvars[] = $name;
    }
    if (!empty($uname)) {       
        if($cnt >= 1){
            $queryWhere .= " AND uname = ? ";
        }
        else{
            $queryWhere .= " uname = ?";
        }
        $cnt++;
        $bindvars[] = $uname;
    }
    if (!empty($email)) {
        if($cnt >= 1){
            $queryWhere .= " AND email = ? ";
        }
        else{
            $queryWhere .= " email = ?";
        }        
        $cnt++;
        $bindvars[] = $email;
    }
    if (!empty($state) && $state == ROLES_STATE_CURRENT) {
        if($cnt >= 1){
            $queryWhere .= " AND state != ? ";
        }
        else{
            $queryWhere .= " state != ?";
        }
        $cnt++;
        $bindvars[] = ROLES_STATE_DELETED;
    }
    elseif (!empty($state) && $state != ROLES_STATE_ALL) {
        if($cnt >= 1){
            $queryWhere .= " AND state = ? ";
        }
        else{
            $queryWhere .= " state = ?";
        }
        $cnt++;
        
        $bindvars[] = (int)$state;
    }
    
    if (!empty($itemtype)) {
        if($cnt >= 1){
            $queryWhere .= " AND itemtype = ? ";
        }
        else{
            $queryWhere .= " itemtype = ?";
        }
        $cnt++;        
        $bindvars[] = $itemtype;        
    }
    if($cnt >= 1)
    {
        $query .= $queryWhere;
    }
    $stmt = $dbconn->prepareStatement($query);
    $result = $stmt->executeQuery($bindvars, ResultSet::FETCHMODE_ASSOC);
    // Check for no rows found, and if so return
    $result->next();
    $user = $result->getRow();
    if (empty($user)) return false;
    // id is a reserved/key words in Oracle et al.
    $user['id'] = $user['id'];
    $user['itemtype'] = $user['itemtype'];
    return $user;
}

?>
<?php
/**
 * Get a specific user by any of his attributes
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 * get a specific user by any of his attributes
 * uname, uid and email are guaranteed to be unique,
 * otherwise the first hit will be returned
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['uid'] id of user to get
 * @param $args['uname'] user name of user to get
 * @param $args['name'] name of user to get
 * @param $args['email'] email of user to get
 * @param int $args['state'] Status of the user to get
 * @param int $args['type'] set to 1 for group (default 0 = user) 
 * NOTE: for groups, use 'name' not 'uname'
 * @return array user array, or false on failure
 */
function roles_userapi_get($args)
{
    // Get arguments from argument array
    extract($args);
    // Argument checks
    if (empty($uid) && empty($name) && empty($uname) && empty($email)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    } elseif (!empty($uid) && !is_numeric($uid)) {
        $msg = xarML('Wrong arguments to roles_userapi_get.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION,
                    'BAD_PARAM',
                     new SystemException($msg));
        return false;
    }

    if (empty($type)) $type = 0;

    $xartable =& xarDBGetTables();
    $rolestable = $xartable['roles'];

    // Get user
    $q = new xarQuery('SELECT',$rolestable);
    $q->addfields(array(
                  'xar_uid', // UID is a reserved word in Oracle (cannot be redefined)
                  'xar_uname AS uname',
                  'xar_name AS name',
                  'xar_type', // TYPE is a key word in several databases (avoid for the future)
                  'xar_email AS email',
                  'xar_pass AS pass',
                  'xar_date_reg AS date_reg',
                  'xar_valcode AS valcode',
                  'xar_state AS state'
                ));
    if (!empty($uid) && is_numeric($uid)) {
        $q->eq('xar_uid',(int)$uid);
    }
    if (!empty($name)) {
        $q->eq('xar_name',$name);
    }
    if (!empty($uname)) {
        $q->eq('xar_uname',$uname);
    }
    if (!empty($email)) {
        $q->eq('xar_email',$email);
    }
    if (!empty($state) && $state == ROLES_STATE_CURRENT) {
        $q->ne('xar_state',ROLES_STATE_DELETED);
    }
    elseif (!empty($state) && $state != ROLES_STATE_ALL) {
        $q->eq('xar_state',(int)$state);
    }
    $q->eq('xar_type',$type);
    if (!$q->run()) return;

    // Check for no rows found, and if so return
    $user = $q->row();
    if ($user == array()) return false;
    // uid and type are reserved/key words in Oracle et al.
    $user['uid'] = $user['xar_uid'];
    $user['type'] = $user['xar_type'];
    return $user;
}

?>

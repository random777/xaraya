<?php
/**
 * Online Block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */

/**
 * Online Block
 * @author Jim McDonald, Greg Allan, John Cox, Michael Makushev
 */
/*
 * initialise block
 */
function roles_onlineblock_init()
{
    // No parameters accepted by this block.
    return array(
        'nocache' => 0, // cache by default
        'pageshared' => 1, // share across pages
        'usershared' => 1, // share for group members
        'cacheexpire' => null);
}

/**
 * get information on block
 */
function roles_onlineblock_info()
{
    return array(
        'text_type' => 'Online',
        'module' => 'roles',
        'text_type_long' => 'Display who is online'
    );
}

/**
 * Display func.
 * @param $blockinfo array containing title,content
 */
function roles_onlineblock_display($blockinfo)
{
    // Security check
    if (!xarSecurityCheck('ViewRoles',0,'Block',"online:$blockinfo[title]:$blockinfo[bid]")) {return;}

    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Database setup
    // TODO: do we need this query? I'd have thought userapi/getallactive gives
    // us everything we need.
    $dbconn =& xarDBGetConn();
    $xartable =& xarDBGetTables();
    $sessioninfotable = $xartable['session_info'];
    $activetime = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
    if($dbconn->databaseType == 'sqlite') {
        $sql = "SELECT COUNT(*)
                FROM (SELECT DISTINCT xar_uid FROM $sessioninfotable
                      WHERE xar_lastused > ? AND xar_uid > 2)";
    } else {
        $sql = "SELECT COUNT(DISTINCT xar_uid)
            FROM $sessioninfotable
            WHERE xar_lastused > ? AND xar_uid > 2";
    }
    $result = $dbconn->Execute($sql, array($activetime));
    if (!$result) {return false;}
    list($args['numusers']) = $result->fields;
    $result->Close();
    if (empty($args['numusers'])) {
        $args['numusers'] = 0;
    }

    // FIXME: there could be many active users, but we only want a handful of them.
    $zz = xarModAPIFunc(
        'roles', 'user', 'getallactive',
        array(
            'order' => 'name',
            'startnum' => 0,
            'include_anonymous' => false,
            'include_myself' => true
        )
    );

    if (!empty($zz)) {
        foreach ($zz as $key => $aa) {
            $args['test1'][$key] = array(
                'name' => $aa['name'],
                'userurl' => xarModURL(
                    'roles', 'user', 'display',
                    array('uid' => $aa['uid'])
                ),
                'total' => '',
                'unread' => '',
                'messagesurl' => ''
            );

            if ($aa['name'] == xarUserGetVar('name')) {
                if (xarModIsAvailable('messages')) {
                    $args['test1'][$key]['total'] = xarModAPIFunc(
                        'messages', 'user', 'count_total',
                        array('uid'=>$aa['uid'])
                    );

                    $args['test1'][$key]['unread'] = xarModAPIFunc(
                        'messages', 'user', 'count_unread',
                        array('uid'=>$aa['uid'])
                    );

                    $args['test1'][$key]['messagesurl'] =xarModURL(
                        'messages', 'user', 'display',
                        array('uid'=>$aa['uid'])
                    );
                }
            }
        }
    }


    if($dbconn->databaseType == 'sqlite') {
        $query2 = "SELECT COUNT(*)
                   FROM (SELECT DISTINCT xar_ipaddr FROM $sessioninfotable
                         WHERE xar_lastused > ? AND xar_uid = 2)";
    } else {
        $query2 = "SELECT COUNT(DISTINCT xar_ipaddr)
               FROM $sessioninfotable
               WHERE xar_lastused > ? AND xar_uid = 2";
    }
    $result2 = $dbconn->Execute($query2, array($activetime));
    if (!$result2) {return false;}
    list($args['numguests']) = $result2->fields;
    $result2->Close();
    if (empty($args['numguests'])) {
        $args['numguests'] = 0;
    }

    // Pluralise
    if ($args['numguests'] == 1) {
         $args['guests'] = xarML('guest');
    } else {
         $args['guests'] = xarML('guests');
    }

    if ($args['numusers'] == 1) {
         $args['users'] = xarML('user');
    } else {
         $args['users'] = xarML('users');
    }

    $uid = xarModGetVar('roles', 'lastuser');

    // Make sure we have a lastuser
    if (!empty($uid)) {
        if(!is_numeric($uid)) {
        //Remove this further down the line
            $status = xarModAPIFunc(
            'roles', 'user', 'get',
            array('uname' => $uid)
            );

        } else {
            $status = xarModAPIFunc(
            'roles', 'user', 'get',
            array('uid' => $uid)
            );

        }
         // Check return
         if ($status) {$args['lastuser'] = $status;}
    }

    $args['blockid'] = $blockinfo['bid'];
    $blockinfo['content'] = $args;
    return $blockinfo;
}

?>

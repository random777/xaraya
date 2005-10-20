<?php
/**
 * View users
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 */
/**
 * @author  Marc Lutolf <marcinmilan@xaraya.com>
 * view users
 */
function roles_user_view($args)
{
    extract($args);

    // Get parameters
    if(!xarVarFetch('startnum', 'int:1', $startnum, 1, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('phase', 'enum:active:viewall', $phase, 'active', XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('name', 'notempty', $data['name'], '', XARVAR_NOT_REQUIRED)) {return;}

    // This $filter variable isnt being used for anything...
    // It is set later on.
    if(!xarVarFetch('filter', 'str', $filter, NULL, XARVAR_DONT_SET)) {return;}

    if(!xarVarFetch('letter', 'str:1:2', $letter, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('search', 'str:1:100', $search, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('order', 'enum:name:uname:email:uid:state:date_reg', $order, 'name', XARVAR_NOT_REQUIRED)) {return;}

    // Bug 3338: disable 'selection' since it allows a user to manipulate the query directly
    //if(!xarVarFetch('selection', 'str', $selection, '', XARVAR_DONT_SET)) {return;}
    if (!isset($selection)) {$selection = '';}

    $data['items'] = array();

    // Specify some labels for display
    $data['pager'] = '';

    // Security Check
    if (!xarSecurityCheck('ReadRole')) return;

    $q = new xarQuery();
    if ($letter) {
        if ($letter == 'Other') {
            // TODO: check for syntax in other databases or use a different matching method.
            $q->regexp('xar_name','^[^A-Z]');
            $data['msg'] = xarML(
                'Members whose Display Name begins with character not listed in alphabet above (labeled as "Other")'
            );
        } else {
        // TODO: handle case-sensitive databases
            $q->like('xar_name',$letter.'%');
            $data['msg'] = xarML('Members whose Display Name begins with "#(1)"', $letter);
        }
    } elseif ($search) {
        // Quote the search string
        $qsearch = '%'.$search.'%';

        $cond[1] = $q->like('xar_name', $qsearch);
        $cond[2] = $q->like('xar_uname', $qsearch);
        if (xarModGetVar('roles', 'searchbyemail')) {
            $cond[3] = $q->like('xar_email', $qsearch);
            $data['msg'] = xarML('Members whose Display Name or User Name or Email Address contains "#(1)"', $search);
        } else {
            $data['msg'] = xarML('Members whose Display Name or User Name "#(1)"', $search);
        }
        $q->qor($cond);
    } else {
        $data['msg'] = xarML("All members");
    }

    $data['order'] = $order;
    $data['letter'] = $letter;
    $data['search'] = $search;
    $data['searchlabel'] = xarML('Go');

    $data['alphabet'] = array(
        'A', 'B', 'C', 'D', 'E', 'F',
        'G', 'H', 'I', 'J', 'K', 'L',
        'M', 'N', 'O', 'P', 'Q', 'R',
        'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z'
    );

    $filter['startnum'] = $startnum;

    switch(strtolower($phase)) {
        case 'active':
            $data['phase'] = 'active';
            $filter = time() - (xarConfigGetVar('Site.Session.Duration') * 60);
            $data['title'] = xarML('Online Members');
            xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Active Members')));

            // Get the records to be displayed
            $queryresult = xarModAPIFunc(
                'roles', 'user', 'getallactive',
                array(
                    'startnum' => $startnum,
                    'filter'   => $filter,
                    'order'   => $order,
                    'selection'   => $q,
                    'include_anonymous' => false,
                    'include_myself' => false,
                    'numitems' => xarModGetVar('roles', 'rolesperpage')
                )
            );
            $data['message'] = xarML('There are no online members selected');
            break;

        case 'viewall':
            $data['phase'] = 'viewall';
            $data['title'] = xarML('All Members');

            xarTplSetPageTitle(xarML('All Members'));

            // Now get the actual records to be displayed
            $queryresult = xarModAPIFunc(
                'roles', 'user', 'getall',
                array(
                    'startnum' => $startnum,
                    'order' => $order,
                    'selection' => $q,
                    'include_anonymous' => false,
                    'include_myself' => false,
                    'numitems' => xarModGetVar('roles', 'rolesperpage')
                )
            );
            $data['message'] = xarML('There are no members selected');
            break;
    }

    // display the query
    $queryresult->qecho();

    $totalitems = $queryresult->getrows();
    $data['total'] = $totalitems;
    if ($totalitems == 0) {
        return $data;
    }

    $items = $queryresult->output();
    // keep track of the selected uid's
    $data['uidlist'] = array();

    // Check individual privileges for Edit / Delete
    for ($i = 0, $max = count($items); $i < $max; $i++) {
        $item = $items[$i];
        $data['uidlist'][] = $item['uid'];

        // Change email to a human readible entry.  Anti-Spam protection.

        if (xarUserIsLoggedIn()) {
            $items[$i]['emailurl'] = xarModURL(
                'roles', 'user', 'email',
                array('uid' => $item['uid'])
            );
        } else {
            $items[$i]['emailurl'] = '';
        }

        if (empty($items[$i]['ipaddr'])) {
            $items[$i]['ipaddr'] = '';
        }
        $items[$i]['emailicon'] = xarTplGetImage('emailicon.gif');
        $items[$i]['infoicon'] = xarTplGetImage('infoicon.gif');
    }
    $data['pmicon'] = '';
    // Add the array of items to the template variables
    $data['items'] = $items;
    $numitems = xarModGetVar('roles', 'rolesperpage');
    $pagerfilter['phase'] = $phase;
    $pagerfilter['order'] = $order;
    $pagerfilter['letter'] = $letter;
    $pagerfilter['search'] = $search;
    $pagerfilter['startnum'] = '%%';

    $data['pager'] = xarTplGetPager(
        $startnum,
        $data['total'],
        xarModURL('roles', 'user', 'view', $pagerfilter),
        $numitems
    );
    return $data;
}


<?php
/**
 * View the current mail queue
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */
/**
 * View the current mail queue (if any)
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @access  public
 * @param   array $args Argument array when calling this function from another function
 * @param   string action The action to perform
 * @return  bool true on success or void on failure
 * @throws  no exceptions
 * @todo    nothing
*/
function mail_admin_viewq($args)
{
    extract($args);
    if (!xarVarFetch('action','str', $action, '')) return;

    if (!xarSecurityCheck('AdminMail')) return;

    $data = array();
    if (!empty($action)) {
        // Confirm authorisation code
        if (!xarSecConfirmAuthKey()) return;

        switch ($action)
        {
            case 'process':
                $data['log'] = xarMod::apiFunc('mail','scheduler','sendmail');
                if (!isset($data['log'])) return;
                break;

            case 'view':
                if (!xarVarFetch('id','str', $id, '')) return;
                if (!empty($id)) {
                    // retrieve the mail data
                    $maildata = xarModGetVar('mail',$id);
                    if (!empty($maildata)) {
                        $data['id'] = $id;
                        $data['mail'] = unserialize($maildata);
                    }
                }
                break;

            case 'delete':
                if (!xarVarFetch('id','str', $id, '')) return;
                if (!empty($id)) {
                    // get the waiting queue
                    $serialqueue = xarModGetVar('mail','queue');
                    if (!empty($serialqueue)) {
                        $queue = unserialize($serialqueue);
                    } else {
                        $queue = array();
                    }
                    // delete the mail data
                    xarModDelVar('mail',$id);
                    // remove the selected mail from the queue
                    if (isset($queue[$id])) {
                        unset($queue[$id]);
                    }
                    // update the waiting queue
                    $serialqueue = serialize($queue);
                    xarModSetVar('mail','queue',$serialqueue);

                    xarResponseRedirect(xarModURL('mail', 'admin', 'viewq'));
                    return true;
                }
                break;

            default:
                break;
        }
    }

// TODO: use separate xar_mail_queue table here someday
    // get the waiting queue
    $serialqueue = xarModGetVar('mail','queue');
    if (!empty($serialqueue)) {
        $queue = unserialize($serialqueue);
    } else {
        $queue = array();
    }
    // sort mail queue in ascending order of 'no earlier than' delivery
    asort($queue, SORT_NUMERIC);

    $data['items'] = $queue;
    // TODO: add a pager (once it exists in BL)
    $data['pager'] = '';
    $data['authid'] = xarSecGenAuthKey();
    // Set the page name
    xarTplSetPageTitle(xarVarPrepForDisplay(xarML('Manage mail que')));
    // return the template variables defined in this template
    return $data;

}

?>
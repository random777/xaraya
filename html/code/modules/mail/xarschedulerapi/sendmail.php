<?php
/**
 * Send queued/scheduled mails via Scheduler
 *
 * @package modules
 * @copyright (C) 2002-2006 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 * @link http://xaraya.com/index.php/release/771.html
 */
/**
 * send queued/scheduled mails (executed by the scheduler module)
 *
 * @author mikespub
 * @access public
 */
function mail_schedulerapi_sendmail($args)
{
    $log = xarML('Starting to send queued mail') . "\n";

// TODO: use separate xar_mail_queue table here someday
    // get the waiting queue
    $serialqueue = xarModVars::get('mail','queue');
    if (!empty($serialqueue)) {
        $queue = unserialize($serialqueue);
    } else {
        $queue = array();
    }
    $now = time();
    $sent = array();
    foreach ($queue as $id => $when) {
        // see if we need to send this mail already or not
        if ($when > $now) continue;

        $log .= xarML('Sending mail #(1)', $id) . ' ';
        // retrieve the mail data
        $data = xarModVars::get('mail',$id);
        if (empty($data)) {
            $log .= xarML('empty') . "\n";
            $sent[] = $id;
            continue;
        }
        $args = unserialize($data);
        unset($args['when']);
        // send it with the internal _sendmail API function
        if (xarMod::apiFunc('mail','admin','_sendmail',$args)) {
            $log .= xarML('succeeded');
            xarModVars::delete('mail',$id);
            $sent[] = $id;
        } else {
            $log .= xarML('failed');
        // CHECKME: do we try again later or not ? That should probably depend on the error ;)
            xarModVars::delete('mail',$id);
            $sent[] = $id;
        }
        $log .= "\n";
    }
    $log .= xarML('Finished sending queued mail');

    // we didn't send anything, so return now
    if (count($sent) == 0) {
        return $log;
    }

// Trick : make sure we're dealing with up-to-date information here,
//         because sending all those mails may have taken a while...
    xarVarDelCached('Mod.Variables.mail', 'queue');

    // get the current waiting queue
    $serialqueue = xarModVars::get('mail','queue');
    if (!empty($serialqueue)) {
        $queue = unserialize($serialqueue);
    } else {
        $queue = array();
    }
    // remove the sent mails from the queue
    foreach ($sent as $id) {
        if (isset($queue[$id])) {
            unset($queue[$id]);
        }
    }
    // update the waiting queue
    $serialqueue = serialize($queue);
    xarModVars::set('mail','queue',$serialqueue);

    return $log;
}

?>
<?php
/**
 * send html mail
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2005 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Mail System
 */

/**
 * This is a utility function that is called to send html mail
 * from any module regardless if the admin has configured html mail
 *
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param  $ 'info' is the email address we are sending (required)
 * @param  $ 'name' is the name of the email receipitent (optional)
 * @param  $ 'recipients' is an array of recipients (required) // NOTE: $info or $recipients is required, not both
 * @param  $ 'ccinfo' is the email address we are sending (optional)
 * @param  $ 'ccname' is the name of the email receipitent (optional)
 * @param  $ 'ccrecipients' is an array of cc recipients (optional)
 * @param  $ 'bccinfo' is the email address we are sending (required)
 * @param  $ 'bccname' is the name of the email receipitent (optional)
 * @param  $ 'bccrecipients' is an array of bcc recipients (optional)
 * @param  $ 'subject' is the subject of the email (required)
 * @param  $ 'message' is the body of the email (required)
 * @param  $ 'htmlmessage' is the html body of the email
 * @param  $ 'priority' is the priority of the message
 * @param  $ 'encoding' is the encoding of the message
 * @param  $ 'wordwrap' is the column width of the message
 * @param  $ 'from' is who the email is from
 * @param  $ 'fromname' is the name of the person the email is from
 * @param  $ 'attachName' is the name of an attachment to a message
 * @param  $ 'attachPath' is the path of the attachment
 * @param  $ 'usetemplates' set to true to use templates in xartemplates (default = true)
 * @param  $ 'when' timestamp specifying that this mail should be sent 'no earlier than' (default is now)
 *                  This requires installation and configuration of the scheduler module
 */
function mail_adminapi_sendhtmlmail($args)
{
    // Get arguments from argument array
    extract($args);

    // Check for required arguments
    $invalid = array();
    if (!isset($info) && !isset($recipients)) {
        $invalid[] = 'info/recipients';
    }
    if (!isset($subject)) {
        $invalid[] = 'subject';
    }
    if (!isset($message)) {
        $invalid[] = 'message';
    }

    if (count($invalid) > 0) {
        $msg = xarML('Wrong arguments to mail_adminapi', join(', ', $invalid), 'admin', 'sendhtmlmail', 'Mail');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return;
    }

    // Check info
    if (!isset($info)){
        $info = '';
    }
    // Check name
    if(!isset($name)) {
        $name='';
    }
    // Check recpipients
    if (!isset($recipients)) {
        $recipients = array();
    }
    // Check CC info/name
    if (!isset($ccinfo)) {
        $ccinfo = '';
    }
    if (!isset($ccname)) {
        $ccname = '';
    }
    if (!isset($ccrecipients)) {
        $ccrecipients = array();
    }
    // Check BCC info/name
    if (!isset($bccinfo)) {
        $bccinfo = '';
    }
    if (!isset($bccname)) {
        $bccname = '';
    }
    if (!isset($bccrecipients)) {
        $bccrecipients = array();
    }
    // Check from
    if (empty($from)) {
        $from = xarModGetVar('mail', 'adminmail');
    }
    // Check fromname
    if (empty($fromname)) {
        $fromname = xarModGetVar('mail', 'adminname');
    }
    // Check wordwrap
    if (!isset($wordwrap)) {
        $wordwrap = xarModGetVar('mail', 'wordwrap');
    }
    // Check priority
    if (!isset($priority)) {
        $priority = xarModGetVar('mail', 'priority');
    }
    // Check encoding
    if (!isset($encoding)) {
        $encoding = xarModGetVar('mail', 'encoding');
    }
    // Check if using mail templates - default is true
    if (!isset($usetemplates)) {
        $usetemplates = true;
    }

    $parsedmessage = '';

    // Check if a valid htmlmessage was sent
    if (!empty($htmlmessage)) {
        // Set the html version of the message

        // Check if headers/footers have been configured by the admin
        $htmlheadfoot = xarModGetVar('mail', 'htmluseheadfoot');

        $parsedmessage .= $htmlheadfoot ? xarModGetVar('mail', 'htmlheader') : '';
        $parsedmessage .= $htmlmessage;
        $parsedmessage .= $htmlheadfoot ? xarModGetVar('mail', 'htmlfooter') : '';

    } else {
        // If the module did not send us an html version of the
        // message ($htmlmessage),
        // then we have to play around with this one a bit by adding some <pre> tags

        // Check if headers/footers have been configured by the admin
        $textheadfoot = xarModGetVar('mail', 'textuseheadfoot');

        $parsedmessage .= '<pre>';
        $parsedmessage .= $textheadfoot ? xarModGetVar('mail', 'textheader') : '';
        $parsedmessage .= $message;
        $parsedmessage .= $textheadfoot ? xarModGetVar('mail', 'textfooter') : '';
        $parsedmessage .= '</pre>';

    }

    // Check if we want delayed delivery of this mail message
    if (!isset($when)) {
        $when = null;
    }

    if (!isset($attachName)) {
        $attachName = '';
    }
    if (!isset($attachPath)) {
        $attachPath = '';
    }
    // Call private sendmail
    return xarModAPIFunc('mail', 'admin', '_sendmail',
        array('info'          => $info,
              'name'          => $name,
              'recipients'    => $recipients,
              'ccinfo'        => $ccinfo,
              'ccname'        => $ccname,
              'ccrecipients'  => $ccrecipients,
              'bccinfo'       => $bccinfo,
              'bccname'       => $bccname,
              'bccrecipients' => $bccrecipients,
              'subject'       => $subject,
              'message'       => $message,
              'htmlmessage'   => $parsedmessage, // set to $parsedmessage
              'priority'      => $priority,
              'encoding'      => $encoding,
              'wordwrap'      => $wordwrap,
              'from'          => $from,
              'fromname'      => $fromname,
              'usetemplates'  => $usetemplates,
              'when'          => $when,
              'attachName'    => $attachName,
              'attachPath'    => $attachPath,
              'htmlmail'      => true));
}

?>
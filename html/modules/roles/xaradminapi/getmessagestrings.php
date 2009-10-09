<?php
/**
 * Get message
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['template'] name of the email type which has apair of -subject and -message files
 * @param $args['module'] module directory in var/messaging
 * @return array of strings of file contents read
 */
function roles_adminapi_getmessagestrings($args)
{
    extract($args);
    if (!isset($template)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_DATA', new SystemException('No template name was given.'));
        return;
    }

//FIXME: the default is always roles
    if(!isset($module)){
        list($module) = xarRequestGetInfo();
    }

    $messaginghome = xarCoreGetVarDirPath() . "/messaging/" . $module;
    $messagesubject = $messaginghome . "/" . $template . "-subject.xt";
    if (!file_exists($messagesubject)) {
        $messagesubject = $messaginghome . "/" . $template . "-subject.xd";
    }
    if (!file_exists($messagesubject)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The subject template was not found.'));
        return;
    }
    $string = '';
    $fd = fopen($messagesubject, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $subject = $string;
    fclose($fd);

    $messagemessage = $messaginghome . "/" . $template . "-message.xt";
    if (!file_exists($messagemessage)) {
        $messagemessage = $messaginghome . "/" . $template . "-message.xd";
    }
    if (!file_exists($messagemessage)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The message template was not found.'));
        return;
    }
    $string = '';
    $fd = fopen($messagemessage, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $message = $string;
    fclose($fd);

    return array('subject' => $subject, 'message' => $message);
}

?>

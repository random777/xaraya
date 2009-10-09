<?php
/**
 * Get message
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
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['template'] name of the email type which has apair of -subject and -message files
 * @param $args['module'] module directory in var/messaging
 * @return array of strings of file contents read
 */
function mail_adminapi_getmessagestrings($args)
{
    extract($args);
    if (!isset($template)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_DATA', new SystemException('No template name was given.'));
        return;
    }

    if(!isset($module)){
        list($module) = xarRequestGetInfo();
    }

    $messaginghome = xarCoreGetVarDirPath() . "/messaging/" . $module;
    $subjecttemplate = $messaginghome . "/" . $template . "-subject.xt";
    if (!file_exists($subjecttemplate)) {
        $subjecttemplate = $messaginghome . "/" . $template . "-subject.xd";
    }
    if (!file_exists($subjecttemplate)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The subject template was not found.'));
        return;
    }
    $string = '';
    $fd = fopen($subjecttemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $subject = $string;
    fclose($fd);

    $messagetemplate = $messaginghome . "/" . $template . "-message.xt";
    if (!file_exists($messagetemplate)) {
        $messagetemplate = $messaginghome . "/" . $template . "-message.xd";
    }        
    if (!file_exists($messagetemplate)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The message template was not found.'));
        return;
    }
    $string = '';
    $fd = fopen($messagetemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    $message = $string;
    fclose($fd);

    return array('subject' => $subject, 'message' => $message);
}

?>

<?php
/**
 * Return message
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
 * @author  John Cox <niceguyeddie@xaraya.com>
 * @param $args['template'] name of the template without .xt/.xd extension
 * @param $args['module'] module directory in var/messaging
 * @return string of file contents read
 */
function mail_adminapi_getmessageincludestring($args)
{
    extract($args);
    if (!isset($template)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_DATA', new SystemException('No template name was given.'));
    }

    if(!isset($module)){
        list($module) = xarRequestGetInfo();
    }

    // Get the template that defines the substitution vars
    $messaginghome = xarCoreGetVarDirPath() . "/messaging/" . $module;
    $messagetemplate = $messaginghome . "/includes/" . $template . ".xt";
    if (!file_exists($messagetemplate)) {
        $messagetemplate = $messaginghome . "/includes/" . $template . ".xd";
    }
    if (!file_exists($messagetemplate)) {
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'MODULE_FILE_NOT_EXIST', new SystemException('The variables template was not found.'));
    }
    $string = '';
    $fd = fopen($messagetemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    fclose($fd);
    return $string;
}

?>

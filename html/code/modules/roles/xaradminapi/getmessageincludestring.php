<?php
/**
 * Get message include string
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Roles module
 * @link http://xaraya.com/index.php/release/27.html
 */
/**
 *
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 * @param $args['template'] name of the template without .xt extension
 * @param $args['module'] module directory in var/messaging
 * @return string of file contents read
 */
function roles_adminapi_getmessageincludestring($args)
{
    extract($args);
    if (!isset($template)) throw new EmptyParameterException('template');

    if(!isset($module)){
        list($module) = xarRequest::getInfo();
    }

// Get the template that defines the substitution vars
    $messaginghome = sys::varpath() . "/messaging/" . $module;
    $msgtemplate = $messaginghome . "/includes/" . $template . ".xt";
    if (!file_exists($msgtemplate)) throw new FileNotFoundException($msgtemplate);

    $string = '';
    $fd = fopen($msgtemplate, 'r');
    while(!feof($fd)) {
        $line = fgets($fd, 1024);
        $string .= $line;
    }
    fclose($fd);
    return $string;
}

?>
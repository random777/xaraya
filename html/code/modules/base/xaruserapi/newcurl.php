<?php
/**
 * Return a newCurl object
 *
 * @package modules
 * @copyright see the html/credits.html file in this release
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Return a new xarCurl object.
 * $args are passed directly to the class.
 */
function base_userapi_newcurl($args)
{
    sys::import('modules.base.class.xarCurl');
    return new xarCurl($args);
}

?>

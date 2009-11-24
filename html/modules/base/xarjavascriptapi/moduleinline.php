<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */

/**
 * Base JavaScript management functions
 * Include a section of inline JavaScript code in a page.
 * Used when a module needs to generate custom JS on-the-fly,
 * such as "var lang_msg = xarML('error - aborted');"
 *
 * @author Jason Judge
 * @param string $args['position']  Position on the page; generally 'head' or 'body'
 * @param string $args['code']      The JavaScript code fragment
 * @param int $args['index']        Optional index in the JS array; unique identifier
 * @return bool true=success; null=fail
 */
function base_javascriptapi_moduleinline($args)
{
    extract($args);

    if (empty($code)) {
        return;
    }

    // Use a hash index to prevent the same JS code fragment
    // from being included more than once.
    if (empty($index)) {
        $index = md5($code);
    }

    // Default the position to the head.
    if (empty($position)) {
        $position = 'head';
    }

    return xarTplAddJavaScript($position, 'code', $code, $index);
}

?>

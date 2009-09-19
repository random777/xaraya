<?php
/**
 * Base JavaScript management functions
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base module
 * @link http://xaraya.com/index.php/release/68.html
 */
/**
 * Add code to a framework event handler
 * @author Marty Vance
 * @param string $args['name']         Name of the event (required)
 * @param string $args['file']         Filename containing code (required), with
 * @param string $args['filepath']     Path to the given file; or
 * @param string $args['code']         String containing code (required)
 * @return bool
 */
function base_jqueryapi_appendframeworkevent($args)
{
    extract($args);

    // This function should not normally be called directly; it gets called
    // by base_javascriptapi_appendframeworkevent, which does the important
    // work.  Use this function to perform additional tasks for appending
    // code to a framework's event hander.

    return '';
}

?>

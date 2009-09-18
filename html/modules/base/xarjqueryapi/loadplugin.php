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
 * Load a JS framework plugin
 * @author Marty Vance
 * @param $args['name']         name of the plugin
 * @param $args['file']         file name to load
 * @param $args['filepath']     path to the file
 * @return bool
 */
function base_jqueryapi_loadplugin($args)
{
    extract($args);

    // This function should not normally be called directly; it gets called
    // by base_javascriptapi_loadplugin, which does the important work.      
    // Use this function to perform additional tasks for loading a plugin

    return true;
}

?>

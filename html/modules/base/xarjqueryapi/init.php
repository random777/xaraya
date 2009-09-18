<?php
/**
 * Base jQuery management functions
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
 * Inititalize jQuery framework
 * @author Marty Vance
 * @param $args['file'] string filename for the framework JS
 * @param $args['filepath'] path to the file
 * @return bool
 */
function base_jqueryapi_init($args)
{
    extract($args);

    // This function should not normally be called directly; it gets called
    // by base_javascriptapi_init, which does the important work. 
    // Use this function to perform additional init tasks

    return true;
}

?>

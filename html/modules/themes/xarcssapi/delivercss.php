<?php
/**
 * Handle additional styles tag
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */

/**
 * Format : <xar:additional-styles /> without params
 * Used in the head section to deliver the styles which were set 
 * with <xar:style ...> in module and block templates.
 *
 * @author Andy Varganov
 * @param none
 * @return string
 */
function themes_cssapi_delivercss($args)
{
    $args['method'] = 'render';
    $args['base'] = 'theme';

    $argstring = 'array(';
    foreach ($args as $key => $value) {
        $argstring .= "'" . $key . "' => '" . $value . "',";
    }
    $argstring .= ")";
    return "echo xarModAPIFunc('themes', 'user', 'deliver',$argstring);\n";
}

?>
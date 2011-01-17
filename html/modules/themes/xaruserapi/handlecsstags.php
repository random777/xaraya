<?php
/**
 * Handle css tags
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Themes module
 * @link http://xaraya.com/index.php/release/70.html
 */
/**
 * Deal with the actual tag
 *
 * @author andyv <andyv@xaraya.com>
 */
function themes_userapi_handlecsstags($args)
{
    $argstring = 'array(';
    foreach ($args as $key => $value) {
        $argstring .= "'" . $key . "' => '" . $value . "',";
    }
        $argstring .= ")";
    if (isset($args['method']) && $args['method'] == 'render') {
        return "echo xarMod::apiFunc('themes', 'user', 'deliver',$argstring);\n";
    } else {
        return "xarMod::apiFunc('themes', 'user', 'register',$argstring);\n";
    }
}
?>
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
 * Handle <xar:base-include-javascript ...> form field tags
 * Format : <xar:base-include-javascript definition="$definition" /> with $definition an array
 *       or <xar:base-include-javascript filename="thisname.js" module="modulename" position="head|body" />
 *               Default modulename is the module in which the tag is called.
 *               Default position is 'head'
 *               filename is mandatory if type is not given.
 *       or <xar:base-include-javascript type="code" code="thissource" position="head|body"/>
 *
 * Example:
 * The following tag is included in an 'articles' template. The file 'myfile.js'
 * can be located in either themes/<current>/modules/articles/includes or
 * modules/articles/xartemplates/includes:
 *
 *    <xar:base-include-javascript filename="myfile.js"/>
 *
 * @author Jason Judge
 * @param array $args['definition']     Form field definition or the type, position, ...
 * @param string $args['code']          String containing JS code
 * @param string $args['filename']      Name of the js file
 * @param string $args['module']        Name of module containing the file
 * @param string $args['position']      Position to place the js (default= 'head')
 * @return string code to generate for this tag
 */
function base_javascriptapi_handlemodulejavascript($args)
{

    // The whole lot can be passed in as an array.
    if (isset($args['definition']) && is_array($args['definition'])) {
        // merge definition into arguments
        foreach ($args['definition'] as $dkey => $dval) {
            $args[$dkey] = $dval;
        }
    }
    extract($args);

    if (!empty($code) && !empty($type)) {
        // If the 'code' attribute has been passed in, then some inline code
        // has been supplied - we don't need to read anything from a file then.
        return "xarTplAddJavaScript('$position', '$type', \"$code\");";
    }

    if (empty($filename)) return '';

    // Return the code to call up the javascript file.
    // Only the file version is supported for now.
    // let modulefile handle the arguments...
    $out = "xarModAPIFunc('base', 'javascript', 'modulefile',\n";
    $out .= " array(\n";
    foreach ($args as $key => $val) {
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $out .= " '$key' => $val,\n";
        } else {
            $out .= " '$key' => '$val',\n";
        }
    }
    $out .= "));";

    return $out;
}

?>

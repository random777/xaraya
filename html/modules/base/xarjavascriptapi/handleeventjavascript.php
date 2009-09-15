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
 * Handle render javascript form field tags
 * Handle <xar:base-trigger-javascript ...> form field tags
 * Format : <xar:base-render-javascript definition="$definition" /> with $definition an array
 *       or <xar:base-render-javascript position="head|body|whatever|" type="code|src|whatever|"/>
 * Default position is ''; default type is ''.
 * Typical use in the head section is: <xar:base-render-javascript position="head"/>
 *
 * @author Jason Judge
 * @param array $args['definition']     Form field definition or the type, position, ...
 * @param string $args['position']      Name or ID of the tag ('body', 'mytag', etc.)
 * @param string $args['type']          Type of event ('onload', 'onmouseup', etc.)
 * @return string empty string
 */
function base_javascriptapi_handleeventjavascript($args)
{
    extract($args);

    // The whole lot can be passed in as an array.
    if (isset($definition) && is_array($definition)) {
        extract($definition);
    }

    // Position and type are mandatory.
    if (empty($position)) {
        $position = '';
    } else {
        $position = addslashes($position);
    }
    if (empty($type)) {
        $type = '';
    } else {
        $type = addslashes($type);
    }

    // Concatenate the JavaScript trigger code fragments.
    // Only pick up the event type JavaScript.

    return "
        echo htmlspecialchars(xarModAPIfunc('base', 'javascript', 'geteventjs', array('position'=>'$position', 'type'=>'$type')));
    ";
}

?>

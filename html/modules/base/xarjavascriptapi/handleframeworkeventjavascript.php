<?php
/**
 * Base Module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Base Module
 * @link http://xaraya.com/index.php/release/68.html
 * @author crisp <crisp@crispcreations.co.uk>
 */
 /**
 * Handle base-js-event template tag
 *
 * @author crisp <crisp@crispcreations.co.uk>
 * @return string
 */
function base_javascriptapi_handleframeworkeventjavascript($args)
{
    // FIXME: MrB Does the wrapping of xarModAPILoad have any consequences for this?
    $out = "xarModAPILoad('base','javascript');
        echo xarModAPIFunc('base',
                   'javascript',
                   'appendframeworkevent',
                   array(\n";
    foreach ($args as $key => $val) {
        if (is_numeric($val) || substr($val,0,1) == '$') {
            $out .= "                         '$key' => $val,\n";
        } else {
            $out .= "                         '$key' => '$val',\n";
        }
    }
    $out .= "                         ));";
    return $out;

}
?>
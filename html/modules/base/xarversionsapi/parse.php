<?php
/**
 * Base User Version management functions
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
 * Parse a version number into an associative array of components
 *
 * @author Marty Vance
 * @param $args['ver'] string version number to parse
 * @return array on success or false on faliure
 */
function base_versionsapi_parse($args)
{
    extract($args);

    if(!isset($ver)) {
        // The version is missing
        $msg = xarML('The version number was not provided.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    if(!is_string($ver)) {
        // The version is not a string
        $msg = xarML('The version number is not a string.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    $regex = '/^([1-9]\d*|0)\.([1-9]\d*|0)\.([1-9]\d*|0)(-(a|b|rc)([1-9]\d*))?$/';

    preg_match($regex, $ver, $matches);

    if(count($matches) != 7 && count($matches) != 4) {
        // The version is not valid
        $msg = xarML('The version number #(1) is not valid.', $ver);
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }
    
    /* Return format is an assocative array naming each component:
     * major: integer
     * minor: integer
     * micro: integer
     * suffix: string, one of 'a' (alpha), 'b' (beta), or 'rc' (release candidate) (optional)
     * rev: integer (> 1, required only if suffix is present)
     */

    $version = array('major' => (int) $matches[1], 'minor' => (int) $matches[2], 'micro' => (int) $matches[3]);

    if(count($matches) == 7) {
        $version['suffix'] = $matches[5];
        $version['rev'] = (int) $matches[6];
    }

    return $version;
}

?>

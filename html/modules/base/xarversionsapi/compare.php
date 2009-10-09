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
 * Base User version management functions
 *
 * Compare two legal-style versions supplied as strings or arrays
 * Usage : $which = xarModAPIFunc('base', 'versions', 'compare', array('ver1'=>$version1, 'ver2'=>$version2));
 * or shortcut $which = xarModAPIFunc('base', 'versions', 'compare', array($version1, $version2));
 *
 * @author Jason Judge
 * @param $args['ver1'] or $args[0] version number 1 (string or array)
 * @param $args['ver2'] or $args[1] version number 2 (string or array)
 * @param $args['strict'] whether to consider suffix and revision (default: true)
 * @return numeric number indicating which version number is the latest
 */
function base_versionsapi_compare($args)
{
    // Indicates which is the latest version: -1, +1 or 0 (neither).
    // Versions can be strings ('1.2.3') or arrays (see base/xarversionsapi/parse.php).
    // See test script for examples: tests/base/version_compare.php

    // Extract the arguments. Prefix unnamed parameters with 'p_'.
    extract($args, EXTR_PREFIX_INVALID, 'p');

    // Set the order parameter to be an array.
    // The order is optional and allows complex ordering to be achieved.
    // Examples:
    // $order =  1 - standard ordering
    // $order = -1 - reverse ordering
    // $order = array(-1,1) - reverse order the first level and normal order remaining levels
    // zero will allow any order for a level.
    if (isset($order) && !is_array($order)) {
        $order = array($order);
    }

    if(!isset($strict) || (bool) $strict != false) {
        $strict = true;
    }

    // Default the version numbers to either a positional
    // parameter value or to '0' if nothing passed in at all.
    if (!isset($ver1)) {
        $ver1 = (isset($p_0) ? $p_0 : NULL);
    }

    if (!isset($ver2)) {
        $ver2 = (isset($p_1) ? $p_1 : NULL);
    }

    if($ver1 == NULL) {
        // The first version number is missing
        $msg = xarML('The first version number is missing');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    if($ver2 == NULL) {
        // The second version number is missing
        $msg = xarML('The second version number is missing');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    $versions = array('ver1' => $ver1, 'ver2' => $ver2);

    // ensure each version number is parsed
    foreach ($versions as $v => $version) {
        $result = null;
        if (is_string($version)) {
            $func = 'parse';
        } elseif (is_array($version)) {
            $func = 'unparse';
        } else {
            if ($v == 'ver1') {
                $msg = xarML('The first version number is neither a string nor an array.');
            } else {
                $msg = xarML('The second version number is neither a string nor an array.');
            }
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return false;
        }

        $result = xarModAPIFunc('base','versions',$func, array('ver' => $version));

        if (!$result) {
            // throw a more meaningful exception from here
            xarErrorHandled();

            if ($v == 'ver1') {
                $msg = xarML('The first version number is invalid.');
            } else {
                $msg = xarML('The second version number is invalid.');
            }
            xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
            return false;

            return false;
        } else {
            if (is_string($version)) {
                $versions[$v] = $result;
            } else {
                $versions[$v] = $version;
            }
        }
    }

    extract($versions);

    $components = array('major','minor','micro','suffix','rev');

    // Default return if no differences are found.
    $latest = null;

    // set up bool flags for use later
    $same_major = ($ver2['major'] == $ver1['major']);
    $same_minor = ($ver2['minor'] == $ver1['minor']);
    $same_micro = ($ver2['micro'] == $ver1['micro']);
    $same_digits = ($same_major && $same_minor && $same_micro);

    $add_extra = ((!isset($ver1['suffix']) && !isset($ver1['rev'])) && (isset($ver2['suffix']) && isset($ver2['rev'])));
    $remove_extra = ((isset($ver1['suffix']) && isset($ver1['rev'])) && (!isset($ver2['suffix']) && !isset($ver2['rev'])));
    $with_extra = (isset($ver1['suffix']) && isset($ver1['rev']) && isset($ver2['suffix']) && isset($ver2['rev']));
    $no_extra = (!isset($ver1['suffix']) && !isset($ver1['rev']) && !isset($ver2['suffix']) && !isset($ver2['rev']));
    $change_extra = ($with_extra && ($ver2['suffix'] != $ver1['suffix'] || $ver2['rev'] != $ver1['suffix']));
    $same_suffix = ($with_extra && $ver1['suffix'] == $ver2['suffix']);
    $same_rev = ($with_extra && $ver1['rev'] == $ver2['rev']);
    $same_extra = ($with_extra && $same_suffix && $same_rev);

    // identical checks
    if (($strict && $same_extra) && $same_digits) { 
        $latest = 0;
    }
    if (($strict && $no_extra) && $same_digits) { 
        $latest = 0;
    }
    if (($strict && $add_extra) && $same_digits) { 
        $latest = -1;
    }
    if (($strict && $remove_extra) && $same_digits) { 
        $latest = 1;
    }

    if ($latest === null) {
        foreach ($components as $level) {
            if(isset($ver1[$level]) && isset($ver2[$level])) {
                if ($ver1[$level] == $ver2[$level]) {
                    $latest = 0;
                } elseif ($ver1[$level] > $ver2[$level]) {
                    $latest = -1;
                    break;
                } else {
                    $latest = 1;
                    break;
                }
            }
            if ($level == 'micro' && !$strict) {
                break;
            }
        }
    }

    if ($latest === null) {
        $msg = xarML('Unknown error in version compare');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    return $latest;
}

?>

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
 * Reassemble a parsed version number back into a string
 *
 * @author Marty Vance
 * @param $args['ver'] array parsed version number to reconstruct
 * @return string on success, false on failure
 */
function base_versionsapi_unparse($args)
{
    extract($args);

    if (!isset($ver)) {
        // The version is missing
        $msg = xarML('The version number was not provided.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    if (!is_array($ver)) {
        // The version is not an array
        $msg = xarML('The version number is not a string.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    /* $ver should contain the following indexes:
     * major: integer
     * minor: integer
     * micro: integer
     * suffix: string, one of 'a' (alpha), 'b' (beta), or 'rc' (release candidate) (lowercase; optional)
     * rev: integer (required only if suffix is present)
     */
    extract($ver);

    if (!isset($major) ||
        !isset($minor) ||
        !isset($micro) ||
        (isset($suffix) && !isset($rev))
    ) {
        // Missing component
        $msg = xarML('A component is missing from the version number.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    if (!is_int($major) ||
        !is_int($minor) ||
        !is_int($micro) ||
        (isset($suffix) && (!in_array($suffix, array('a','b','rc')) || !is_int($rev)))
    ) {
        // Bad component
        $msg = xarML('A version number component is invalid.');
        xarErrorSet(XAR_SYSTEM_EXCEPTION, 'BAD_PARAM', new SystemException($msg));
        return false;
    }

    $version = (int)$major . '.' . (int)$minor . '.' . (int)$micro;

    if (isset($suffix)) {
        $version .= '-' . $suffix . (int)$rev;
    }


    return $version;
}

?>

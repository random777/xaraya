<?php
/**
 * Stub file_get_contents
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage PHP Version Compatibility Library
 * @author Jason Judge
 */

/**
 * Stub for the fnmatch() function
 *
 * @see _fnmatch()
 */

function fnmatch($pattern, $string)
{
    require_once dirname(__FILE__) . '/functions/_fnmatch.php';
    return _fnmatch($pattern, $string);
}

?>
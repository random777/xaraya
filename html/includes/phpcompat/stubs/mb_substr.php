<?php
/**
 * Stub mb_string
 *
 * @copyright (C) 2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage PHP Version Compatibility Library
 * @author Jo Dalle Nogare
 */
 
/**
 * Stub for mb_substr() function
 *
 * @see _mb_substr()
 */
function mb_substr($str, $start, $len = '', $encoding='UTF-8')
{
    require_once dirname(__FILE__) . '/functions/_mb_substr.php';
    return _mb_substr($str, $start, $len, $encoding);
}
?>
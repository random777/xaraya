<?php
/**
 * Stub html_entity_decode
 *
 * @package core
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage PHP Version Compatibility Library
 * @author Jo Dalle Nogare
 */

/**
 * Stub for the html_entity_decode() function
 *
 * @see _html_entity_decode()
 * @internal quote_style not supported and defaults to ENT_COMPAT
 */
function html_entity_decode($string)
{
    require_once dirname(__FILE__) . '/functions/_html_entity_decode.php';
    return _html_entity_decode($string);
}

?>

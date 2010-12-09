<?php
/**
 *
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

if (!class_exists('ExceptionRendering')) {
    sys::import('exceptions.exceptionrendering');
}

/**
 * Class for rendering exceptions as plain text
 *
 * @package exceptions
 */
class TextExceptionRendering extends ExceptionRendering
{
    var $linebreak = "\n";
    var $openstrong = "";
    var $closestrong = "";
    var $openpre = "";
    var $closepre = "";
}

?>

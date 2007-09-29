<?php
/**
 * Exception Handling System
 *
 * @package exceptions
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

include_once dirname(__FILE__)."/exception.class.php";

/**
 * Class for exception type NoException
 *
 * @package exceptions
 */
class NoException extends xarException
{
    function NoException()
    {
        $this->major = XAR_NO_EXCEPTION;
        $this->id = "NoException initialized";
        $this->title = "No Exception";
    }
}

?>

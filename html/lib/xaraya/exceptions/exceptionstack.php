<?php
/**
 * Error Stack class
 *
 * @package exceptions
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 * @author Marc Lutolf <marcinmilan@xaraya.com>
 */

/**
 * Class for handling exceptions as a stack
 *
 * @package exceptions
 */
class xarExceptionStack
{
    var $stack;

    function xarExceptionStack()
    {}

    function isempty()
    {
        return count($this->stack) == 0;
    }

    function size()
    {
        return count($this->stack);
    }

    function peek()
    {
        return $this->stack[count($this->stack)-1];
    }

    function pop()
    {
        $obj = $this->stack[count($this->stack)-1];
        array_pop($this->stack);
        return $obj;
    }

    function push($obj)
    {
        $this->stack[] = $obj;
    }

    function initialize()
    {
        $this->stack = array(new NoException());
    }
}

?>

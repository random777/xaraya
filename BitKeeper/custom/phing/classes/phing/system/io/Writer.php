<?php
/*
 * $Id: Writer.php,v 1.5 2003/02/24 18:22:16 openface Exp $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://binarycloud.com/phing/>. 
 */

import("phing.system.lang.RuntimeException");
import("phing.system.io.File");
/**
 * Abstract class for reading character streams. 
 *  @package   phing.system.io
 */


class Writer {
    var	$_out = null;

    function Writer(&$out) {
        $this->_out = $out;
    }

    function write($cbuf = null, $off = null, $len = null) {
        return $this->_out->write($cbuf, $off, $len);
    }

    function mark() {}

    function reset()	{
        return $this->_out->reset();
    }
    function close()	{
        return $this->_out->close();
    }
    function open()		{
        return $this->_out->open();
    }
    function ready() {}

    function markSupported() {}
}
?>

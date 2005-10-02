<?php
/*
 * $Id: StringReader.php,v 1.5 2003/02/24 18:22:16 openface Exp $
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

// This class should be named DummyReader
// But i prefere let the Ant naming.
class StringReader extends Reader {
    var	$_string;

    function StringReader(&$string) {
        $this->setReader($this);
        $this->_string = $string;
    }

    function skip($n) {}

    function read($cbuf = null, $off = null, $len = null) {}

    function mark() {}

    function reset() {}

    function close() {}

    function open() {}

    function ready() {}

    function markSupported() {}
}
?>

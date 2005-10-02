<?php
/*
 * $Id: Reader.php,v 1.4 2003/02/24 18:22:16 openface Exp $
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

/*
 * Abstract class for reading character streams.
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @version   $Revision: 1.4 $ $Date: 2003/02/24 18:22:16 $
 * @access    public
*  @package   phing.system.io
*/
class Reader {
    var	$in = null;

    function Reader() {}

    function setReader(&$in) {
        $this->in = $in;
    }

    function skip($n)	{
        return $this->in->skip($n);
    }
    function read($cbuf = null, $off = null, $len = null) {
        return $this->in->read($cbuf, $off, $len);
    }

    function mark() {}

    function reset()	{
        return $this->in->reset();
    }
    function close()	{
        return $this->in->close();
    }
    function open()		{
        return $this->in->open();
    }
    function ready() {}

    function markSupported() {}
}
?>

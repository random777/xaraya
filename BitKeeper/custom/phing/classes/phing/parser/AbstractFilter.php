<?php
/*
 * $Id: AbstractFilter.php,v 1.5 2003/04/09 15:58:10 thyrell Exp $
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

import("phing.parser.ExpatParseException");

/**
 * This is an abstract class all SAX handler classes must extend
 *
 * @author    Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.5 $ $Date: 2003/04/09 15:58:10 $
 * @access    public
 * @package   phing.parser
 */

class AbstractFilter {

    var $parentHandler = null;
    var $parser = null;

    /**
     * Constructs a SAX handler called filter.
     *
     * The constructor must be called by all derived classes.
     *
     * @param   object  the parser object
     * @param   object  the parent handler of this handler
     * @access  public
     */
    function AbstractFilter(&$parser, &$parentHandler) {
        $this->parentHandler =& $parentHandler;
        $this->parser =& $parser;
        $this->parser->setHandler($this);
    }

    /**
     * Gets invoked when a XML open tag occurs
     *
     * Must be overloaded by the child class. Throws an ExpatParseException
     * if there is no handler registered for an element.
     *
     * @param  string  the name of the XML element
     * @param  array   the attributes of the XML element
     * @access public
     */
    function startElement($name, $attribs) {
        $exc = new ExpatParseException("Unexpected element $name");
        throw ($exc);
        return $exc;
    }

    /**
     * Gets invoked when element closes method.
     *
     * @access  private
     */
    function _finished() {}

    /**
     * Gets invoked when a XML element ends.
     *
     * Can be overloaded by the child class. But should not. It hands
     * over control to the parentHandler of this.
     *
     * @param  string  the name of the XML element
     * @access public
     */
    function endElement($name) {
        $this->_finished();
        $this->parser->setHandler($this->parentHandler);
    }

    /**
     * Invoked by occurance of #PCDATA.
     *
     * @param     string  the name of the XML element
     * @exception ExpatParserException if there is no CDATA but method
     *            was called
     * @access    public
     */
    function characters($data) {
        $s = trim($data);
        if (strlen($s) > 0) {
            throw (new ExpatParseException("Unexpected text $s"));
            return;
        }
    }
}
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

<?php
/*
 * $Id: AbstractSAXParser.php,v 1.5 2003/04/09 15:58:10 thyrell Exp $
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

/**
 * The abstract SAX parser class.
 *
 * This class represents a SAX parser. It is a abstract calss that must be
 * implemented by the real parser that must extend this class
 *
 * @author    Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.5 $ $Date: 2003/04/09 15:58:10 $
 * @access    public
 * @package   phing.parser
 */

class AbstractSAXParser {

    var $handler = null;

    /**
     * Constructs a SAX parser
     */
    function AbstractSAXParser() {}

    /**
     * Sets options for PHP interal parser. Must be implemented by the parser
     * class if it should be used.
     */
    function parserSetOption($opt,$val) {}

    /**
     * Sets the current element handler object for this parser. Usually this
     * is an object using extending "AbstractFilter".
     *
     * @param   object  the handler object
     * @access  public
     */
    function setHandler(&$obj) {
        $this->handler=&$obj;
        // uncomment for debug
        //System::println("[PARSER-DEBUG] Setted SAX handler: ". get_class($obj));
    }

    /**
     * Method that gets invoked when the parser runs over a XML start element.
     *
     * This method is called by PHP's internal parser funcitons and registered
     * in the actual parser implementation.
     * It gives control to the current active handler object by calling the
     * <code>startElement()</code> method.
     *
     * @param  object  the php's internal parser handle
     * @param  string  the open tag name
     * @param  array   the tag's attributes if any
     * @throws Exception if something gone wrong. Catches any exception
     *         and re-throws it.
     * @access public
     */
    function startElement($parser, $name, $attribs) {
        $this->handler->startElement($name,$attribs);
        if (catch("Exception", $exc)) {
            throw($exc);
            return;
        }
    }

    /**
     * Method that gets invoked when the parser runs over a XML close element.
     *
     * This method is called by PHP's internal parser funcitons and registered
     * in the actual parser implementation.
     *
     * It gives control to the current active handler object by calling the
     * <code>endElement()</code> method.
     *
     * @param   object  the php's internal parser handle
     * @param   string  the closing tag name
     * @throws  Exception if something gone wrong. Catches any exception
     *          and re-throws it.
     * @access  public
     */
    function endElement($parser, $name) {
        $this->handler->endElement($name);
        if (catch("Exception", $exc)) {
            throw($exc);
            return;
        }
    }

    /**
     * Method that gets invoked when the parser runs over CDATA.
     *
     * This method is called by PHP's internal parser functions and registered
     * in the actual parser implementation.
     *
     * It gives control to the current active handler object by calling the
     * <code>characters()</code> method. That processes the given CDATA.
     *
     * @param   object  the php's internal parser handle
     * @param   string  the CDATA
     * @throws  Exception if something gone wrong. Catches any exception
     *          and re-throws it.
     * @access  public
     */
    function characters($parser, $data) {
        $this->handler->characters($data);
        if (catch("Exception", $exc)) {
            throw($exc);
            return;
        }
    }

    /**
     * Entrypoint for parser. This method needs to be implemented by the
     * child classt that utilizes the concrete parser
     */
    function parse() {}
}
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

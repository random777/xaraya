<?php
/*
 * $Id: ExpatParser.php,v 1.8 2003/06/04 12:22:36 purestorm Exp $
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
import("phing.system.io.IOException");
import("phing.system.io.FileReader");
import("phing.parser.*");

/**
 * This class is a wrapper for the PHP's internal expat parser.
 *
 * It takes an XML file represented by a abstract path name, and starts
 * parsing the file and calling the different "trap" methods inherited from
 * the AbstractParser class.
 *
 * Those methods then invoke the represenatative methods in the registered
 * handler classes.
 *
 * @author	  Andreas Aderhold <andi@binarycloud.com>
 * @copyright © 2001,2002 THYRELL. All rights reserved
 * @version   $Revision: 1.8 $ $Date: 2003/06/04 12:22:36 $
 * @access    public
 * @package   phing.parser
 */

class ExpatParser extends AbstractSAXParser {

    var $parser = null;
    var $reader = null;
    var $file = null;
    var $buffer = 4096;
    var $error_string = "";
    var $line = 0;
    var $location = null;

    /**
     * Constructs a new ExpatParser object.
     *
     * The constructor accepts a File object that represents the filename
     * for the file to be parsed. It sets up php's internal expat parser
     * and options.
     *
     * @param  object  The Reader Object that is to be read from.
     * @throws RuntimeException if the given argument is not a File object
     * @access public
     */
    function ExpatParser(&$reader, $filename=null) {

        if (!is_a($reader, "Reader")) {
            throw (new RuntimeException("Illegal argument type (Reader required)", __FILE__, __LINE__));
            System::halt(-1);
        }
        $this->reader =& $reader;
        if ($filename !== null) 
            $this->file = new File($filename);
        $this->parser = xml_parser_create();
        $this->buffer = 4096;
        $this->location = new Location();
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser,"startElement","endElement");
        xml_set_character_data_handler($this->parser,"characters");
    }

    /**
     * Override PHP's parser default settings, created in the constructor.
     *
     * @param  string  the option to set
     * @throws mixed   the value to set
     * @return boolean true if the option could be set, otherwise false
     * @access public
     */
    function parserSetOption($opt, $val) {
        return xml_parser_set_option($this->parser, $opt, $val);
    }

    /**
     * Returns the location object of the current parsed element. It describes
     * the location of the element within the XML file (line, char)
     *
     * @return object  the location of the current parser
     * @access public
     */
    function &getLocation() {
        return $this->location;
    }

    /**
     * Starts the parsing process.
     *
     * @param  string  the option to set
     * @return int     1 if the parsing succeeded
     * @throws ExpatParserException if something gone wrong during parsing
     * @throws IOException if XML file can not be accessed
     * @access public
     */
    function parse() {
        while ( ($data = $this->reader->read()) !== -1 ) {
            // update the location
            if ($this->file !== null) 
                $path = $this->file->getAbsolutePath();
            else
                $path = "unknown file";

            $this->location = new Location(
                    $path,
                    xml_get_current_line_number($this->parser),
                    xml_get_current_column_number($this->parser)
                    );

            if (!xml_parse($this->parser, $data, $this->reader->eof())) {
                $error = xml_error_string(xml_get_error_code($this->parser));
                xml_parser_free($this->parser);
                throw (new ExpatParseException($error, $this->location));
                return;
            }
        }
        xml_parser_free($this->parser);
        
        return 1;
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

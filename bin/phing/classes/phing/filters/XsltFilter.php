<?php

/*
 * $Id: XsltFilter.php,v 1.21 2003/07/09 06:06:39 purestorm Exp $
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

import('phing.filters.BaseParamFilterReader');

/**
 * XSLT Filter.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a> (based
 *             on previous XsltTask v1.22 of Andreas Aderhold, andi@binarycloud.com)
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.21 $ $Date: 2003/07/09 06:06:39 $
 * @access    public
 * @see       FilterReader
 * @package   phing.filters
 */
class XsltFilter extends BaseParamFilterReader {

    /**
     * XSL stylesheet.
     * @var string
     */
    var	$_xslFile   = null;

    /**
     * Whether XML file has been transformed.
     * @var boolean
     */
    var	$_processed = false;

    /**
     * [Deprecated] The transformed XML file.
     * @var string
     */
    var	$_buffer = null;

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseParamFilterReader#BaseParamFilterReader()
     */
    function XsltFilter() {
        // This may be a common problem, let's be nice.
        if (!function_exists("xslt_create")) {
            throw (new RuntimeException("XSLT extension required for this operation."), __FILE__, __LINE__);
            System::halt(-1);
            return;
        }

        parent::BaseParamFilterReader();
    }

    /**
     * Creates a new filtered reader.
     *
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A LineContains object filtering the underlying
     *                stream.
     */
    function &newXsltFilter(&$reader) {
        $this->log("Creating new XsltFilter, stylesheet is \"" . $this->_xslFile->toString() . "\"", PROJECT_MSG_DEBUG);

        // type check, error must never occur, bad code of it does
        if (!is_a($reader, "Reader")) {
            throw (new RuntimeException("Expected object of type 'Reader' got something else"), __FILE__, __LINE__);
            System::halt(-1);
            return;
        }

        $o = new XsltFilter();
        $o->setReader($reader);

        return $o;
    }

    /**
     * Set the XSLT stylesheet.
     * @param mixed $file File object or path.
     */
    function setStyle($file) {
        if ( is_a($file, "File") ) {
            $file = $file->getPath();
        }

        $this->_xslFile = new File((string) $file);
    }

    /**
     * Get the path to XSLT stylesheet.
     * @return mixed XSLT stylesheet path.
     */
    function getStyle() {
        if ( is_a($this->_xslFile, "File") )
            return $this->_xslFile->toString();
        return $this->_xslFile;
    }

    /**
     * Reads stream, applies XSLT and returns resulting stream.
     * @return string transformed buffer.
     */
    function read() {

        if ($this->_processed === true)
            return -1; // EOF

        if ( !$this->getInitialized() ) {
            $this->_initialize();
            $this->setInitialized(true);
        }

        // Read XML
        $_xml = NULL;
        while ( ($data = $this->in->read()) !== -1 )
            $_xml .= $data;

        if ($_xml === NULL ) { // EOF?
            return -1;
        }

        if(empty($_xml)) {
            $this->log("XML file is empty!", PROJECT_MSG_WARN);
            return ''; // return empty string, don't attempt to apply XSLT
        }
       
        // Read XSLT
        $xslFr = new FileReader($this->_xslFile);
        $xslFr->readInto($_xsl);

        $this->log("Processing file now", PROJECT_MSG_DEBUG);

        // { try
        $out = $this->_ProcessXsltTransformation($_xml, $_xsl);
        $this->_processed = true;
        if (catch("IOException", $e)) {
            throw(new BuildException($e->getMessage(), __FILE__, __LINE__));
            return false;
        }

        return $out;
    }

    // {{{ method _ProcessXsltTransformation($xml, $xslt) throws BuildException
    /**
     * Try to process the XSLT transformation
     *
     * @param   string  XML to process.
     * @param   string  XSLT sheet to use for the processing.
     *
     * @throws BuildException   On XSLT errors
     */
    function _ProcessXsltTransformation($_xml, $_xsl) {
        $processor = xslt_create();
        xslt_set_encoding($processor,"ISO-8859-1");

        $arguments = array(
                         '/_xml'	=> $_xml,
                         '/_xsl' => $_xsl);

        $result = xslt_process($processor, 'arg:/_xml', 'arg:/_xsl', NULL, $arguments);
        if ( !$result ) {
            print $_xml;
            $errno = xslt_errno($processor);
            $err   = xslt_error($processor);
            $this->_buffer = null;
            xslt_free($processor);
            throw (new BuildException("XSLT Error no $errno. $err"), __FILE__, __LINE__);
            return false;
        } else {
            return $result;
        }
    }
    // }}}

    /**
     * [Deprecated. Chain system uses new read() method.]
     * It is expected to return the next _char_ and ends up queueing data to get around this.
     * @return string Single character.
     */
    function readChar() {
        $logger =& System::getLogger();
        $logger->log("Processing file now", PROJECT_MSG_DEBUG);

        if ( !$this->getInitialized() ) {
            $this->_initialize();
            $this->setInitialized(true);
        }

        if ( !$this->_processed ) {
            $this->_processFile();
            $this->_processed = true;
        }

        if ( ($this->_buffer === null) || (strlen($this->_buffer) === 0) ) {
            $this->_buffer = null;
            $ch = -1;
        } else {
            $ch = substr($this->_buffer, 0, 1);
            $this->_buffer = substr($this->_buffer, 1);
            if ( strlen($this->_buffer) === 0 )
                $this->_buffer = null;
        }

        return $ch;
    }

    /**
     * [Deprecated. used by readChar(); chain system uses new read() method.]
     * 
     */
    function _processFile() {
        $processor = xslt_create();

        $xslFr = new FileReader($this->_xslFile);
        $xslFr->readInto($_xsl);

        $_xml = "";
        while ( ($ch = $this->in->readChar()) !== -1 ) {
            $_xml .= $ch;
        }

        $arguments = array(
                         '/_xml'	=> $_xml,
                         '/_xsl' => $_xsl);

        $result = xslt_process($processor, 'arg:/_xml', 'arg:/_xsl', NULL, $arguments);
        if ( !$result ) {
            $errno = xslt_errno($processor);
            $err   = xslt_error($processor);
            $this->_buffer = null;
            xslt_free($processor);
            throw (new BuildException("XSLT Error no $errno. $err"), __FILE__, __LINE__);
        } else {
            $this->_buffer = $result;
        }

    }

    /**
     * Creates a new XsltFilter using the passed in
     * Reader for instantiation.
     *
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     *
     * @return object A new filter based on this configuration, but filtering
     *         the specified reader
     */
    function &chain(&$reader) {
        $newFilter = &XsltFilter::newXsltFilter($reader);
        $newFilter->setStyle($this->getStyle());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /**
     * Parses the parameters to get stylesheet path.
     */
    function _initialize() {
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i = 0 ; $i<count($params) ; $i++) {
                if ( $params[$i]->getName() === "style" ) {
                    $this->setStyle($params[$i]->getValue());
                    break;
                }
            }
        }
    }

}

?>

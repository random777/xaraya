<?php
/*
 * $Id: FilterSet.php,v 1.4 2003/02/24 18:22:16 openface Exp $
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

class FilterSet extends DataType {

    /** The default token start string */
    var $DEFAULT_TOKEN_START = "@";

    /** The default token end string */
    var $DEFAULT_TOKEN_END = "@";

    var $startOfToken = DEFAULT_TOKEN_START;
    var $endOfToken = DEFAULT_TOKEN_END;

    /**
     * List of ordered filters and filter files.
     */
    var $filters = array();

    /**
     * Create a Filterset from another filterset
     *
     * @param object the filterset upon which this filterset will be based.
     */
    function FilterSet($filterset = null) {
        parent::DataType();
        // ZE2 copy return data using ->clone();
        if ($filterSet !== null) {
            $this->filters = $filterset->_getFilters();
        }
        $this->startOfToken = $this->DEFAULT_TOKEN_START;
        $this->endOfToken = $this->DEFAULT_TOKEN_END;
    }

    function &_getFilters() {
        if ($this->isReference()) {
            $ref =& $this->_getRef();
            return $ref->_getFilters();
        }
        return $this->filters;
    }

    function &_getRef() {
        // fixme
        return $this->getCheckedRef($class, "filterset");
    }

    /**
     * Gets the filter hash of the FilterSet.
     *
     * @return   The hash of the tokens and values for quick lookup.
     */
    function getFilterHash() {
        $filterSize = count ($this->_getFilters());
        $filterHash = array();
        $filters =& $this->_getFilters();
        for ($i=0; $i< count($filters); ++$i) {
            $f =& $filters[$i];
            $filterHash[$f->getToken()] = $f->getValue();
        }
        return $filterHash;
    }

    /**
     * set the file containing the filters for this filterset.
     *
     * @param object sets the filter file to read filters for this filter set
    *        from.
     * @throws BuildException if there is a problem reading the filters
     */
    function setFiltersfile(&$filtersFile)  {
        if ($this->isReference()) {
            throw($this->tooManyAttributes(), __FILE__, __LINE__);
        }
        $this->readFiltersFromFile($filtersFile);
    }

    /**
     * The string used to id the beginning of a token.
     *
     * @param string  The new Begintoken value
     */
    function setBeginToken($startOfToken) {
        if ($this->isReference()) {
            throw($this->tooManyAttributes(), __FILE__, __LINE__);
        }
        $this->startOfToken = $startOfToken;
    }

    function getBeginToken() {
        if ($this->isReference()) {
            $ref =& $this->_getRef();
            return $reg->getBeginToken();
        }
        return $this->startOfToken;
    }


    /**
     * The string used to id the end of a token.
     *
     * @param string  The new Endtoken value
     */
    function setEndToken($endOfToken) {
        if ($this->isReference()) {
            throw($this->tooManyAttributes(), __FILE__, __LINE__);
        }
        $this->endOfToken = $endOfToken;
    }

    function getEndToken() {
        if ($this->isReference()) {
            $ref =& $this->_getRef();
            return $reg->getEndToken();
        }
        return $this->endOfToken;
    }


    /**
     * Read the filters from the given file.
     *
     * @param  object         the file from which filters are read
     * @throws BuildException  Throw a build exception when unable to read the
     * file.
     */
    function readFiltersFromFile(&$filtersFile) {
        if ($this->isReference()) {
            throw ($this->tooManyAttributes(), __FILE__, __LINE__);
        }

        if ($filtersFile->isFile()) {
            $this->log("Reading filters from " . $filtersFile->toString, PROJECT_MSG_VERBOSE );
            { // try to load properties
                $props = new Properties();
                $props->load($filtersFile);
                $enum = $props->propertyNames();
                foreach($enum as $key) {
                    $value = $props->getProperty($key);
                    $this->filters[] = new Filter($key, $value);
                }
            }
            if (catch ("Exception", $ioe)) {
                throw (new BuildException("Could not read filters from file " . $filtersFile->toString()), __FILE__, __LINE__);
                return;
            }

        } else {
            throw (new BuildException("Must specify a file not a directory in the filtersfile attribute:" . $filtersFile->toString()), __FILE__, __LINE__);
        }
    }

    /**
     *  Does replacement on the given string with token matching.
     *  This uses the defined begintoken and endtoken values which default to
    *  @ for both.
     *
     *  @param  string  The line to process the tokens in.
     *  @return string  The string with the tokens replaced.
     */
    function replaceTokens($line) {
        $beginToken = $this->getBeginToken();
        $endToken = $this->getEndToken();
        $index = strIndexOf($beginToken, $line);

        if ($index > -1) {
            $tokens = $this->getFilterHash();
            { // try to replace token
                $b = "";
                $i = 0;
                $token = null;
                $value = null;
                do {
                    $endIndex = indexOf( $endToken, $line, $index + strlen($beginToken) + 1 );
                    if ($endIndex == -1) {
                        break;
                    }
                    $token = line.substring(index + beginToken.length(), endIndex );
                    $b .= substring($line, $i, $index);
                    if (array_key_exists($tokens, $token)) {
                        $value = (string) $tokens[$token];
                        $this->log("Replacing: " . $beginToken . $token . $endToken . " -> " . $value, PROJECT_MSG_VERBOSE);
                        $b .= $value;
                        $i = $index + strlen($beginToken) + strlen($token) + strlen($endToken);
                    } else {
                        // just append beginToken and search further
                        $b .= $beginToken;
                        $i = $index + strlen($beginToken);
                    }
                } while (($index = strIndexOf($beginToken, $line, $i )) > -1 );

                $b .= substring($line, $i);
                return (string) $b;
            }
            if (catch("StringIndexOutOfBoundsException", $e)) {
                return $line;
            }
        } else {
            return $line;
        }
    }

    /**
     *  Create a new filter
     *
     *  @param  mixed the filter to be added or the string tokem
     *  @param  string the value of the token, if param1 is given
     */
    function addFilter(&$filterOrToken, $value = null) {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed(), __FILE__, __LINE__);
        }

        if (isinstanceof($filterOrToken, "Filter")) {
            $this->filters[] =& $filter;
        } else {
            $this->filters[] = new Filter($filterOrToken, $value);
        }
    }

    /**
     *  Create a new FiltersFile
     *
     *  @return  object   The filter that was created.
     */
    function &createFiltersfile() {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed(), __FILE__, __LINE__);
        }
        return new FiltersFile();
    }

    /**
     *  Add a Filterset to this filter set
     *
     *  @param object the filterset to be added to this filterset
     */
    function addFilterSet(&$filterSet) {
        if ($this->isReference()) {
            throw ($this->noChildrenAllowed(), __FILE__, __LINE__);
        }
        $filters =& $filterSet->_getFilters();
        for ($i=0; $i<count($filters); ++$i) {
            $this->filters[] =& $filters[$i];
        }
    }

    /**
    * Test to see if this filter set it empty.
    *
    * @return  boolean Return true if there are filter in this set otherwise
    *                  false.
    */
    function hasFilters() {
        return ( count($this->_getFilters()) > 0 );
    }
}


/**
* Individual filter component of filterset. ze2->innerclass of filterset
*/

class Filter {
    /** Token which will be replaced in the filter operation */
    var $token;

    /** The value which will replace the token in the filtering operation */
    var $value;

    /**
     * Constructor for the Filter object
     *
     * @param string  The token which will be replaced when filtering
     * @param string The value which will replace the token when filtering
     */
    function Filter($token = null, $value = null) {
        $this->token = (string) $token;
        $this->value = (string) $value;
    }

    /**
     * Gets the Token attribute of the Filter object
     *
     * @return  string The Token value
     */
    function getToken() {
        return $this->token;
    }

    /**
     * Gets the Value attribute of the Filter object
     *
     * @return  string The Value value
     */
    function getValue() {
        return $this->value;
    }
}
/**
 * The filtersfile nested element. (ze2->inner class of filterset)
 */
class FiltersFile {
    var $outer;
    /**
     * Constructor for the Filter object
     */
    function  FiltersFile() {}
    /**
     * Sets the file from which filters will be read.
     *
     * @param object the file from which filters will be read.
     */
    function setFile(&$file) {
        $this->outer->readFiltersFromFile($file);
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

<?php

/*
 * $Id: ReplaceTokens.php,v 1.18 2003/06/02 17:46:55 openface Exp $  
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
import('phing.types.TokenSource');

/*
 * Replaces tokens in the original input with user-supplied values.
 *
 * Example:
 *
 * <pre><replacetokens begintoken="#" endtoken="#">;
 *   <token key="DATE" value="${TODAY}"/>
 * </replacetokens></pre>
 *
 * Or:
 *
 * <pre><filterreader classname="phing.filters.ReplaceTokens">
 *   <param type="tokenchar" name="begintoken" value="#"/>
 *   <param type="tokenchar" name="endtoken" value="#"/>
 *   <param type="token" name="DATE" value="${TODAY}"/>
 * </filterreader></pre>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @author    hans lellelid, hans@velum.net
 * @version   $Revision: 1.18 $ $Date: 2003/06/02 17:46:55 $
 * @access    public
 * @see       BaseParamFilterReader
 * @package   phing.filters
 */
class ReplaceTokens extends BaseParamFilterReader {
    // {{{ properties
    /**
     * Default "begin token" character.
     * @var string
     */
    var	$_DEFAULT_BEGIN_TOKEN = "@";

    /**
     * Default "end token" character.
     * @var string
     */
    var	$_DEFAULT_END_TOKEN = "@";

    /**
     * [Deprecated] Data that must be read from, if not null.
     * @var string
     */
    var	$_queuedData = null;

    /**
     * Array to hold the replacee-replacer pairs (String to String).
     * @var array
     */
    var	$_tokens = array();

    /**
     * Array to hold the token sources that make tokens from
     * different sources available
     * @var array
     */
    var $_tokensources = array();

    /**
     * Array holding all tokens given directly to the Filter and
     * those passed via a TokenSource.
     * @var array
     */
    var $_alltokens = null;

    /**
     * Character marking the beginning of a token.
     * @var string
     */
    var	$_beginToken = null;

    /**
     * Character marking the end of a token.
     * @var string
     */
    var	$_endToken = null;
    // }}}

    /**
     * Constructor for "dummy" instances.
     * 
     * @see BaseFilterReader#BaseFilterReader()
     */
    function ReplaceTokens() {
        parent::BaseParamFilterReader();

        $this->_beginToken = $this->_DEFAULT_BEGIN_TOKEN;
        $this->_endToken   = $this->_DEFAULT_END_TOKEN;
    }

    /*
     * Creates a new filtered reader.
     *
     * @param reader A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return object A ReplaceTokens object filtering the underlying
     *                stream.
    */
    function &newReplaceTokens(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Expected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $o = new ReplaceTokens();
        $o->setReader($reader);

        return $o;
    }

    /**
     * Performs lookup on key and returns appropriate replacement string.
     * @param string $key Key to search for.
     * @return string 	Text with which to replace key or value of key if none is found.
     * @access private
     */
    function _replaceToken($key) {
        /* Get tokens from tokensource and merge them with the
         * tokens given directly via build file. This should be 
         * done a bit more elegantly
         */
        if ($this->_alltokens === null) {
            $this->_alltokens = array();

            $count = count($this->_tokensources);
            for ($i = 0; $i < $count; $i++) {
                $source =& $this->_tokensources[$i];
                $this->_alltokens = array_merge($this->_alltokens, $source->getTokens());
            }


            $this->_alltokens = array_merge($this->_tokens, $this->_alltokens);
        }

        $tokens =& $this->_alltokens;

        $replaceWith = null;
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            if ($tokens[$i]->getKey() === $key) {
                $replaceWith = $tokens[$i]->getValue();
            }
        }

        // TODO: Clean this up... This should go to the Project's logger.
        // Hoever, a Filter does not know anything about the Project it
        // belongs to yet :(
        if ($replaceWith === null) {
            $replaceWith = $this->_beginToken . $key . $this->_endToken;
            #$logger =& System::getLogger();
            #$logger->Log(PH_LOG_DEBUG, "No token defined for key \"$key\"");
        } 

        return $replaceWith;
    }

    /**
     * Returns stream with tokens having been replaced with appropriate values.
     * If a replacement value is not found for a token, the token is left in the stream.
     * 
     * @return mixed filtered stream, -1 on EOF.
     */
    function read() {
        if ( !$this->getInitialized() ) {
            $this->_initialize();
            $this->setInitialized(true);
        }

        // read from next filter up the chain
        $buffer = $this->in->read();

        if($buffer === -1) {
            return -1;
        }

        // filter buffer
        $buffer = preg_replace("/".preg_quote($this->_beginToken)."([\w\.]+?)".preg_quote($this->_endToken)."/e",
                               "\$this->_replaceToken(\"$1\")", $buffer);

        return $buffer;
    }

    /**
    * [Deprecated. For reference only. Chain system uses new read() method.]  
     * Returns the next character in the filtered stream, replacing tokens
     * from the original stream.
     * 
     * @return the next character in the resulting stream, or -1
     *         if the end of the resulting stream has been reached
     * 
     * @exception IOException if the underlying stream throws an IOException
     * during reading     
     */
    function readChar() {
        if ( !$this->getInitialized() ) {
            $this->_initialize();
            $this->setInitialized(true);
        }

        $ch = -1;

        if ( $this->_queuedData !== null && strlen($this->_queuedData) > 0 ) {
            $ch = substr($this->_queuedData, 0, 1);
            if ( strlen($this->_queuedData) > 1 ) {
                $this->_queuedData = substr($this->_queuedData, 1);
            } else {
                $this->_queuedData = null;
            }
            return $ch;
        }

        $ch = $this->in->readChar();
        if ( $ch === $this->_beginToken ) {
            $key = "";
            do {
                $ch = $this->in->readChar();
                if ( $ch !== -1 ) {
                    $key .= $ch;
                } else {
                    break;
                }
            } while ( $ch !== $this->_endToken );

            if ( $ch === -1 ) {
                $this->_queuedData = $this->_beginToken.$key;
                return $this->readChar();
            } else {
                $key = substr($key, 0, strlen($key)-1);
                $replaceWith = null;
                for($i = 0 ; $i<count($this->_tokens) ; $i++) {
                    if ( $this->_tokens[$i]->getKey() === $key ) {
                        $replaceWith = $this->_tokens[$i]->getValue();
                        // I could place a break here. I don't do that because
                        // tokens are normally stored in hashtable and then
                        // key are overridable.
                    }
                }

                if ( $replaceWith !== null ) {
                    $this->_queuedData = $replaceWith;
                    return $this->readChar();
                } else {
                    $this->_queuedData = $this->_beginToken.$key.$this->_endToken;
                    return $this->readChar();
                }
            }
        }

        return $ch;
    }

    // {{{ Accessors
    /**
     * Sets the "begin token" character.
     * 
     * @param string $beginToken the character used to denote the beginning of a token.
     */
    function setBeginToken($beginToken) {
        $this->_beginToken = (string) $beginToken;
    }

    /**
     * Returns the "begin token" character.
     * 
     * @return string The character used to denote the beginning of a token.
     */
    function getBeginToken() {
        return $this->_beginToken;
    }

    /**
     * Sets the "end token" character.
     * 
     * @param string $endToken the character used to denote the end of a token
     */
    function setEndToken($endToken) {
        $this->_endToken = (string) $endToken;
    }

    /**
     * Returns the "end token" character.
     * 
     * @return the character used to denote the beginning of a token
     */
    function getEndToken() {
        return $this->_endToken;
    }

    /**
     * Adds a token element to the map of tokens to replace.
     * 
     * @return object The token added to the map of replacements.
     *               Must not be <code>null</code>.
     */
    function &createToken() {
        $num = array_push($this->_tokens, new Token());
        return $this->_tokens[$num-1];
    }
    
    /**
     * Adds a token source to the sources of this filter.
     *
     * @return  object  A Reference to the source just added.
     */
    function &createTokensource() {
        $num = array_push($this->_tokensources, new TokenSource());
        return $this->_tokensources[$num-1];
    }

    /**
     * Sets the map of tokens to replace.
     * ; used by ReplaceTokens::chain()
     *
     * @param array A map (String->String) of token keys to replacement
     *              values. Must not be <code>null</code>.
     */
    function setTokens($tokens) {
        // type check, error must never occur, bad code of it does
        if ( !is_array($tokens) ) {
            throw (new RuntimeException("Excpected 'array', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $this->_tokens = $tokens;
    }

    /**
     * Returns the map of tokens which will be replaced.
     * ; used by ReplaceTokens::chain()
     *
     * @return array A map (String->String) of token keys to replacement values.
     */
    function getTokens() {
        return $this->_tokens;
    }
    // }}}

    /**
     * Sets the tokensources to use; used by ReplaceTokens::chain()
     * 
     * @param   array   An array of token sources.
     */ 
    function setTokensources($sources) {
        // type check
        if ( !is_array($sources)) {
            throw(new RuntimeException("Exspected 'array', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }
        $this->_tokensources = $sources;
    }

    /**
     * Returns the token sources used by this filter; used by ReplaceTokens::chain()
     * 
     * @return  array
     */
    function getTokensources() {
        return $this->_tokensources;
    }

    /**
     * Creates a new ReplaceTokens using the passed in
     * Reader for instantiation.
     * 
     * @param object A Reader object providing the underlying stream.
     *               Must not be <code>null</code>.
     * 
     * @return object A new filter based on this configuration, but filtering
     *         the specified reader
     */
    function &chain(&$reader) {
        // type check, error must never occur, bad code of it does
        if (!is_a($reader, 'Reader')) {
            throw (new RuntimeException("Expected object of type 'Reader', got something else"), __FILE__, __LINE__);
            System::halt(-1);
        }

        $newFilter = ReplaceTokens::newReplaceTokens($reader);
        $newFilter->setBeginToken($this->getBeginToken());
        $newFilter->setEndToken($this->getEndToken());
        $newFilter->setTokens($this->getTokens());
        $newFilter->setTokensources($this->getTokensources());
        $newFilter->setInitialized(true);

        return $newFilter;
    }

    /**
     * Initializes tokens and loads the replacee-replacer hashtable.
     * This method is only called when this filter is used through
     * a <filterreader> tag in build file.
     */
    function _initialize() {
        $params = $this->getParameters();
        if ( $params !== null ) {
            for($i = 0 ; $i<count($params) ; $i++) {
                if ( $params[$i] !== null ) {
                    $type = $params[$i]->getType();
                    if ( $type === "tokenchar" ) {
                        $name = $params[$i]->getName();
                        if ( $name === "begintoken" ) {
                            $this->_beginToken = substr($params[$i]->getValue(), 0, 1);
                        } else if ( $name === "endtoken" ) {
                            $this->_endToken = substr($params[$i]->getValue(), 0, 1);
                        }
                    } else if ( $type === "token" ) {
                        $name  = $params[$i]->getName();
                        $value = $params[$i]->getValue();

                        $tok = new Token();
                        $tok->setKey($name);
                        $tok->setValue($value);

                        array_push($this->_tokens, $tok);
                    } else if ( $type === "tokensource" ) {
                        // Store data from nested tags in local array
                        $arr = array(); $subparams = $params[$i]->getParams();
                        $count = count($subparams);
                        for ($i = 0; $i < $count; $i++)  {
                            $arr[$subparams[$i]->getName()] = $subparams[$i]->getValue();
                        }

                        // Create TokenSource
                        $tokensource =& new TokenSource();
                        if (isset($arr["classname"])) 
                            $tokensource->setClassname($arr["classname"]);

                        // Copy other parameters 1:1 to freshly created TokenSource
                        foreach ($arr as $key => $value) {
                            if (strtolower($key) === "classname")
                                continue;
                            $param =& $tokensource->createParam();
                            $param->setName($key);
                            $param->setValue($value);
                        }

                        $this->_tokensources[] =& $tokensource;
                    }
                }
            }
        }
    }
}

/**
 * Holds a token.
 */
class Token {

    /**
     * Token key.
     * @var string
     */
    var $_key;

    /**
     * Token value.
     * @var string
     */
    var $_value;

    /**
     * Sets the token key.
     * 
     * @param string $key The key for this token. Must not be <code>null</code>.
     */
    function setKey($key) {
        $this->_key = (string) $key;
    }

    /**
     * Sets the token value.
     * 
     * @param string $value The value for this token. Must not be <code>null</code>.
     */
    function setValue($value) {
        $this->_value = (string) $value;
    }

    /**
     * Returns the key for this token.
     * 
     * @return string The key for this token.
     */
    function getKey() {
        return $this->_key;
    }

    /**
     * Returns the value for this token.
     * 
     * @return string The value for this token.
     */
    function getValue() {
        return $this->_value;
    }
}

?>

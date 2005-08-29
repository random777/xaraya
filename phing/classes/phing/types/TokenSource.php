<?php
/*
 * $Id: TokenSource.php,v 1.2 2003/04/24 19:25:42 purestorm Exp $
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

import('phing.types.DataType');

/**
 * A parameter is composed of a name, type and value.
 *
 * Example of usage:
 *
 * <replacetokens>
 *   <tokensource classname="phing.filters.util.IniFileTokenReader">
 *     <!-- all params for the TokenReader here -->
 *     <param name="file" value="tokens.ini" />
 *   </tokensource>
 * </replacetokens>
 *
 * or:
 * 
 * <filterreader classname="phing.filters.ReplaceTokens">
 *   <param type="tokensource>
 *     <param name="classname" value="phing.filters.util.IniFileTokenReader" />
 *     <param name="file" value="tokens.ini" />
 *   </param>
 * </filterreader>
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @package   phing.types
*/
class TokenSource extends DataType {
    // {{{ properties
    /**
     * String to hold the path to the TokenReader
     * @var     string
     */
    var $classname = null;

    /**
     * Array holding parameters for the wrapped TokenReader.
     * @var     array
     */
    var $parameters = array();

    /**
     * Reference to the TokenReader used by this TokenSource
     * @var     object
     */
    var $reader = null;

    /**
     * Array with key/value pairs of tokens
     */
    var $tokens = null;
    // }}}

    function TokenSource() {}

    /**
     * This method is called to load the sources from the reader
     * into the buffer of the source.
     */
    function Load() {
        // Create new Reader
        if ($this->classname === null) {
            throw(BuildException("No Classname given to TokenSource.", __FILE__, __LINE__));
            return;
        }
        import($this->classname);
        $lastDot = strLastIndexOf(".", $this->classname);
        $classname = substring($this->classname, $lastDot+1);
        $this->reader =& new $classname($this->project);

        // Configure Reader
        $this->configureTokenReader($this->reader);

        // Load Tokens
        // try {
        while ($token = $this->reader->readToken()) {
            $this->tokens[] = $token;
        }
        if (catch("BuildException", $e)) {
            $this->log("Error reading TokenSource: " . $e->toString(), PROJECT_MSG_WARN);
        }
        if (catch("IOException", $e)) {
            $this->log("Error reading TokenSource: " . $e->toString(), PROJECT_MSG_WARN);
        }
    }

    /**
     * This function uses the wrapper to read the tokens and then
     * returns them.
     *
     * @access  public
     */
    function getTokens() {
        if ($this->tokens === null)
            $this->Load();

        return $this->tokens;
    }

    /**
     * Configures a TokenReader with the parameters passed to the
     * TokenSource.
     * 
     * @access      private
     */
    function configureTokenReader(&$reader) {
        $count = count($this->parameters);
        for ($i = 0; $i < $count; $i++) {
            $method_name = "Set" . $this->parameters[$i]->getName();
            $value = $this->parameters[$i]->getValue();
            $reader->$method_name($value);
        }

        return;
    }

    // {{{ Accessors
    function setClassname($str) {
        $this->classname= $str;
    }
    function getClassname() {
        return $this->classname;
    }

    function &createParam() {
        $num = array_push($this->parameters, new Parameter());
        return $this->parameters[$num-1];
    }
    // }}}
}


?>

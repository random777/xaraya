<?php
/*
 * $Id: IniFileTokenReader.php,v 1.2 2003/04/24 19:25:42 purestorm Exp $
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

import("phing.types.TokenReader");
import("phing.system.io.IOException");
import("phing.filters.ReplaceTokens"); // For class Token

/**
 * Class that allows reading tokens from INI files.
 * 
 * @author    Manuel Holtgewe
 * @version   $Revision: 1.2 $
 * @access    public
 * @package   phing.filters.util
 */
class IniFileTokenReader extends TokenReader {
    // {{{
    /**
     * Holds the path to the INI file that is to be read.
     * @var object  Reference to a File Object representing
     *              the path to the INI file.
     */
    var $file = null;

    /**
     * @var string  Sets the section to load from the INI file.
     *              if omitted, all sections are loaded.
     */
    var $section = null;
    // }}}

    /**
     * Reads the next token from the INI file
     *
     * @throws  IOException     On error
     * @access  public
     */
    function readToken() {
        if ($this->file === null) {
            throw(new BuildException("No File set for IniFileTokenReader"), __FILE__, __LINE__);
            return;
        }

        static $tokens = null;
        if ($tokens === null) {
            $tokens = array();
            $arr = parse_ini_file($this->file->getAbsolutePath(), true);
            if ($this->section === null) {
                foreach ($arr as $sec_name => $values) {
                    foreach($arr[$sec_name] as $key => $value) {
                        $tok = new Token;
                        $tok->setKey($key);
                        $tok->setValue($value);
                        $tokens[] = $tok;
                    }
                }
            } else if (isset($arr[$this->section])) {
                foreach ($arr[$this->section] as $key => $value) {
                    $tok = new Token;
                    $tok->setKey($key);
                    $tok->setValue($value);
                    $tokens[] = $tok;
                }
            }
        }

        if (count($tokens) > 0) {
            return array_pop($tokens);
        } else
            return null;
    }
    
    // {{{ Accessors
    function setFile($file) {
        if (is_a("File", $file)) {
            $this->file =& $file;
        } else {
            $this->file = new File((string) $file);
        }
    }

    function setSection($str) {
        $this->section = (string) $str;
    }
    // }}}
}

?>

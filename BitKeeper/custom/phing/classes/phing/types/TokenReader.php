<?php
/*
 * $Id: TokenReader.php,v 1.1 2003/04/24 19:35:03 purestorm Exp $
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

import("phing.system.io.Reader");
import("phing.system.io.IOException");
import("phing.filters.ReplaceTokens"); // For class Token

import("phing.Project");

/**
 * Abstract class for TokenReaders
 * 
 * @author    Manuel Holtgewe
 * @version   $Revision: 1.1 $
 * @access    public
 * @package   phing.filters.util
 */
class TokenReader extends Reader {
    // {{{ properties
    /**
     * @var object  Reference to the Project the Tokenreader is used in.
     */
    var $project;
    // }}}
    // {{{ constructor TokenReader(&$project);
    /**
     * Constructor
     * @param   object  Reference to the project the TokenReader is used in.
     */
    function TokenReader(&$project) {
        $this->project =& $project;
    }
    // }}}
    // {{{ method log($level, $msg)
    /**
     * Utility function for logging
     */
    function log($level, $msg) {
        $this->project->log($level, $msg);
    }
    // }}}
    // {{{ method readToken
    /**
     * Reads the next token from the Reader
     *
     * @throws  IOException     On error
     * @access  public
     * @abstract
     */
    function readToken() {
        return false;
    }
    // }}}
}

?>

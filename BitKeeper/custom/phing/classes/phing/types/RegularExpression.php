<?php
/*
 * $Id: RegularExpression.php,v 1.4 2003/03/26 21:53:11 purestorm Exp $
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
import('phing.Project');
import('phing.util.regex.RegexMatcher');

/*
 * A regular expression datatype.  Keeps an instance of the
 * compiled expression for speed purposes.  This compiled
 * expression is lazily evaluated (it is compiled the first
 * time it is needed).  The syntax is the dependent on which
 * regular expression type you are using.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @version   $Revision: 1.4 $ $Date: 2003/03/26 21:53:11 $
 * @access    public
 * @see       phing.util.regex.RegexMatcher
 * @package   phing.types
*/
class RegularExpression extends DataType {
    var $_regexp   = null;

    function RegularExpression() {
        $this->_regexp  = new RegexMatcher();
    }

    function setPattern($pattern) {
        $this->_regexp->setPattern($pattern);
    }

    function &getPattern(&$p) {
        if ( $this->isReference() ) {
            $ref = &$this->getRef($p);
            return $ref->getPattern($p);
        }

        return $this->_regexp->getPattern();
    }

    function &getRegexp($p) {
        if ( $this->isReference() ) {
            $ref = &$this->getRef($p);
            return $ref->getRegexp($p);
        }

        return $this->_regexp;
    }

    function &getRef(&$p) {
        if ( !$this->checked ) {
            $stk = array();
            array_push($stk, $this);
            $this->dieOnCircularReference($stk, $p);
        }

        $o = &$this->ref->getReferencedObject($p);
        if ( !is_a($o, "RegularExpression") ) {
            $msg = $this->ref->getRefId()." doesn\'t denote a RegularExpression";
            throw ( new BuildException($msg) );
        } else {
            return $o;
        }
    }
}

?>

<?php
/* 
 * $Id: RegexMapper.php,v 1.3 2003/04/09 15:58:10 thyrell Exp $
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

import('phing.system.lang.functions');
import('phing.mappers.FileNameMapper');
import('phing.util.regex.RegexMatcher');

/**
 *  description here
 *
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *  @author   hans lellelid, hans@velum.net
 *  @version  $Revision: 1.3 $
 *  @package   phing.mappers
 */

class RegexMapper extends FileNameMapper {

	/**
	* @var string
	* @access public
	*/
	var $to  = null;
	
	/**
	 * @var object RegexMatcher object 
	 * @access protected
	 */
	var $reg = null;  // protected

	function RegexMapper($_id = null) {
		parent::FileNameMapper($_id);
		// instantiage regex matcher here
		$this->reg = &new RegexMatcher();
		return true;
	}

    /**
     * Sets the &quot;from&quot; pattern. Required.
     */
    function SetFrom($from) {
		$this->reg->SetPattern($from);
    }

    /**
     * Sets the &quot;to&quot; pattern. Required.
     */
    function SetTo($to) {
	
        // [HL] I'm changing the way this works for now to just use string
		//$this->to = strToCharArray($to);
		
		$this->to = $to;
    }

    function Main($sourceFileName) {
		if ($this->reg == null  || $this->to == null || !$this->reg->matches((string) $sourceFileName)) {
            return null;
        }
        return (array) $this->_replaceReferences($sourceFileName);
    }

    /**
     * Replace all backreferences in the to pattern with the matched groups.
     * groups of the source.
	 * @param string $source The source filename.
	 * @access protected
     */
	function _replaceReferences($source) {
	
		// the expression has already been processed (when ->matches() was run in Main())
		// so no need to pass $source again to the engine.
        $groups = (array) $this->reg->getGroups(); // no param
		
		$result = $this->to;
		
		// Account for \\1 syntax
		$result = preg_replace('/\\\([\d]+)/e', "\$groups[$1]", $result);
			
		/*
		[HL] Instead of doing the following (loop through arr of chars) am using regex code
		 above.  The one possible disadvantage w/ above code is that we lose some of the ability
		 to throw() errors if regex is improperly formed.  E.g. more than likely it will just
		 substitute '' or not change anything.
		
        for ($i=0; $i<count($this->to); ++$i) {
            if ($this->to[$i] === '\\') {
                if (++$i < count($this->to)) {
                    // FIXME
					//$value = (int) Character::digit($this->to[$i], 10);
					// -- [HL] I'm assuming that 2nd param to digit() is "base" ... or ???
                    if ($value > -1) {
                        $result .= (string) $v[$value];
                    } else {
                        $result .= $this->to[$i];
                    }
                } else {
					// XXX - should return err instead?
					// [HL] Yes we should probably throw an exception ...
                    throw (new BuildException("Invalid REGEX replace syntax string. (unescaped '\')")); return;
                    
					// other option is to assume that an un-escaped '\' should be an escaped slash
					$result .= '\\';
                }
            } else {
                $result .= $this->to[$i]);
            }
        }
		*/
			
        return (string) $result;
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

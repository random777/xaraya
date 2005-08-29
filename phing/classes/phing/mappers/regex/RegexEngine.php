<?php
/* 
 * $Id: RegexEngine.php,v 1.2 2003/02/24 18:22:16 openface Exp $
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
 * Contains some shared attributes and methods -- and some abstract methods with
 * engine-specific implementations that sub-classes must override.
 * 
 * @author hans lellelid, hans@velum.net
 * @package phing.util.regex
 */
class RegexEngine {
	
	/**
	* @var boolean
	* @access protected
	*/
	var $_caseInsensitive=false;

	function RegexEngine() { /* do nothing yet */ }
	
	/**
	 * Sets whether or not regex operation is case sensitive.
	 * @param boolean $bit
	 * @return void
	 */
	function SetCaseInsensitive($bit) {
		$this->_caseInsensitive = $bit;
	}
		
	/**
	 * ABSTRACT: Matches pattern against source string and sets the matches array.
	 * @param string $pattern The regex pattern to match.
	 * @param string $source The source string.
	 * @param array $matches The array in which to store matches.
	 * @return boolean Success of matching operation.
	 */
	function Match($pattern, $source, &$matches) { 
		/* abstract method */
	}
	
	/**
	 * ABSTRACT: Matches all patterns in source string and sets the matches array.
	 * @param string $pattern The regex pattern to match.
	 * @param string $source The source string.
	 * @param array $matches The array in which to store matches.
	 * @return boolean Success of matching operation.
	 */	
	function MatchAll($pattern, $source, &$matches) {
		/* abstract method */
	}

	/**
	 * ABSTRACT: Replaces $pattern with $replace in $source string.
	 * @param string $pattern The regex pattern to match.
	 * @param string $replace The string with which to replace matches.
	 * @param string $source The source string.
	 * @return string The replaced source string.
	 */		
	function Replace($pattern, $replace, $source) {
		/* abstract method */
	}

}

?>

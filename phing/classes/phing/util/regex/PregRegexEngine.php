<?php
/* 
 * $Id: PregRegexEngine.php,v 1.3 2003/02/24 18:22:17 openface Exp $
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

import('phing.util.regex.RegexEngine');

/**
 * PREG Regex Engine.
 * Implements a regex engine using PHP's preg_match(), preg_match_all(), and preg_replace() functions.
 * 
 * @author hans lellelid, hans@velum.net
 * @package phing.util.regex
 */
class PregRegexEngine extends RegexEngine {
	
	function PregRegexEngine() { 
		parent::RegexEngine(); 	
	}
	
	/**
	 * The pattern needs to be converted into PREG style -- which includes adding expression delims & any flags, etc.
	 * @param string $pattern
	 * @return string prepared pattern.
	 */
	function _PreparePattern($pattern)
	{
		return '/'.$pattern.'/'.($this->_caseInsensitive ? 'i' : '');
	}
	
	/**
	 * Matches pattern against source string and sets the matches array.
	 * @param string $pattern The regex pattern to match.
	 * @param string $source The source string.
	 * @param array $matches The array in which to store matches.
	 * @return boolean Success of matching operation.
	 */
	function Match($pattern, $source, &$matches) { 
		return preg_match($this->_PreparePattern($pattern), $source, $matches);
	}

	/**
	 * Matches all patterns in source string and sets the matches array.
	 * @param string $pattern The regex pattern to match.
	 * @param string $source The source string.
	 * @param array $matches The array in which to store matches.
	 * @return boolean Success of matching operation.
	 */		
	function MatchAll($pattern, $source, &$matches) {
		return preg_match_all($this->_PreparePattern($pattern), $source, $matches);
	}

	/**
	 * Replaces $pattern with $replace in $source string.
	 * @param string $pattern The regex pattern to match.
	 * @param string $replace The string with which to replace matches.
	 * @param string $source The source string.
	 * @return string The replaced source string.
	*/		
	function Replace($pattern, $replace, $source) {
		return preg_replace($this->_PreparePattern($pattern), $replace, $source);
	}

}

?>
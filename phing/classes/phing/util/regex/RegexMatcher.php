<?php
/* 
 * $Id: RegexMatcher.php,v 1.3 2003/02/24 18:22:17 openface Exp $
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
 * A factory class for regex functions.
 * @author hans lellelid, hans@velum.net
 * @package  phing.util.regex
 */
class RegexMatcher {

	/**
	 * Matching groups found. 
	 * @var array
	 */
	var $groups=array();
	 
	/**
	 * Pattern to match.
	 * @var string
	 */
	var $pattern=null;
		
	/**
	 * The regex engine -- e.g. 'preg' or 'ereg';
	 * @var object
	 */
	var $engine=null;
	
	/**
	 * Constructor sets the regex engine to use (preg by default).
	 * @param string $_engineType The regex engine to use.
	 */
	function RegexMatcher($_engineType='preg') {
		
		$_engine_class = ucfirst(strtolower($_engineType)).'RegexEngine';
		$_engine_classpath = 'phing.util.regex.'.$_engine_class;
		
		import($_engine_classpath);
		$this->engine = &new $_engine_class();
	}

	/**
	 * sets pattern to use for matching
	 * @param string $_matchPattern the pattern to match against
	 * @return void
	 */
	function SetPattern($_matchPattern) {
		$this->pattern = $_matchPattern;		
	}
	
	/**
	 * Performs match of this->$pattern against $subject
	 * @param string $subject The subject, against which to perform matches
	 * @return boolean Whether or not pattern matches subject string passed.
	 * @access public
	 */
	function matches($subject) {
		if($this->pattern === null) {			
			return null;
		}
		return $this->engine->Match($this->pattern, $subject, $this->groups);
	}
	
	/**
	 * Get array of matched groups.
	 * @return array Matched groups
	 */ 
	function GetGroups() {
		return $this->groups;
	}
	
	/**
	 * Sets whether the regex matching is case insensitive.
	 * (default is false -- i.e. case sensisitive)
	 * @param boolean $bit
	 */ 
	function SetCaseInsensitive($bit) {
		$this->engine->SetCaseInsensitive($bit);
	}
	
	/**
	 * Get specific matched group. 
	 * @param integer $_groupIndex
	 * @return string specified group or NULL if group is not set.
	 */ 
	function GetGroup($_groupIndex) { 
		if(isset($this->groups[$_groupIndex])) {
			return $this->groups[$_groupIndex];
		} else {
			return null;
		}
	}
} 

?>
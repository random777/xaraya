<?php
/*
 * $Id: Equals.php,v 1.7 2003/04/09 15:59:23 thyrell Exp $
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

import("phing.BuildException");

/**
 *  A simple string comparator.  Compares two strings for eqiality in a
 *  binary safe manner. Implements the condition interface specification.
 *
 *  @author    Andreas Aderhold <andi@binarycloud.com>
 *  @copyright © 2001,2002 THYRELL. All rights reserved
 *  @version   $Revision: 1.7 $ $Date: 2003/04/09 15:59:23 $
 *  @access    public
 *  @package   phing.tasks.system.condition
 */
class Equals {

	var $_arg1 = null;
	var $_arg2 = null;

	function setArg1($a1) {
		$this->_arg1 = (string) $a1;
	}

	function setArg2($a2) {
		$this->_arg2 = (string) $a2;
	}

	// this method is required by all conditions!
	function evaluate() {
		if ($this->_arg1 === null || $this->_arg2 === null) {
			throw (new BuildException("both arg1 and arg2 are required in equals"), __FILE__, __LINE__); return;
		}
		return !(boolean)strcmp($this->_arg1, $this->_arg2);
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
<?php
/* 
 * $Id: IdentityMapper.php,v 1.3 2003/04/09 15:58:10 thyrell Exp $
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

import('phing.mappers.FileNameMapper');

// {{{ Identity Mapper

/**
 * This mapper does nothing ;)
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.3 $
 *  @package   phing.mappers
 */

class IdentityMapper extends FileNameMapper {

	// {{{ constructor IdentityMapper($_id = null)

	/**
	 * Constructor. Creates the object
	 *
	 * @returns  object    The class instance
	 * @access   public
	 * @author   Andreas Aderhold, andi@binarycloud.com
	 */

	function IdentityMapper($_id = null)
	{
		parent::FileNameMapper($_id);
		return;
	}

	// }}}
	// {{{ method Main($_input)

	/**
	 * The mapper implementation. Basically does nothing in this case.
	 *
	 * @param    mixed     The data the mapper works on
	 * @returns  mixed     The data after the mapper has been applied
	 * @access   public
	 * @author   Andreas Aderhold, andi@binarycloud.com
	 */

	function Main($_sourceFileName)
	{
		return (array) $_sourceFileName;
	}

	// }}}
	// {{{ method SetTo($_to)

	/**
	 * Accessor. Sets the to property
	 *
	 */

	function SetTo($_to) {}

	// }}}
	// {{{ method SetFrom($_to)

	/**
	 * Accessor. Sets the from property. What this mapper should
	 * recognize.
	 *
	 */

	function SetFrom($_from) {}

	// }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

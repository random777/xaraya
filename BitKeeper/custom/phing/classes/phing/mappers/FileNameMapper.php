<?php
// {{{ Header
/*
 * -File       $Id: FileNameMapper.php,v 1.2 2003/04/09 15:58:10 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2002, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.ProjectComponent');

// {{{ FlattenMapper

/**
 * description here
 *
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *  @version  $Revision: 1.2 $
 *  @package   phing.mappers
 */

class FileNameMapper {

	// {{{ constructor FileNameMapper($_id = null)

	/**
	 * Constructor. Creates the object must be called by child classes
	 *
	 * @returns  object    The class instance
	 * @access   public
	 * @author   Andreas Aderhold, andi@binarycloud.com
	 */

	function FileNameMapper() {}

	// }}}
	// {{{ method Main($_input)

	/**
	 * The mapper implementation. This one needs to be implemented
	 *
	 * @param    mixed     The data the mapper works on
	 * @returns  mixed     The data after the mapper has been applied
	 * @access   public
	 * @author   Andreas Aderhold, andi@binarycloud.com
	 */

	function Main($_sourceFileName)
	{
		$f = new File($_sourceFileName);
		return (array) $f->getName();
	}

	// }}}
	// {{{ method SetTo($_to)

	/**
	 * Accessor. Sets the to property. The actual implementation
	 * depends on the child class.
	 *
	 * @param    string     To what this mapper should convert the from string
	 * @returns  boolean    True
	 * @access   public
	 * @author   Andreas Aderhold, andi@binarycloud.com
	 */

	function SetTo($_to) {}

	// }}}
	// {{{ method SetFrom($_to)

	/**
	 * Accessor. Sets the from property. What this mapper should
	 * recognize. The actual implementation is dependent upon the
	 * child class
	 *
	 * @param    string     On what this mapper should work
	 * @returns  boolean    True
	 * @access   public
	 * @author   Andreas Aderhold, andi@binarycloud.com
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

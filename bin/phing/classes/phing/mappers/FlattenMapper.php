<?php
// {{{ Header
/*
 * -File       $Id: FlattenMapper.php,v 1.2 2003/04/09 15:58:10 thyrell Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2002, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.mappers.FileNameMapper');

// {{{ FlattenMapper

/**
 * Flattens the mapper
 *
 * @author   Andreas Aderhold, andi@binarycloud.com
 * @version  $Revision: 1.2 $
 * @package  phing.mappers
 */

class FlattenMapper extends FileNameMapper {

	// {{{ constructor FlattenMapper($_id = null)

	/**
	 * Constructor. Creates the object
	 *
	 * @returns  object    The class instance
	 * @access   public
	 * @author   Andreas Aderhold, andi@binarycloud.com
	 */

	function FlattenMapper($_id = null)
	{
		// FIXME
		// remove this log calls when stables
		$Logger =& System::GetLogger();
		$Logger->Log(PH_LOG_DEBUG, "Attempting to create component (FlattenMapper)");
		parent::FileNameMapper($_id);
		return;
	}

	// }}}
	// {{{ method Main($_sourceFileName)

	/**
	 * The mapper implementation. Returns string with source filename
	 * but without leading directory information
	 *
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
	 * Ignored
	 */

	function SetTo($_to) {}

	// }}}
	// {{{ method SetFrom($_from)

	/**
	 * Ignored
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

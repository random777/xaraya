;
; This file contains handy stuff while developing for xaraya
; provided you are using a (x)emacs like editor. ;-)
;
; It's the intention to gather handy functions for editting
; xaraya files in this package, maybe even make it into a xaraya-mode 
; who knows.
;

;
; Standard file header
;
(defun xar-fileheader ()
	"Insert a xaraya standard phpdoc fileheader"
	(interactive)
	(insert "
/**
 * File: $Id$
 *
 * Short description of purpose of file
 *
 * @package Xaraya eXtensible Management System
 * @copyright (C) 2002 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage module name
 * @link  link to where more info can be found
 * @author author name <author@email> (this tag means responsible person)
*/
"
					)
	)

;
; Standard function header
;
(defun xar-funcheader ()
	"Insert a xaraya standard phpdoc fileheader"
	(interactive)
	(insert "
/**
 * Short description of the function
 *
 * A somewhat longer description of the function which may be 
 * multiple lines, can contain examples.
 *
 * @author  Author Name <author@email>
 * @deprec  <insert this if function is deprecated>
 * @access  public / private / protected
 * @param   param1 type Description of parameter 1
 * @param   param2 type Description of parameter 2
 * @return  type to return description
 * @throws  list of exception identifiers which can be thrown
 * @todo    list of things which must be done to comply to relevant RFC
*/"
					)
	)

;
; Control-Meta-f : fileheader
; Control-Meta-h : funcheader
;
(global-set-key [(control meta f)] 'xar-fileheader)
(global-set-key [(control meta h)] 'xar-funcheader)


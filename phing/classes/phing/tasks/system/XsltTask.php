<?php
// {{{ Header
/*
 * -File       $Id: XsltTask.php,v 1.26 2003/06/25 16:40:33 purestorm Exp $
 * -License    LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright  2001, Thyrell
 * -Author     Anderas Aderhold, andi@binarycloud.com
 */
// }}}

import('phing.tasks.system.CopyTask');
import('phing.system.io.FileReader');
import('phing.system.io.FileWriter');

/**
 *  Implements an XSLT processing filter while copying files.
 *
 *  @author   Andreas Aderhold, andi@binarycloud.com
 *  @version  $Revision: 1.26 $ $Date: 2003/06/25 16:40:33 $
 *  @package  phing.tasks.system
 */

class XsltTask extends CopyTask {
    var	$_xsltFilter;

    /**
     *
     *
     * @access public
     */
    function XsltTask() {
        parent::CopyTask();

        $fchain = &$this->createFilterchain($this->getProject());
        $this->_xsltFilter = &$fchain->createXsltFilter();
    }

    function Main() {
        $this->Log("Doing XSLT transformation using style \"" . $this->_xsltFilter->GetStyle() . "\"", PROJECT_MSG_VERBOSE);

        parent::Main();
    }

    /**
     *
     *
     * @access public
     */
    function setStyle($style) {
        $this->_xsltFilter->setStyle($style);
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

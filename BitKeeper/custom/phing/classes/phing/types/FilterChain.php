<?php
/*
 * $Id: FilterChain.php,v 1.5 2003/02/24 18:22:16 openface Exp $
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

import('phing.types.DataType');
import('phing.filters.HeadFilter');
import('phing.filters.TailFilter');
import('phing.filters.LineContains');
import('phing.filters.LineContainsRegExp');
import('phing.filters.ExpandProperties');
import('phing.filters.PrefixLines');
import('phing.filters.ReplaceTokens');
import('phing.filters.StripPhpComments');
import('phing.filters.StripLineBreaks');
import('phing.filters.StripLineComments');
import('phing.filters.TabToSpaces');
import('phing.filters.XsltFilter');

/*
 * FilterChain may contain a chained set of filter readers.
 *
 * @author <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @version   $Revision: 1.5 $ $Date: 2003/02/24 18:22:16 $
 * @access    public
 * @package   phing.types
*/
class FilterChain extends DataType {

    var $__p			=	null;
    var $_filterReaders	=	array();

    function FilterChain(&$p) {
        $this->__p =& $p;
    }

    function &getFilterReaders()		{
        return $this->_filterReaders;
    }

    function &createExpandProperties()		{
        return $this->_addFilter(new ExpandProperties());
    }
    function &createHeadFilter()			{
        return $this->_addFilter(new HeadFilter());
    }
    function &createTailFilter()			{
        return $this->_addFilter(new TailFilter());
    }
    function &createLineContains()			{
        return $this->_addFilter(new LineContains());
    }
    function &createLineContainsRegExp()	{
        return $this->_addFilter(new LineContainsRegExp());
    }
    function &createPrefixLines()			{
        return $this->_addFilter(new PrefixLines());
    }
    function &createReplaceTokens()			{
        return $this->_addFilter(new ReplaceTokens());
    }
    function &createStripPhpComments()		{
        return $this->_addFilter(new StripPhpComments());
    }
    function &createStripLineBreaks()		{
        return $this->_addFilter(new StripLineBreaks());
    }
    function &createStripLineComments()		{
        return $this->_addFilter(new StripLineComments());
    }
    function &createTabToSpaces()			{
        return $this->_addFilter(new TabToSpaces());
    }
    function &createXsltFilter()          {
        return $this->_addFilter(new XsltFilter());
    }
    function &createFilterReader()			{
        return $this->_addFilter(new PhingFilterReader());
    }


    /*
     * Makes this instance in effect a reference to another FilterChain 
     * instance.
     *
     * <p>You must not set another attribute or nest elements inside
     * this element if you make it a reference.</p>
     *
     * @param r the reference to which this instance is associated
     * @exception BuildException if this instance already has been configured.
    */
    function setRefid(&$r) {
        if ( count($this->_filterReaders) === 0 ) {
            throw ( $this->tooManyAttributes() );
            return;
        }

        // change this to get the objects from the other reference
        $o = &$r->getReferencedObject($this->getProjec());
        if ( is_a($o, "FilterChain") ) {
            $this->_filterReaders = &$o->getFilterReaders();
        } else {
            throw ( new BuildException($r->getRefId()." doesn\'t refer to a FilterChain") );
        }

        parent::setRefid($r);
    }

    function &_addFilter(&$filter) {
        // FilterChains reference are allowed to have children.

        $num = array_push($this->_filterReaders, $filter);
        return $this->_filterReaders[$num-1];
    }
}

?>

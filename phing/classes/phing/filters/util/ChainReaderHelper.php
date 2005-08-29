<?php
/*
 * $Id: ChainReaderHelper.php,v 1.7 2003/07/09 06:06:39 purestorm Exp $
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

import('phing.Project');
import('phing.filters.BaseFilterReader');
import('phing.types.PhingFilterReader');
import('phing.types.FilterChain');
import('phing.types.Parameter');
import('phing.util.FileUtils');

/**
 * Process a FilterReader chain.
 *
 * Here, the interesting method is 'getAssembledReader'.
 * The purpose of this one is to create a simple Reader object which
 * apply all filters on another primary Reader object. 
 *
 * For example : In copyFile (phing.util.FileUtils) the primary Reader
 * is a FileReader object (more accuratly, a BufferedReader) previously 
 * setted for the source file to copy. So, consider this filterchain :
 *		
 * 	<filterchain>
 *		<stripphpcomments />
 *		<linecontains>
 *			<contains value="foo">
 *		</linecontains>
 *      <tabtospaces tablength="8" />
 *	</filterchain>
 *
 *	getAssembledReader will return a Reader object wich read on each
 *	of these filters. Something like this : ('->' = 'which read data from') :
 *
 *  [TABTOSPACES] -> [LINECONTAINS] -> [STRIPPHPCOMMENTS] -> [FILEREADER]
 *                                                         (primary reader)
 *
 *  So, getAssembledReader will return the TABTOSPACES Reader object. Then
 *  each read done with this Reader object will follow this path.
 *
 *	Hope this explanation is clear :)
 *
 * TODO: Implement the classPath feature.
 *
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @version   $Revision: 1.7 $ $Date: 2003/07/09 06:06:39 $
 * @access    public
 * @package   phing.filters.util
*/
class ChainReaderHelper {

    var	$primaryReader	= null;		// Primary reader to wich the reader chain is to be attached
    var	$bufferSize	    = 8192;		// The site of the buffer to be used.
    var	$filterChains   = array();	// Chain of filters

    var	$_project	    = null;		// The Phing project

    /*
     * Sets the primary reader
    */
    function setPrimaryReader($reader) {
        $this->primaryReader = $reader;
    }

    /*
     * Set the project to work with
    */
    function setProject(&$project) {
        $this->_project = $project;
    }

    /*
     * Get the project
    */
    function &getProject() {
        return $this->_project;
    }

    /*
     * Sets the buffer size to be used.  Defaults to 8192,
     * if this method is not invoked.
    */
    function setBufferSize($size) {
        $this->bufferSize = $size;
    }

    /*
     * Sets the collection of filter reader sets
    */
    function setFilterChains(&$fchain) {
        $this->filterChains = &$fchain;
    }

    /*
     * Assemble the reader
    */
    function &getAssembledReader() {
        $instream = &$this->primaryReader;
        $filterReadersCount = count($this->filterChains);
        $finalFilters = array();

        // Collect all filter readers of all filter chains used ...
        for($i = 0 ; $i<$filterReadersCount ; $i++) {
            $filterchain = &$this->filterChains[$i];
            $filterReaders = $filterchain->getFilterReaders();
            $readerCount = count($filterReaders);
            for($j = 0 ; $j<$readerCount ; $j++)
                $finalFilters[] = $filterReaders[$j];
        }

        // ... then chain the filter readers.
        $filtersCount = count($finalFilters);
        if ( $filtersCount > 0 ) {
            for($i = 0 ; $i<$filtersCount ; $i++) {
                $o = $finalFilters[$i];

                if ( is_a($o, "PhingFilterReader") ) {
                    // This filter reader is an external class.
                    // TODO: Implement classPath feature.

                    $filter = $finalFilters[$i];
                    $className = $filter->getClassName();
                    $classpath = $filter->getClassPath();
                    $project   =& $filter->getProject();
                    if ( $className !== null ) {
                        $clazz = null;
                        if ( $classpath === null ) {
                            import($className);
                            // Perhaps should be nice to have a function for that ?
                            $lastDot = strLastIndexOf(".", $className);
                            $imp = substring($className, $lastDot+1);
                            $clazz = new $imp();
                        } else {
                            import($className);
                            $clazz = new $className;
                        }
                    }

                    if ( $clazz !== null ) {
                        if ( !is_a($clazz, "FilterReader") )
                            throw( new BuildException($className." does not extend phing.io.FilterReader") );
                        $clazz->setReader($instream);
                        $clazz->setProject($this->getProject());
                        $instream = $clazz;
                        if ( is_a($clazz, "BaseParamFilterReader") ) {
                            $instream->setParameters($filter->getParams());
                        }
                    }
                } else if ( method_exists($o, "chain") && is_a($o, "Reader") ) {
                    // This filter reader is an internal.

                    $rdr =& $o->chain($instream);
                    if ( $this->_project !== null && is_a($o, "BaseFilterReader") ) {
                        $rdr->setProject($this->_project);
                    }
                    $instream =& $rdr;
                }
            }
        }

        return $instream;
    }

    /*
     * Read data from the reader and return the
     * contents as a string.
    */
    function readFully($reader) {
        return FileUtils::readFully($reader, $this->bufferSize);
    }

}

?>

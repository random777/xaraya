<?php
/*
 * $Id: Parameter.php,v 1.4 2003/04/07 15:52:37 purestorm Exp $
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

/*
 * A parameter is composed of a name, type and value. Nested
 * Parameters are also possible, but the using task/type has
 * to support them
 *
 * @author    Manuel Holtgrewe
 * @author    <a href="mailto:yl@seasonfive.com">Yannick Lecaillez</a>
 * @package   phing.types
*/
class Parameter extends DataType {

    var $name  = null;
    var $type  = null;
    var $value = null;
    var $parameters = array();

    function setName($name) {
        $this->name = (string) $name;
    }

    function setType($type) {
        $this->type = (string) $type;
    }

    function setValue($value) {
        $this->value = (string) $value;
    }

    function getName() {
        return $this->name;
    }

    function getType() {
        return $this->type;
    }

    function getValue() {
        return $this->value;
    }

    function &createParam() {
        $num = array_push($this->parameters, new Parameter());
        return $this->parameters[$num-1];
    }

    function getParams() {
        return $this->parameters;
    }
}

?>

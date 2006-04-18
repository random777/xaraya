<?php
/**
 * Sequence adapter
 * 
 * A class which helps others classes to implement
 * specialized sequence behaviour by letting them
 * implement their interface in terms of the sequence
 * interface. (exposed protected in this class)
 */ 
class SequenceAdapter implements iAdapter, iSequenceAdapter 
{
    // Who does the actual work?
    private $implementor;
    // iAdapter implementation
    // Our children have nothing to say, we do the construction
    final public function __construct($type = 'array', $args = array())
    {
        switch($type) {
        case 'array':
            // Sequence stored as plain array, volatile
            $classfile = 'array_sequence.php';
            $class='ArraySequence';
            break;
        case 'dd':
            // Sequence stored in dd object, persistent
            $classfile = 'dd_sequence.php';
            $class= 'DynamicDataSequence';
            break;
        default:
            throw new Exception("Sequence type $type is not supported");
        }
        include_once dirname(__FILE__).'/'.$classfile;
        $this->implementor = new $class($args);
    }

    // iSequenceAdapter implementation

    // I want to have this protected but php wont let me
    public function __get($property) 
    {
        switch($property) {
        case 'size':
            return $this->implementor->size;
        case 'empty': // TODO: this is traditionally a method, should we?
            return $this->implementor->empty;
        case 'head':
            return $this->implementor->head;
        case 'tail':
            return $this->implementor->tail;
        default:
            throw new Exception("Property $property does not exist");
        }
    }
    // The actual implementor handles the implementation details,
    protected function &get($position) 
    {    
        $item = $this->implementor->get($position); 
        return $item;
    }
    protected function insert(&$item, $position) 
    { 
        return $this->implementor->insert($item, $position);
    }
    protected function delete($position)
    { 
        return $this->implementor->delete($position);
    }
    protected function clear() 
    {  
        return $this->implementor->clear(); 
    } 
}
?>
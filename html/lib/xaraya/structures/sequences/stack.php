<?php
sys::import('xaraya.structures.sequences.interfaces');
sys::import('xaraya.structures.sequences.adapters.sequence_adapter');

/**
 * A stack manipulates only the item at the head of the sequence
 *
 */
class Stack extends SequenceAdapter implements iStack
{
    public function push($item)
    {
        return $this->insert($item, $this->head);
    }

    public function &pop()
    {
        $item = null;
        if($this->empty) return $item;
        $item = parent::get($this->head);
        if($item == null) return $item;
        parent::delete($this->head);
        return $item;
    }
    
    public function clear()
    {
        parent::clear();
    }

    public function peek()
    {
        $item = $this->pop();
        $this->push($item);
        return $item;
    }
}
?>

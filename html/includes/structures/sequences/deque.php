<?php

include_once dirname(__FILE__).'/interfaces.php';
include_once dirname(__FILE__).'/adapters/sequence_adapter.php';

// A deque can be manipulated at both ends

/**
 * Implementation of the Deque datastructure
 *
 */
class Deque extends SequenceAdapter implements iDeque
{
    // Push an item into the Deque, head or tail
    public function push(&$item, $whichEnd) 
    {
        return parent::insert($item,$whichEnd);
    }

    // Pop an item off the Deque, head or tail
    public function &pop($whichEnd)
    {
        $item = parent::get($whichEnd);
        if($item == null) return $item;
        parent::delete($whichEnd);
        return $item;
    }

    public function clear()
    {
        return parent::clear();
    }
}
?>
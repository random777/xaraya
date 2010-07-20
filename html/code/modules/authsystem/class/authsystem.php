<?php
sys::import('xaraya.structures.descriptor');
/**
* Model for all authsystem subjects (events)
**/
class Authsystem extends ObjectDescriptor implements SplSubject
{
    /**
    * An array of SplObserver objects to notify.
    *
    * @var array
    */ 
    protected $observers = array();

    /**
    * Constructor method
    *
    * @param $args array of arguments to make available to observers
    * @return void
    */
    public function __construct(Array $args=array())
    {
        parent::__construct($args);
        self::refresh($this);
    }  

    /**
    * Attaches an SplObserver
    *
    * @param SplObserver        The observer to attach
    * @return void
    */
    public function attach(SplObserver $obs)
    {
        $id = spl_object_hash($obs);
        $this->observers[$id] = $obs;
    }

    /**
    * Detaches the SplObserver
    *
    * @param SplObserver        The observer to detach
    * @return void
    */
    public function detach(SplObserver $obs)
    {
        $id = spl_object_hash($obs);
        unset($this->observers[$id]);
    }

    /**
    * Notify all observers
    *
    * @return void
    */
    public function notify()
    {
        foreach($this->observers as $obs)
        {
            $obs->update($this);
        }
    }
    /**
    * Refresh property values from __constructor($args)
    *
    * @return void
    */
    public function refresh(Object $object)
    {
        $publicproperties = $object->getPublicProperties();
        foreach ($this->args as $key => $value) 
            if (in_array($key,$publicproperties) || array_key_exists($key,$publicproperties)) $object->$key = $value;
        //else echo $key ."<br />";  // temporary for debugging
    }
    public function getInfo()
    {
        return $this->getPublicProperties();
    }

}
?>
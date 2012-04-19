<?php
/** 
 * PostDispatch Subject
 *
**/
sys::import('xaraya.structures.events.subject');
class ThemesPostDispatchSubject extends EventSubject implements ixarEventSubject
{
    protected $subject = 'PostDispatch';   // name of this event subject
}
?>
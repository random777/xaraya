<?php
// {{{ Header
/*
 * -File		$Id: Signal.php,v 1.3 2003/04/09 15:58:10 thyrell Exp $
 * -License		LGPL (http://www.gnu.org/copyleft/lesser.html)
 * -Copyright	2001, original author
 * -Author		don't know the original author
 * -Author		Anderas Aderhold, andi@binarycloud.com
 */
// }}}

/* $Id: Signal.php,v 1.3 2003/04/09 15:58:10 thyrell Exp $ */
/* TODO: Add additional error handling/reporting */

/* Slots and Signals
 * =================
 * This code is a simple implementation of the slots/signals concept.
 * Slots and signals are used by the Qt toolkit as a kind of extra-powerful 
 * callback. The best source of information on them is the Qt toolkit docs
 * see (http://doc.trolltech.com/3.0/signalsandslots.html)
 *
 * To briefly summarize, they allow you to bind a signal to one or more 
 * slots and/or signals. When a signal is 'emitted', all slots and signals 
 * bound to it are called. Each slot corresponds to a single function. 
 * When a signal is called by another signal, the called signal is emitted,
 * calling the slots and signals that it is bound to.
 *
 * Any parameters passed when the signal is emitted are passed to the 
 * slots and signals that that are called. This easily allows you to write 
 * handler functions (slots) and bind them to events (signals) as needed - 
 * without having to make explicit function calls or rewrite handler functions
 * just to accomodate minor modifications in how a function is called.
 *
 * I find slots and signals particular useful for message passing and error 
 * handling.
 *
 * Have Fun! :)
 *
 */

// Request or define a slot (one arg == request, two == define)
// name of slot and, The handler to bind to a slot when defining it (optional)

function slot($name, $handler = NULL) {
    /* A static array containing all defined slots */
    static $slots = array ();

    /* if the function is called with 1 argument */
    if (is_null ($handler)) {
        /* and the slot name requested is defined */
        if (isset ($slots[$name]))
            /* Return the slot, each slot is an array containing 3 elements */
            return array(
                       'type' => 'slot',
                       'name' => $name,
                       'handler' => $slots[$name]
                   );
        /* If the slot name requested is not defined, exit */
        return FALSE;
    }

    /* If the function has been called with 2 arguments */
    /* Ensure that the function named in $handler is defined */
    if (!function_exists($handler)) {
        trigger_error ("Cannot create slot '$name'. Function '$handler' is not defined.");
        return FALSE;
    }

    /* Define the slot */
    $slots[(string) $name] = (string) $handler;
    return array (
               'type' => 'slot',
               'name' => $name,
               'handler' => $slots[$name]
           );
}
/* Return a signal (and define if needed) */
/* The name of the signal to define (and return) */
function signal($_name) {
    /* A static array containing all signals */
    static $signals = array ();
    /* If the called signal has not been defined, define it */
    if (!isset($signals[$_name])) {
        /* signals can easily be numeric values, allowing for numeric constants */
        $signals[$_name] = true;
    }
    return array (
               'type' => 'signal',
               'name' => $_name
           );
}

# Bind a signal to a slot or another signal signal (or list the bindings for a signal)
# The signal to bind/unbind - should be a call to signal()
# The slot/signal to bind to the signal (optional)
function signal_bind($signal, $slot_or_signal = NULL) {
    /* A static array containing all the signal to signal/slot bindings */
    static $bindings = array ();

    // If called with one argument
    if (is_null($slot_or_signal)) {
        if (is_array($signal) && 'signal' == $signal['type']) {
            $signal = $signal['name'];
        }
        if (isset($bindings[$signal])) {
            /* Return an array of signals and/or slots bound to $signal */
            return $bindings[$signal];
        }
        /* Return an empty array if nothing is bound to $signal */
        return array ();
    }

    /* If $signal is not a valid signal */
    if (!is_array($signal) || 'signal' != $signal['type']
            || ! is_array ($slot_or_signal)) {
        /* If $slot_or_signal can not be a valid signal or slot */
        return FALSE;
    }

    /* Bind $slot_or_signal to $signal */
    $bindings[$signal['name']][] = $slot_or_signal;
    return TRUE;
}

/* Call all the slots/signals bound to a signal */
// The signal to throw
function emit($signal) {
    /* Get the arguments passed with the signal */
    $args = func_get_args ();
    /* Strip off the first argument - it contains the signal ($signal) */
    array_shift($args);

    /* Call every signal or slot bound to $signal */
    foreach(signal_bind($signal) as $slot_or_signal) {
        /* If the current bound item is a signal */
        if ('signal' == $slot_or_signal['type']) {
            /* Pass the arguments to the signal called */
            array_unshift ($args, $slot_or_signal['name']);
            call_user_func_array('emit', $args);
            continue;
        }
        /* If the current bound item is not a signal */
        call_user_func_array($slot_or_signal['handler'], $args);
    }
}
?>
<?

/*  Basic usage examples
    Uncomment to perform some simple tests
*/
function bar () {
    $args = func_get_args ();
    echo implode ('::', $args), "\n";

}

slot('argent', create_function ('', 'echo "Silver\n";'));

#signal_bind(signal('foo'), slot('bar', 'bar'));
#signal_bind(signal('foo'), slot('bar'));
#signal_bind(signal('far'), signal('foo'));
signal_bind(signal('far'), slot('argent'));
#emit('foo', 'a', 1, 2);
#emit('foo', 2, 1);
#emit('far', 'test', 'test2');
#emit('lar', 'loo');

emit('far');
?>
<?
/*
 * Local Variables:
 * mode: php
 * tab-width: 4
 * c-basic-offset: 4
 * End:
 */
?>

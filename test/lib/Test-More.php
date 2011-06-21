<?php
/*
    Procedural interface wrapper for Test-More-OO.php.
    See Test-More-OO.php for documentation.
*/

require_once('Test-More-OO.php');

global $__Test;
$__Test = new TestMore();

// Expose public API for TestMore methods as functions
function plan()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'plan'),$args); }
function ok()           { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'ok'),$args); }
function diag()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'diag'),$args); }
function web_output()   { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'web_output'),$args); }
function done_testing() { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'done_testing'),$args); }
function bail()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'bail'),$args); }
function pass()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'pass'),$args); }
function fail()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'fail'),$args); }
function is()           { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'is'),$args); }
function isnt()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'isnt'),$args); }
function like()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'like'),$args); }
function unlike()       { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'unlike'),$args); }
function cmp_ok()       { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'cmp_ok'),$args); }
function can_ok()       { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'can_ok'),$args); }
function isa_ok()       { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'isa_ok'),$args); }
function include_ok()   { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'include_ok'),$args); }
function require_ok()   { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'require_ok'),$args); }
function skip()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'skip'),$args); }
function eq_array()     { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'eq_array'),$args); }
function eq_hash()      { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'eq_hash'),$args); }
function eq_set()       { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'eq_set'),$args); }
function is_deeply()    { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'is_deeply'),$args); }
function isnt_deeply()  { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'isnt_deeply'),$args); }
function todo()         { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'todo'),$args); }
function todo_skip()    { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'todo_skip'),$args); }
function todo_start()   { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'todo_start'),$args); }
function todo_end()     { global $__Test; $args = func_get_args(); return call_user_func_array(array($__Test,'todo_end'),$args); }

?>

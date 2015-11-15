<?php
/**
 * Created by PhpStorm.
 * User: ershov-ilya
 * Website: ershov.pw
 * GitHub : https://github.com/ershov-ilya
 * Date: 15.11.2015
 * Time: 17:18
 */

header( 'Content-Type: text/plain; charset=utf-8' ) ;

class Foo {
    public function __toString() {
        return 'foo';
    }

    public function __toArray() {
        return array('foo'=>'bar');
    }
    public function __invoke($str) {
        return "invoke ".$str;
    }
}

$foo = new Foo;

var_dump((string)$foo);
print_r((array)$foo);
var_dump($foo('test str'));
die("Done\n");
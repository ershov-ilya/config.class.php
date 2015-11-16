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
define('DEBUG' , true) ;
defined( 'DEBUG') or define('DEBUG' , false) ;

class Config {
    private $data;
    private $source;

    function __construct($source=NULL, $preload=true){
        $sourcetype=gettype($source);
        if(DEBUG) print 'sourcetype: '.$sourcetype.PHP_EOL;

        switch($sourcetype){
            case "NULL":
                $this->data=array();
                break;
            case "string":
                $this->source='file';
                $this->data=array();
                break;
            case "array":
                $this->source='db';
                $this->data=array();
                break;
            case "object":
                $this->source='db';
                $this->data=array();
                break;
        }

    }

//    public function __toString() {
//        return 'foo';
//    }

    public function __invoke($param, $required=false, $default='') {
        return "invoke ".$param;
    }
}

try {
    $config = new Config();
} catch (Exception $e) {
    echo $e->getMessage();
}

var_dump($config('test str'));
die("Done\n");

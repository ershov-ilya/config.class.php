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
    private $connection;
    private $db;

    function __construct($source=null, $props=array()){
        $config=array(
            'preload'   =>  true
        );
        $config=array_merge($config, $props);

        $sourcetype=gettype($source);
        if(DEBUG) print 'sourcetype: '.$sourcetype.PHP_EOL;

        $this->data=array();
        $this->source=null;
        switch($sourcetype){
            case "string":
                if(is_file($source)) {
                    $this->source = 'file';
                    $content=file($source);
                    foreach($content as $str){
                        $str=rtrim($str);
                        $arr=explode(' ',$str,2);
                        $this->data[$arr[0]]=$arr[1];
                    }
                }
                break;
            case "array":
                $this->source='db';
                break;
            case "object":
                $this->source='db';
                break;
        }

    }

    function all(){
        return $this->data;
    }

//    public function __toString() {
//        return 'foo';
//    }

    public function __invoke($param, $required=false, $default='') {
        return "invoke ".$param;
    }
}

try {
    $config = new Config('test.config');
} catch (Exception $e) {
    echo $e->getMessage();
}

print_r($config->all());

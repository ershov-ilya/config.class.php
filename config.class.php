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
    private $config;
    private $source;
    private $connection;
    private $pdoconfig;
    private $dbh;

    function __construct($source=null, $props=array()){
        $config=array(
            'preload'   =>  true,
            'table'     =>  'config',
            'keyfield'  =>  'key',
            'valuefield'  =>  'value'
        );
        $this->config=$config=array_merge($config, $props);

        $this->data=array();
        $this->source=null;
        $this->pdoconfig=null;
        $this->connection=false;

        $sourcetype=gettype($source);
        if(DEBUG) print 'sourcetype: '.$sourcetype.PHP_EOL;

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
                $this->pdoconfig=$source;
                if($config['preload']) {
                    $this->connect();
                    if($this->connection){
                        $sql = "SELECT * FROM `".$config['table']."`";
                        $stmt = $this->dbh->query($sql);
                        $stmt->setFetchMode(PDO::FETCH_ASSOC);
                        $rows = $stmt->fetchAll();
                        if(isset($rows[0][$config['keyfield']])) {
                            foreach ($rows as $row) {
                                $k = $row[$config['keyfield']];
                                unset($row[$config['keyfield']]);
                                $this->data[$k]=$row;
                            }
                        } else return;
                    }
                }

                $this->source='db';
                break;
            case "object":
                $this->source='db';
                break;
        }

        if(DEBUG) print 'source: '.$this->source.PHP_EOL;
    }

    private function connect(){
        extract($this->pdoconfig);
        if(!(isset($dbtype) && isset($dbhost) && isset($dbname) && isset($dbuser) && isset($dbpass))){
            break;
        }
        try
        {
            /* @var PDO $DBH */
            // Save stream
            $this->dbh = $DBH = new PDO("$dbtype:host=$dbhost;dbname=$dbname" , $dbuser, $dbpass,
                array (PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
            );
        }
        catch (PDOException $e ) {
            if(DEBUG) print 'Exception: ' . $e-> getMessage();
            if(function_exists('logMessage')) logMessage('Exception: ' . $e-> getMessage());
            $this->dbh = null;
            return false;
        }
        $this->connection=true;
        return true;
    }

    function all(){
        return $this->data;
    }

    public function set($key, $value){
        $this->data[$key]=$value;
    }

    public function get($key, $field=null){
        if(empty($field)) $field=$this->config['valuefield'];
        return $this->data[$key][$field];
    }

    public function __invoke($key, $required=false, $default=null) {
        if(isset($this->data[$key])) {
            if($this->source=='db') return $this->data[$key][$this->config['valuefield']];
            return $this->data[$key];
        }
        else return $default;
    }

}

require_once('pdo.config.private.php');
try {
    $config = new Config($pdoconfig);
} catch (Exception $e) {
    echo $e->getMessage();
}

print_r($config->all());
var_dump($config('report_sms_phone'));

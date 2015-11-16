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

    function __construct(&$source=null, $props=array()){
        $config=array(
            'preload'       =>  true,
            'table'         =>  'config',
            'keyfield'      =>  'key',
            'valuefield'    =>  'value'
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
                $this->source='direct';
                break;
            case "object":
                if(class_exists('Database')){
                    if($source instanceof Database) {
                        $this->dbh = &$source->dbh;
                        $this->source='Database';
                        $this->connection=true;
                    }
                }
                if(class_exists('PDO')){
                    if($source instanceof PDO) {
                        $this->dbh = &$source;
                        $this->source='PDO';
                        $this->connection=true;
                    }
                }
                break;
        }

        if($config['preload']) {
            if(!$this->connection) $this->connect();
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

        if(DEBUG) print 'source: '.$this->source.PHP_EOL;
    }

    function __destruct() {
        $this->dbh = null;
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
            $this->connection=false;
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
        $config=$this->config;
        if(empty($field)) $field=$config['valuefield'];
        if(!$this->config['preload']){
            if(empty($this->data[$key][$field])){
                if(!$this->connection) $this->connect();
                if($this->connection){
                    $sql = "SELECT * FROM `".$config['table']."` WHERE `".$config['keyfield']."`='".$key."'";
                    $stmt = $this->dbh->query($sql);
                    $stmt->setFetchMode(PDO::FETCH_ASSOC);
                    $rows = $stmt->fetchAll();
                    $row=$rows[0];
                    if(isset($row[$config['keyfield']])) {
                        $k = $row[$config['keyfield']];
                        unset($row[$config['keyfield']]);
                        $this->data[$k]=$row;
                    }
                    else
                        return '';
                }
            }
        }
        return $this->data[$key][$field];
    }

    public function __invoke($key, $required=false, $default=null) {
        if($this->source=='db') return $this->get($key);
        return $this->data[$key];
    }

    public function __toString(){
        return ($this->connection)?'соединение с БД открыто':'нет соединения с БД';
    }
}

require_once('pdo.config.private.php');
require_once('../database/database.class.php');
$db=new Database($pdoconfig);
try {
    $config = new Config($pdoconfig);
} catch (Exception $e) {
    echo $e->getMessage();
}

// DEBUG
print $config.PHP_EOL;
var_dump($config('report_sms_phone'));
print $config.PHP_EOL;
print_r($config->all());

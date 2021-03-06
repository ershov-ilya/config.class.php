<?php
/**
 * Created by PhpStorm.
 * User: ershov-ilya
 * Website: ershov.pw
 * GitHub : https://github.com/ershov-ilya
 * Date: 15.11.2015
 * Time: 17:18
 */

//header( 'Content-Type: text/plain; charset=utf-8' ) ;
//define('DEBUG' , true) ;
defined( 'DEBUG') or define('DEBUG' , false) ;

class Config {
    private $data;
    private $config;
    private $source;
    private $connection;
    private $pdoconfig;
    private $dbh;

    /**
     * @param pointer $source
     * @param array $props
     */
    function __construct(&$source=null, $props=array()){
        $config=array(
            'preload'       =>  true,
            'table'         =>  'config',
            'keyfield'      =>  'key',
            'valuefield'    =>  'value',
            'file_delimiter'     =>  ' ',
            'file_strip_first_line'  =>  false
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
                    if($config['file_strip_first_line']) unset($content[0]);
                    foreach($content as $str){
                        $str=rtrim($str);
                        $arr=explode($config['file_delimiter'],$str,2);
                        $this->data[$arr[0]]=$arr[1];
                    }
                }
                break;
            case "array":
                $this->pdoconfig=$source;
                $this->source='pdoconfig';
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
        if($this->source != 'pdoconfig') return false;
        extract($this->pdoconfig);
        if(!(isset($dbtype) && isset($dbhost) && isset($dbname) && isset($dbuser) && isset($dbpass))){
            return false;
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
        $config=$this->config;
        $this->data[$key]=$value;
        if(!$this->connection) $this->connect();
        /*
         * INSERT INTO table (id,Col1,Col2) VALUES (1,1,1),(2,2,3),(3,9,3),(4,10,12)
         * ON DUPLICATE KEY UPDATE Col1=VALUES(Col1),Col2=VALUES(Col2);
         */
        if($this->connection) {
            $sql = "INSERT INTO `" . $config['table'] . "` (`" . $config['keyfield'] . "`,`" . $config['valuefield'] . "`) VALUES (:key,:value) ON DUPLICATE KEY UPDATE " . $config['valuefield'] . "=VALUES(`" . $config['valuefield'] . "`)";

            $stmt = $this->dbh->prepare($sql);
            $stmt->bindParam(':key', $key);
            $stmt->bindParam(':value', $value);
            $res = $stmt->execute();
            if (empty($res)) {
                if (DEBUG) {
                    print "ERROR:\n";
                    print_r($stmt->errorInfo());
                }
                return false;
            }
            return true;
        }
        return false;
    }

    public function get($key, $field=null){
        $config=$this->config;
        if(empty($field)) $field=$config['valuefield'];
        if(!$config['preload']){
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
        if(isset($this->data[$key][$field])) {
            $value=$this->data[$key][$field];
            switch($value){
                case '0':
                    $value=0;
                    break;
                case '1':
                    $value=1;
                    break;
                case 'true':
                    $value=true;
                    break;
                case 'false':
                    $value=false;
                    break;
            }
            return $value;
        }
        return null;
    }

    public function __invoke($key, $default=null, $save=false) {
        if($this->source=='file') return $this->data[$key];
        $value=$this->get($key);
        if($value === null){
            $value=$default;
            if($save){
                $this->set($key, $default);
            }
        }

        return $value;
    }

    public function __toString(){
        return ($this->connection)?'DB connection is open':'No connection with DB';
    }
}

//require_once('pdo.config.private.php');
//require_once('../database/database.class.php');
//$db=new Database($pdoconfig);
//
//try {
//    $file='test.config';
//    $config = new Config($db);
//} catch (Exception $e) {
//    echo $e->getMessage();
//}
//
//// DEBUG
//print $config.PHP_EOL;
//print_r($config->all());
//$config->set('test','nice');
//print "key(report_sms_phone):\n";
//var_dump($config('report_sms_phone'));
//print "key(report_sms_phone2):\n";
//var_dump($config('report_sms_phone2', '+79213309363', true));


<?php
/**
 * mychaelstyle\ProviderMysql
 * @package mychaelstyle
 * @auther Masanori Nakashima
 */
namespace mychaelstyle;
require_once dirname(__FILE__).'/Provider.php';
/**
 * mychaelstyle\ProviderMysql
 * @package mychaelstyle
 * @auther Masanori Nakashima
 */
class ProviderMysql implements Provider {
  /**
   * @var string $database database name.
   */
  protected $database;
  /**
   * @var array $options
   */
  protected $options = array();
  /**
   * @var string dsn master 
   */
  protected $dsn_master;
  /**
   * @var array $dsn_slaves
   */
  protected $dsn_slaves = array();
  /**
   * @var resource pdo master
   */
  protected $pdo_master;
  /**
   * @var resource pdo slave
   */
  protected $pdo_slave;
   /**
   * constructor
   */
  public function __construct(){
    $this->options = array();
  }

	/**
   * connect a local file system.
   * and check the root path.
   * @param string $dsn 'Mysql://[host:port]/[database]/'. e.g. 'Mysql://localhost:3306/foo'
   * @param array $options map
	 * @see Provider::connect()
	 */
	public function connect($uri,$options=array()){
    $elms = explode('/',$uri);
    if(count($elms)<2){
      throw new \mychaelstyle\Exception('provider mysql: invalid dsn! require host:port and database! ',0);
    }
    $this->options = $options;
    $host = $elms[0];
    $host = strpos($host,':')===false ? $host : substr($host,0,strpos($host,':'));
    $port = strpos($host,':')===false ? 3306 : substr($host,strpos($host,':')+1);
    $this->database = $elms[1];
    if(isset($elms[2])){
      $table = null;
      if(strpos($elms[2],'?') !== false){
        list($table,$opts) = explode('?',$elms[2]);
        $this->parseUriParams($opts);
      }
      if(!isset($options['table']) && !is_null($table)){
        $options['table'] = $table;
      }
    }
    $this->dsn_master = 'mysql:dbname='.$this->database.';host='.$host.';port='.$port;

    if(isset($this->options['pdo'])){
      if(is_array($this->options['pdo'])){
        $this->pdo_master = array_shift($this->options['pdo']);
        $k = array_rand($this->options['pdo'],1);
        $this->pdo_slave  = $this->options['pdo'][$k];
      } else {
        $this->pdo_master = $this->options['pdo'];
        $this->pdo_slave  = $this->options['pdo'];
      }
    } else if(!isset($this->options['user'])){
      throw new \mychaelstyle\Exception('provider mysql: invalid options! require user and pass!',0);
    } else {
      $hosts = (isset($options['slaves']) && is_array($options['slaves']))?$options['slaves']:array();
      array_unshift($hosts,$elms[0]);
      foreach($hosts as $host){
        $host = strpos($host,':')===false ? $host : substr($host,0,strpos($host,':'));
        $port = strpos($host,':')===false ? 3306 : substr($host,strpos($host,':')+1);
        $this->dsn_slaves[] = 'mysql:dbname='.$this->database.';host='.$host.';port='.$port;
      }
    }
  }

  /**
   * get pdo connection
   * @param boolean $writable
   */
  protected function getConnection($writable=false){
    if($writable){
      if(is_null($this->pdo_master)){
        $this->pdo_master = $this->__connect($this->dsn_master);
      }
      return $this->pdo_master;
    } else {
      if(is_null($this->pdo_slave)){
        $k = array_rand($this->dsn_slaves,1);
        $dsn = $this->dsn_slaves[$k];
        $this->pdo_slave = $this->__connect($dsn);
      }
      return $this->pdo_slave;
    }
  }

  /**
   * get pdo connection
   */
  private function __connect($dsn){
    $conn = new \PDO($dsn,$this->options['user'], $this->options['pass'], array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8') );
    $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
    $conn->setAttribute(\PDO::ATTR_TIMEOUT, (defined('MYSQL_TIMEOUT') ? MYSQL_TIMEOUT : 5));
    return $conn;
  }

 /**
   * disconnect and reset this object verialbles.
	 * @see Provider::disconnect()
   */
  public function disconnect(){
    $this->options = array();
    $this->database= null;
  }

  /**
   * parse uri parameters
   * @param string parameter strings form encoded
   */
  protected function parseUriParams($param){
    if(!is_null($param)){
      $elms = explode('&',$param);
      $map = array();
      foreach($elms as $elm){
        if(strpos($elm,'=')!==false){
          $mp = explode('=',$elm);
          $key = array_shift($mp);
          if(!isset($this->options[$key])){
            $this->options[$key] = implode('=',$mp);
          }
        }
      }
    }
  }

  /**
   * get pdo param type
   */
  protected function paramType($value){
    if(preg_match('',$value)>0){
      return \PDO::PARAM_INT;
    }
    return \PDO::PARAM_STR;
  }
}

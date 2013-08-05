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
   * @var PDO $connections
   */
  private $connections = array();
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
    if(count($elms)===0){
      throw new \mychaelstyle\Exception('provider mysql: invalid dsn!',0);
    }
    $this->database = $elms[0];
    $this->table = $elms[2];
    if(strpos($this->table,'?')!==false){
      list($this->table,$opts) = explode('?',$this->table);
      $this->parseUriParams($opts);
    }
    $this->options = $options;
    if(isset($this->options['pdo'])){
      if(is_array($this->options['pdo'])){
        $this->connections = array_merge($this->connections,$this->options['pdo']);
      } else {
        $this->connections[0] = $this->options['pdo'];
      }
    } else if(!isset($options['user']) || !isset($options['pass'])){
      throw new \mychaelstyle\Exception('provider mysql: invalid options! require user and pass!',0);
    } else {
      $hosts = (isset($options['slaves']) && is_array($options['slaves']))?$options['slaves']:array();
      array_unshift($hosts,$elms[0]);
      foreach($hosts as $host){
        $host = strpos($host,':')===false ? $host : substr($host,0,strpos($host,':'));
        $port = strpos($host,':')===false ? 3306 : substr($host,strpos($host,':')+1);
        $dsn = 'mysql:dbname='.$this->database.';host='.$host.';port='.$port;
        $conn = new \PDO( $dsn, $options['user'], $options['pass'], array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8') );
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
        $conn->setAttribute(\PDO::ATTR_TIMEOUT, (defined('MYSQL_TIMEOUT') ? MYSQL_TIMEOUT : 5));
        $this->connections[] = $conn;
      }
    }
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
  }
 
}

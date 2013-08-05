<?php
/**
 * mychaelstyle\datastore\providers\Mysql
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\datastore\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
require_once dirname(dirname(dirname(__FILE__))).'/utils/File.php';
/**
 * ファイルをMySQL保存するデータストアプロバイダ
 * 
 * [DSN] Mysql://[database]/[table]
 *
 * e.g. local://home/foo/var
 *
 * [Initialize Options]
 * 'user'   => MySQLの接続ユーザ。
 * 'pass'   => MySQLの接続パスワード。
 * 'slaves' => MySQL read replica host names. 
 *
 * e.g)
 * $options = array(
 *   'user'   => 'hoge',
 *   'pass'   => 'foovar',
 *   'slaves' => array('localhost:3306','slavehost:3306')
 * );
 * $dsn = 'Mysql://localhost:3306/filedb/filetable';
 * $datastore = new mychaelstyle\Storage($dsn,$options);
 * $file = $datastore.createFile('example.txt',$options); 
 * $file->open('w');
 * $file->write("foo\nvar");
 * $file->close();
 *
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
class Mysql implements \mychaelstyle\datastore\Provider {
  /**
   * @var string $database database name.
   */
  private $database;
  /**
   * @var string $table table name.
   */
  private $table;
  /**
   * @var string $field_uri field name for uri.
   */
  private $field_uri = 'uri';
  /**
   * @var string $field_contents field name for the file contents.
   */
  private $field_contents = 'contents';
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
   * @param string $dsn 'Mysql://[host:port]/[database]/[table]'. e.g. 'Mysql://localhost:3306/foo/var'
   * @param array $options map has keys '' and 'folder_permission'. e.g. array('permission'=>0666,'folder_permission'=>0755)
	 * @see Provider::connect()
	 */
	public function connect($uri,$options=array()){
    $elms = explode('/',$uri);
    if(count($elms)<3){
      throw new \mychaelstyle\Exception('File datastore provider mysql: invalid dsn!',0);
    }
    $this->database = $elms[1];
    $this->table = $elms[2];
    if(strpos($this->table,'?')!==false){
      list($this->table,$opts) = explode('?',$this->table);
      $this->perseUriParams($opts);
    }
    $this->options = $options;
    if(isset($this->options['pdo'])){
      if(is_array($this->options['pdo'])){
        $this->connections = array_merge($this->connections,$this->options['pdo']);
      } else {
        $this->connections[0] = $this->options['pdo'];
      }
    } else if(!isset($options['user']) || !isset($options['pass'])){
      throw new \mychaelstyle\Exception('File datastore provider mysql: invalid options! require user and pass!',0);
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
   * perse uri parameters
   * @param string parameter strings form encoded
   */
  private function perseUriParams($param){
    if(!is_null($param)){
      $elms = explode('&',$param);
      $map = array();
      foreach($elms as $elm){
        if(strpos($elm,'=')!==false){
          $mp = explode('=',$elm);
          $key = array_shift($mp);
          $map[$key] = implode('=',$mp);
        }
      }
      if(isset($map['uri']) && strlen($map['uri'])>0){
        $this->field_uri = $map['uri'];
      }
      if(isset($map['contents']) && strlen($map['contents'])>0){
        $this->field_contents = $map['contents'];
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
    $this->table = null;
    $this->field_uri= 'uri';
    $this->field_contents= 'contents';
  }

  private function paramType($value){
    if(preg_match('',$value)>0){
      return \PDO::PARAM_INT;
    }
    return \PDO::PARAM_STR;
  }

  /**
   * batch write datas
   * @param array $datas
   */
  public function batchWrite(array $datas){
    foreach($datas as $table => $rows){
      foreach($rows as $row){
        // use first elment
        $keys = array_keys($row);
        $fields = array();
        $holders = array();
        foreach($row as $f => $v){
          $fields[] = $f;
          $holders[] = ':'.$f;
        }
        $sql = sprintf('INSERT INTO %s (%s)VALUES(%s)',$table,
          implode(',',$fields),implode(',',$holders));
        try {
          $statement = $pdo->prepare($sql);
          foreach($row as $f => $v){
            $statement->bindValue(':'.$f,$v,$this->paramType($v));
          }
          $statement->execute();
          $statement->closeCursor();
        } catch(\Exception $e){
          throw new \mychaelstyle\Exception('Data provider mysql: fail to insert!',0,$e);
        }
      }
    }
  }
  public function batchGet(array $keys){
    $retMap = array();
    foreach($keys as $table => $rows){
      $retMap[$table] = isset($retMap[$table])?$retMap[$table]:array();
      foreach($rows as $row){
        $conds = array();
        foreach($row as $k => $v){
          $conds[] = $k.'=:'.$k;
        }
        $sql = sprintf('SELECT * FROM %s WHERE %s',$table,implode(' AND ',$conds));
        try {
          $statement = $pdo->prepare($sql);
          foreach($row as $f => $v){
            $statement->bindValue(':'.$f,$v,$this->paramType($v));
          }
          $statement->execute();
		      $retMap[$table][] = $statement->fetch(\PDO::FETCH_ASSOC);
          $statement->closeCursor();
        } catch(\Exception $e){
          throw new \mychaelstyle\Exception('Data provider mysql: fail to select!',0,$e);
        }
      }
    }
    return $retMap;
  }
  /**
   * batch remove
   */
  public function batchRemove(array $keys){
    foreach($keys as $table => $rows){
      foreach($rows as $row){
        $conds = array();
        foreach($row as $k => $v){
          $conds[] = $k.'=:'.$k;
        }
        $sql = sprintf('DELETE FROM %s WHERE %s',$table,implode(' AND ',$conds));
        try {
          $statement = $pdo->prepare($sql);
          foreach($row as $f => $v){
            $statement->bindValue(':'.$f,$v,$this->paramType($v));
          }
          $statement->execute();
          $statement->closeCursor();
        } catch(\Exception $e){
          throw new \mychaelstyle\Exception('Data provider mysql: fail to select!',0,$e);
        }
      }
    }
  }
  /**
   * write a record
   */
  public function write($table,array $data){
    $datas = array($table => array($data));
    return $this->batchWrite($datas);
  }
  /**
   * get
   */
  public function get($table,$key){
    $keys = array(
      $table => array(
        $key
      )
    );
    $result = $this->batchGet($keys);
    return (isset($result[$table]) && isset($result[$table][0])) ? $result[$table][0] : null;
  }
  /**
   * remove
   */
  public function remove($table,$key){
    $keys = array(
      $table => array(
        $key
      )
    );
    $this->batchRemove($keys);
  }
}

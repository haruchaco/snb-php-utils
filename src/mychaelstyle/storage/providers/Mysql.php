<?php
/**
 * mychaelstyle\storage\providers\Mysql
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\storage\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
require_once dirname(dirname(dirname(__FILE__))).'/utils/File.php';
/**
 * ファイルをMySQL保存するストレージプロバイダ
 * 
 * [DSN] mysql://[database]/[table]
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
 * $dsn = 'mysql://localhost:3306/filedb/filetable';
 * $storage = new mychaelstyle\Storage($dsn,$options);
 * $file = $storage.createFile('example.txt',$options); 
 * $file->open('w');
 * $file->write("foo\nvar");
 * $file->close();
 *
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
class Mysql implements \mychaelstyle\storage\Provider {
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
      throw new \mychaelstyle\Exception('File storage provider mysql: invalid dsn!',0);
    }
    $this->database = $elms[1];
    $this->table = $elms[2];
    if(strpos($this->table,'?')!==false){
      list($this->table,$opts) = explode('?',$this->table);
      $ops = explode('&',$opts);
      if(is_array($ops) && count($ops)>0){
        foreach($ops as $k => $v){
          $fn = 'field_'.$k;
          $this->$fn = $v;
        }
      }
    }
    $this->options = $options;
    if(isset($this->options['pdo'])){
      if(is_array($this->options['pdo'])){
        $this->connections = array_merge($this->connections,$this->options['pdo']);
      } else {
        $this->connections[0] = $this->options['pdo'];
      }
    } else if(!isset($options['user']) || !isset($options['pass'])){
      throw new \mychaelstyle\Exception('File storage provider mysql: invalid options! require user and pass!',0);
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
   * parse uri parameters
   * @param string parameter strings form encoded
   */
  private function parseUriParams($param){
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

  /**
   * get contents from uri
   */
  public function get($uri,$path=null){
    $uri = $this->__formatUri($uri);
    $pdo = $this->connections[array_rand($this->connections)];
    $sql = sprintf('SELECT %s FROM %s WHERE %s=:uri',$this->field_contents,$this->table,$this->field_uri);
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':uri',$uri,\PDO::PARAM_STR);
    $statement->execute();
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
    $tmp = tempnam(sys_get_temp_dir(),'tmp_mychaelstyle_storage_mysql_');
    if(file_put_contents($tmp,$result['contents'])){
      if(is_null($path)){
        $decodedPath = tempnam(sys_get_temp_dir(),'tmp_mychaelstyle_storage_mysql_');
        \mychaelstyle\utils\File::base64decode($tmp,$decodedPath);
        $ret = file_get_contents($decodedPath);
        @unlink($tmp);
        @unlink($decodedPath);
        return $ret;
      } else {
        \mychaelstyle\utils\File::base64decode($tmp,$path);
        @unlink($tmp);
        return true;
      }
    }
    throw new \mychaelstyle\Exception('File storage provider mysql: fail to open temporary file!'.$tmp,0);
  }

	/**
	 * put file
	 * @param string $srcPath
	 * @param string $dstUri
   * @param array $options
	 */
	public function put($srcPath,$dstUri,$options=array()){
    $dstUri = $this->__formatUri($dstUri);
    $pdo = $this->connections[0];
    $this->remove($dstUri);
    $tmp = tempnam(sys_get_temp_dir(),'tmp_mychaelstyle_storage_mysql_');
    \mychaelstyle\utils\File::base64encode($srcPath,$tmp);
    $contents = file_get_contents($tmp);
    try {
      $sql = sprintf('INSERT INTO %s (%s,%s)VALUES(:uri,:contents)',$this->table,$this->field_uri,$this->field_contents);
      $statement = $pdo->prepare($sql);
      $statement->bindValue(':uri',$dstUri,\PDO::PARAM_STR);
      $statement->bindValue(':contents',$contents,\PDO::PARAM_STR);
      $statement->execute();
      @unlink($tmp);
    } catch(\Exception $e){
      @unlink($tmp);
      throw new \mychaelstyle\Exception('File provider mysql: fail to select file!',0,$e);
    }
  }

	/**
	 * remove file or folder
	 * @param string $dstUri
	 * @param boolean $recursive
	 */
	public function remove($dstUri,$recursive=false){
    $dstUri = $this->__formatUri($dstUri);
    $pdo = $this->connections[0];
    $sql = sprintf('DELETE FROM %s WHERE %s=:uri',$this->table,$this->field_uri);
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':uri',$dstUri,\PDO::PARAM_STR);
    $statement->execute();
  }

  /**
   * format uri
   * @param string $uri
   * @return string formated uri
   */
  private function __formatUri($uri){
    if(strpos($uri,'/')===false){
      return '/'.$uri;
    }
    return $uri;
  }
 
}

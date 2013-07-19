<?php
namespace snb\storage\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
require_once dirname(dirname(__FILE__)).'/Exception.php';
/**
 * ファイルをMySQL保存するストレージプロバイダ
 * 
 * [DSN] mysql://[database]/[table]
 *
 * e.g. local:///home/foo/var
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
 * $storage = new snb\Storage($dsn,$options);
 * $file = $storage.createFile('example.txt',$options); 
 * $file->open('w');
 * $file->write("foo\nvar");
 * $file->close();
 *
 * @package snb\storage\providers
 * @autthe Masanori Nakashima
 */
class Mysql extends \snb\storage\Provider {
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
   * @param string $dsn 'local://[local base folder path]'. e.g. 'local:///tmp/foo'
   * @param array $options map has keys 'permission' and 'folder_permission'. e.g. array('permission'=>0666,'folder_permission'=>0755)
	 * @see Provider::connect()
	 */
	public function connect($dsn,$options=array()){
    $this->perseDsn($dsn);
    $this->options = $options;
    $uri = null;
    $param = null;
    $port = '3306';
    if(strpos($this->provider_root,'?')!==false){
      list($uri,$param) = explode('?',$this->provider_root);
    } else {
      $uri = $this->provider_root;
    }
    list($masterHost,$this->database,$this->table) = explode('/',$uri);
    if(strpos($masterHost,':')!==false){
      list($masterHost,$port) = explode(':',$masterHost);
    }
    $this->parseUriParams($param);
    if(isset($this->options['pdo'])){
      if(is_array($this->options['pdo'])){
        $this->connections = array_merge($this->connections,$this->options['pdo']);
      } else {
        $this->connections[0] = $this->options['pdo'];
      }
    } else {
		  $dsn = 'mysql:dbname='.$this->database.';host='.$masterHost.';port='.$port;
      $this->connections[0] = new \PDO( $dsn, $options['user'], $options['pass'], array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8') );
      $this->connections[0]->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
      $this->connections[0]->setAttribute(\PDO::ATTR_TIMEOUT, (defined('MYSQL_TIMEOUT') ? MYSQL_TIMEOUT : 5));
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
    $this->provider_name = null;
    $this->provider_root = null;
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
    $uri = $this->formatUri($uri);
    $pdo = $this->connections[array_rand($this->connections)];
    $sql = sprintf('SELECT %s FROM %s WHERE %s=:uri',$this->field_contents,$this->table,$this->field_uri);
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':uri',$uri,\PDO::PARAM_STR);
    $statement->execute();
		$result = $statement->fetch(\PDO::FETCH_ASSOC);
    $tmp = tempnam(sys_get_temp_dir(),'tmp_snb_storage_mysql_');
    if(file_put_contents($tmp,$result['contents'])){
      if(is_null($path)){
        $decodedPath = tempnam(sys_get_temp_dir(),'tmp_snb_storage_mysql_');
        $this->decode($tmp,$decodedPath);
        $ret = file_get_contents($decodedPath);
        @unlink($tmp);
        @unlink($decodedPath);
        return $ret;
      } else {
        $this->decode($tmp,$path);
        @unlink($tmp);
        return true;
      }
    }
    throw new \snb\storage\Exception('File storage provider mysql: fail to open temporary file!'.$tmp,0);
  }
	/**
	 * put file
	 * @param string $srcPath
	 * @param string $dstUri
   * @param array $options
	 */
	public function put($srcPath,$dstUri,$options=array()){
    $dstUri = $this->formatUri($dstUri);
    $pdo = $this->connections[0];
    $this->remove($dstUri);
    $tmp = tempnam(sys_get_temp_dir(),'tmp_snb_storage_mysql_');
    $this->encode($srcPath,$tmp);
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
      throw new \snb\storage\Exception('File provider mysql: fail to select file!',0,$e);
    }
  }
	/**
	 * remove file or folder
	 * @param string $dstUri
	 * @param boolean $recursive
	 */
	public function remove($dstUri,$recursive=false){
    $dstUri = $this->formatUri($dstUri);
    $pdo = $this->connections[0];
    $sql = sprintf('DELETE FROM %s WHERE %s=:uri',$this->table,$this->field_uri);
    $statement = $pdo->prepare($sql);
    $statement->bindValue(':uri',$dstUri,\PDO::PARAM_STR);
    $statement->execute();
  }
}

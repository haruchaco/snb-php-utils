<?php
/**
 * mychaelstyle\storage\providers\Mysql
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\storage\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
require_once dirname(dirname(dirname(__FILE__))).'/ProviderMysql.php';
require_once dirname(dirname(dirname(__FILE__))).'/utils/File.php';
/**
 * ファイルをMySQL保存するストレージプロバイダ
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
class Mysql extends \mychaelstyle\ProviderMysql implements \mychaelstyle\storage\Provider {
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
    $statement->closeCursor();
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
      $statement->closeCursor();
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
    $statement->closeCursor();
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

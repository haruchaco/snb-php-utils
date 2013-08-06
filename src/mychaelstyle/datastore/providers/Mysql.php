<?php
/**
 * mychaelstyle\datastore\providers\Mysql
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\datastore\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
require_once dirname(dirname(dirname(__FILE__))).'/ProviderMysql.php';
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
class Mysql extends \mychaelstyle\ProviderMysql implements \mychaelstyle\datastore\Provider {
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
   * constructor
   */
  public function __construct(){
    parent::__construct();
  }

	/**
   * connect a local file system.
   * and check the root path.
   * @param string $dsn 'Mysql://[host:port]/[database]/[table]'. e.g. 'Mysql://localhost:3306/foo/var'
   * @param array $options map has keys '' and 'folder_permission'. e.g. array('permission'=>0666,'folder_permission'=>0755)
	 * @see Provider::connect()
	 */
	public function connect($uri,$options=array()){
    parent::connect($uri,$options);
    $this->table = $this->options['table'];
    $this->field_uri = isset($this->options['uri']) ? $this->options['uri']: 'uri';
    $this->field_contents = isset($this->options['contents']) ? $this->options['contents']: 'contents';
  }

  /**
   * disconnect and reset this object verialbles.
	 * @see Provider::disconnect()
   */
  public function disconnect(){
    parent::disconnect();
    $this->table = null;
    $this->field_uri= 'uri';
    $this->field_contents= 'contents';
  }

  /**
   * batch write datas
   * @param array $datas
   */
  public function batchWrite(array $datas){
    $pdo = $this->getConnection(true);
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
    $pdo = $this->getConnection();
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
    $pdo = $this->getConnection(true);
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

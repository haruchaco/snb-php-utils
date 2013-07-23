<?php
/**
 * mychaelstyle\providers\Local
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\storage\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * ファイルをローカルファイルシステムに保存するストレージプロバイダ
 * 
 * [DSN] local://[directory path]
 *
 * e.g. local:///home/foo/var
 *
 * [Initialize Options]
 * 'permission' => 8進数 Unix形式で指定。
 * 'folder_permission' =>  8進数 Unix形式で指定。
 *
 * [File options]
 * 'permission' => 8進数 Unix形式で指定。
 * 'folder_permission' =>  8進数 Unix形式で指定。
 * 
 * e.g)
 * $options = array(
 *   'permission' => 0644
 *   'folder_permission' => 0755
 * );
 * $dsn = 'local:///home/foo/var';
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
class Local extends \mychaelstyle\storage\Provider {
  /**
   * @var string base path to save files
   */
  private $base_path = null;
  /**
   * constructor
   */
  public function __construct(){
    $this->options = array();
    $this->base_path = null;
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
    // check the folder permision
    if(!is_dir($this->provider_root)){
      throw new \mychaelstyle\Exception('The base path is not a directory! '.$this->provider_root,
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
    }
    $this->base_path = preg_replace('/\/$/','',$this->provider_root);
    $this->options = $options;
    if(!isset($this->options['permission'])){
      $this->options['permission'] = 0644;
    }
    if(!isset($this->options['folder_permission'])){
      $this->options['folder_permission'] = 0755;
    }
  }
  /**
   * disconnect and reset this object verialbles.
	 * @see Provider::disconnect()
   */
  public function disconnect(){
    $this->provider_name = null;
    $this->provider_root = null;
    $this->base_path = null;
    $this->options = array();
  }
  /**
   * get contents from uri
   */
  public function get($uri,$path=null){
    $filePath = $this->getRealPath($uri);
    if(file_exists($filePath)){
      if(!is_null($path)){
        return @copy($filePath,$path);
      } else {
        return file_get_contents($filePath);
      }
    } else {
      return null;
    }
  }
	/**
	 * put file
	 * @param string $srcPath
	 * @param string $dstUri
   * @param array $options
	 */
	public function put($srcPath,$dstUri,$options=array()){
    clearstatcache();
    $options = array_merge($this->options,$options);
    $filePath = $this->getRealPath($dstUri);
    $dirPath  = dirname($filePath);
    if(!is_dir($dirPath) && !@mkdir($dirPath,$options['folder_permission'],true)){
      throw new \mychaelstyle\Exception('Fail to mkdir! '.$dirPath,
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
    }
    if(@copy($srcPath,$filePath)){
      if(isset($options['permission'])){
        @chmod($filePath,$options['permission']);
      }
    } else {
      throw new \mychaelstyle\Exception('Fail to copy file!',
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
    }
  }
	/**
	 * remove file or folder
	 * @param string $dstUri
	 * @param boolean $recursive
	 */
	public function remove($dstUri,$recursive=false){
    clearstatcache();
    $filePath = $this->getRealPath($dstUri);
    if(file_exists($filePath)){
      if(is_dir($filePath)){
        if(!is_executable($filePath)){
          throw new \mychaelstyle\Exception('Fail to remove dir because permission denied! '.$filePath,
            \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
        }
        $files = scandir($filePath);
        if($recursive){
          $this->removeDir($filePath);
        } else if(count($files)>2){
          throw new \mychaelstyle\Exception('Fail to remove dir because that has files! '.$filePath,
            \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
        } else if(!@rmdir($filePath)){
          throw new \mychaelstyle\Exception('Fail to remove dir! '.$filePath,
            \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
        }
      } else {
        unlink($filePath);
      }
    }
  }
  /**
   * Get real path from uri
   */
  private function getRealPath($uri){
    $path = $this->base_path.'/'.(preg_replace('/^\//','',$uri));
    return $path;
  }
  /**
   * remove dir recursive
   * @param string $path
   */
  private function removeDir($path){
    clearstatcache();
    if(is_dir($path) && strlen($path)>strlen($this->base_path)){
      if(!is_executable($path)){
        throw new \mychaelstyle\Exception('Fail to remove dir because permission denied! '.$path,
          \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
      }
      $files = scandir($path);
      foreach($files as $file){
        $tp = $path.(preg_match('/\/$/',$path)>0 ? '' : '/').$file;
        if(strpos($file,'.')===0){
          // nothing to do
        } else if(is_file($tp)){
          if(!unlink($tp)){
            throw new \mychaelstyle\Exception('Fail to remove file! '.$tp,
              \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
          }
        } else if(is_dir($tp)){
          $this->removeDir($tp);
        }
      }
      @rmdir($path);
    }
  }
}

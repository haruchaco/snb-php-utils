<?php
namespace snb\file\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
require_once dirname(dirname(__FILE__)).'/Exception.php';
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
 * $file = snb\file\Storage.createFile('example.txt',$options); 
 * $file->open('w');
 * $file->write("foo\nvar");
 * $file->close();
 * $file->commit();
 *
 * @package snb\file\providers
 * @autthe Masanori Nakashima
 */
class Local extends \snb\file\Provider {
  /**
   * base path to save files
   */
  private $base_path = null;
  /**
   * constructor
   */
  public function __construct(){
    $this->base_path = null;
    $this->options = array();
  }
	/**
	 * (non-PHPdoc)
	 * @see Provider::connect()
	 */
	public function connect($dsn,$options=array()){
    $this->perseDsn($dsn);
    // check the folder permision
    if('local'!==strtolower($this->provider_name)){
      throw new \snb\file\Exception('Invalid dsn strings!',
        \snb\file\Exception::ERROR_PROVIDER_CONNECTION);
    } else if(strlen(trim($this->provider_root))==0){
      throw new \snb\file\Exception('The base path is null!',
        \snb\file\Exception::ERROR_PROVIDER_CONNECTION);
    } else if(!is_dir($this->provider_root)){
      throw new \snb\file\Exception('The base path is not a directory! '.$this->provider_root,
        \snb\file\Exception::ERROR_PROVIDER_CONNECTION);
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
	 * (non-PHPdoc)
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
    $options = array_merge($this->options,$options);
    $filePath = $this->getRealPath($dstUri);
    $dirPath  = dirname($filePath);
    if(!is_dir($dirPath)){
      mkdir($dirPath,$options['folder_permission'],true);
    }
    if(@copy($srcPath,$filePath)){
      if(isset($options['permission'])){
        @chmod($filePath,$options['permission']);
      }
    } else {
      throw new \snb\file\Exception('Fail to copy file!',
        \snb\file\Exception::ERROR_PROVIDER_CONNECTION);
    }
  }
	/**
	 * remove file or folder
	 * @param string $dstUri
	 * @param boolean $recursive
	 */
	public function remove($dstUri,$recursive=false){
    $filePath = $this->getRealPath($dstUri);
    if(file_exists($filePath)){
      if(is_dir($filePath)){
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
}

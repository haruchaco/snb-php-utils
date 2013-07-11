<?php
namespace snb\file;
require_once(dirname(__FILE__).'/Storage.php');
require_once(dirname(__FILE__).'/Exception.php');
/**
 * ファイルを扱うユーティリティクラス
 * 
 * @package snb\file
 */
class File {
  /**
   * target storage object
   */
  private $storage = null;
  /**
   * file uri
   */
  private $uri = null;
  /**
   * file options
   */
  private $options = array();
  /**
   * temporary file path
   */
  private $tmp = null;
  /**
   *
   */
  private $privious = null;
  /**
   * file handle of the temporary file
   */
  private $handle = null;
  /**
   * file handle open mode
   */
  private $mode = 'r';
  /**
   * auto commit
   */
  private $auto_commit = true;
  /**
   * Constructor
   * @param string $dns ファイル保存先ルートディレクトリDNS
   * @param array $options 保存先接続オプション情報
   */
  public function __construct(Storage $storage,$uri,array $options=array(),$autoCommit=true){
    $this->storage = $storage;
    $this->uri = $uri;
    $this->options = $options;
    $this->auto_commit = $autoCommit;
  }
  /**
   * open file
   * @param string $mode mode of fopen
   */
  public function open($mode='r'){
    if($this->isOpened()){
      throw new Exception('This instance already open another uri file!');
    }
    $this->mode = $mode;
    $this->tmp  = tempnam(sys_get_temp_dir(),'snb_tmp_');
    $this->privious = tempnam(sys_get_temp_dir(),'snb_priv_');
    // ファイルがDNSのいずれかに存在するなら取得して一時ファイルにコピー
    try {
      $this->storage->get($this->uri,$this->tmp);
    } catch(Exception $e){
      // nothing to do
    }
    // rollbackのためにオープン時の状態をもう一つ保存
    if(file_exists($this->tmp) && filesize($this->tmp)>0){
      copy($this->tmp,$this->privious);
    } else {
      touch($this->tmp);
      $this->privious = null;
    }

    $this->handle  = @fopen($this->tmp, $this->mode);
    if( false === $this->handle ){
      throw new Exception('Fail to open file '.$this->tmp);
    } else {
      $lr = true;
      if( strpos($mode,'r') !== false ){
        $lr = @flock($this->handle,LOCK_SH);
      } else {
        $lr = @flock($this->handle,LOCK_EX);
      }
      if( false === $lr ){
        throw new Exception('Fail to lock file! '.$this->tmp);
      }
    }
  }
  /**
   * write. like fwrite
   * @param string $strings
   */
  public function write($strings){
    $this->checkOpen();
    return fwrite($this->handle,$strings);
  }
  /**
   * gets
   * @param int $length
   */
  public function gets($length=null){
    $this->checkOpen();
    if( is_null($length) ) {
      return fgets($this->handle);
    } else {
      return fgets($this->handle, $length);
    }
  }
  /**
   * eof
   * @return boolean true if the handle at end of file.
   */
  public function eof(){
    $this->checkOpen();
    return feof($this->handle);
  }
  /**
   * close file handle
   */
  public function close(){
    $this->checkOpen();
		@flock($this->handle, LOCK_UN);
		@fclose($this->handle);
		// if read only, return
		if(strpos($this->mode,'r') !== false){
			return true;
		}
    if($this->auto_commit){
      $this->commit();
    }
  }
  /**
   * comit at close a file.
   */
  public function commit(){
    if(!$this->canCommit()){
      throw new Exception('No file for commit!',0);
    }
    $this->storage->put($this->tmp,$this->uri,$this->options);
    $this->finalize();
  }
  /**
   * rollback
   */
  public function rolback(){
    $this->storage->remove($this->uri,$this->options);
    if(!is_null($this->privious)){
      $this->storage->put($this->privious,$this->uri,$this->options);
    }
    $this->finalize();
  }
  /**
   * finalize
   */
  private function finalize(){
    if(!is_null($this->tmp) && file_exists($this->tmp)){
      @unlink($this->tmp);
    }
    if(!is_null($this->privious) && file_exists($this->privious)){
      @unlink($this->privious);
    }
    $this->tmp = null;
    $this->privious = null;
    $this->uri = null;
    $this->handle = null;
  }
  /**
   * can commit
   */
  private function canCommit(){
    if(is_null($this->uri) || is_null($this->tmp)
      || !file_exists($this->tmp)){
      return false;
    }
    return true;
  }
  /**
   * is opened
   * @return boolean true if already opend
   */
  private function isOpened(){
    if(is_null($this->uri) || is_null($this->handle) || is_null($this->tmp)){
      return false;
    }
    return true;
  }
  /**
   * check this instance has already open a file.
   * @throws snb\util\Exception
   */
  private function checkOpen(){
    if(!$this->isOpened()){
      throw new Exception('This instance has not opened yet!');
    }
  }
}

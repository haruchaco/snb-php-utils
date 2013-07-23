<?php
/**
 * snb\storage\providers\Mysql
 * @package snb
 * @subpackage storage
 * @auther Masanori Nakashima
 */
namespace snb\storage\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
require_once dirname(dirname(__FILE__)).'/Exception.php';
/**
 * ファイルをAmazon Web Service S#に保存するストレージプロバイダ
 * 
 * [DSN] amazon_s3://[region]/[bucket name]
 *
 * e.g. amazon_s3://REGION_TOKYO/logs_archives
 *
 * [Initialize Options] same with aws php sdk credentials.
 * - key    ... Amazon Web Services Key.
 * - secret ... Amazon Web Services Secret.
 * - default_cache_config ... see the aws php sdk document.
 * - certificate_autority ... see the aws php sdk document.
 *
 * - curlopts ... curl options.
 * - acl      ... acl. see the aws php sdk.
 * - contentType ... content-type
 *
 * e.g)
 * $options = array(
 *   'key' => 'your key',
 *   'secret' => 'your secret',
 *   'default_cache_config => '',
 *   'certificate_autority' => false,
 *   'curlopts' => array(CURLOPT_SSL_VERIFYPEER => false),
 *   'acl' => AmazonS3::ACL_PUBLIC,
 *   'contentType' => 'image/png'
 * );
 * $provider = new AmazonS3();
 * $provider->connect($dsn,$options);
 *
 * [File options]
 * If is not set, use the initialize options value.
 * 
 * - curlopts ... curl options.
 * - acl      ... acl. see the aws php sdk.
 * - contentType ... content-type
 *
 * @package snb
 * @subpackage storage
 * @auther Masanori Nakashima
 */
class AmazonS3 extends \snb\storage\Provider {
  /**
   * region
   */
  private $region = null;
  /**
   * base path to save files
   */
  private $bucket_name = null;
  /**
   * s3 object
   */
  private $s3 = null;
  /**
   * constructor
   */
  public function __construct(){
    $this->options = array();
    $this->region = null;
    $this->bucket_name = null;
  }
	/**
	 * prepare connect an AmazonS3 bucket.
   * @param string $dsn 'amazon_s3://[region name]/[bucket_name]/'
   * @param array $options map. see the AmazonS3 options.
	 * @see Provider::connect()
	 */
	public function connect($dsn,$options=array()){
    $this->perseDsn($dsn);
    list($this->region,$this->bucket_name) = explode('/',$this->provider_root);
    $this->options = $options;
    // region
    $region = constant('\AmazonS3::'.$this->region);
    $this->region = (is_null($region)) ? $this->region : $region;
    // create object
		$this->s3 = new \AmazonS3($this->options);
		$this->s3->set_region($this->region);
  }
  /**
   * disconnect.
   * reset the member variables.
	 * @see Provider::disconnect()
   */
  public function disconnect(){
    $this->provider_name = null;
    $this->provider_root = null;
    $this->region = null;
    $this->bucket_name = null;
    $this->s3 = null;
    $this->options = array();
  }
  /**
   * get contents from uri
   */
  public function get($uri,$path=null){
    $uri = $this->_formatUri($uri);
    $localPath = (!is_null($path) ) ?
      $path :tempnam(sys_get_temp_dir(),'snb_aws_s3_tmp_');
		$response = $this->s3->get_object(
      $this->bucket_name,
      $uri,
      array('fileDownload' => $localPath));
		if ($response->isOK()) {
      if(is_null($path)){
        $contents = file_get_contents($localPath);
        @unlink($localPath);
        return $contents;
      } else {
        return true;
      }
		} else if(file_exists($path)){
      @unlink($path);
    }
    throw new \snb\storage\Exception('Fail to download from amazon s3!',
      \snb\storage\Exception::ERROR_PROVIDER_CONNECTION);
  }
	/**
	 * put file
	 * @param string $srcPath
	 * @param string $dstUri
   * @param array $options
	 */
	public function put($srcPath,$dstUri,$options=array()){
    $dstUri = $this->_formatUri($dstUri);
		$this->remove($dstUri);
    $options = $this->_mergePutOptions($options);
    $options['fileUpload'] = $srcPath; 
		$response = $this->s3->create_object(
      $this->bucket_name,
      $dstUri,
      $options
    );
		if ($response->isOK()) {
      return true;
		} else {
      throw new \snb\storage\Exception('Fail to upload to amazon s3!',
        \snb\storage\Exception::ERROR_PROVIDER_CONNECTION);
    }
  }
	/**
	 * remove file or folder
	 * @param string $dstUri
	 * @param boolean $recursive
	 */
	public function remove($dstUri,$recursive=false){
    $dstUri = $this->_formatUri($dstUri);
		$response = $this->s3->delete_objects(
      $this->bucket_name,
      array(  
			  'objects' => array(array('key' => $dstUri)))
    );
		if ($response->isOK()) {
      return true;
    } else {
      return false;
    }
		return true;
  }
  /**
   * merge options for put.
   * @param array $options
   * @return array
   */
  private function _mergePutOptions($options){
    $mergedOpts = array_merge($this->options,$options);
    if(isset($options['acl'])){
      $mergedOpts['acl'] = $options['acl'];
    } else if(!isset($options['acl']) || strlen($options['acl'])==0){
      $mergedOpts['acl'] = \AmazonS3::ACL_PUBLIC;
    }
    if(isset($options['contentType'])){
      $mergedOpts['contentType'] = $options['contentType'];
    } else if(!isset($options['contentType']) || strlen($options['contentType'])==0){
      $mergedOpts['contentType'] = 'text/plain';
    }
    if(isset($options['curlopts'])){
      $mergedOpts['curlopts'] = $options['curlopts'];
    } else if(!isset($options['curlopts']) ||
      !is_array($options['curlopts']) || count($options['curlopts'])==0){
      $mergedOpts['curlopts'] = array(CURLOPT_SSL_VERIFYPEER => false);
    }
    return $mergedOpts;
  }
  /**
   * format uri for S3
   */
  private function _formatUri($uri){
		if( strpos($uri,'/')===0 ){
			$uri = substr($uri,1);
		}
    return $uri;
  }
}

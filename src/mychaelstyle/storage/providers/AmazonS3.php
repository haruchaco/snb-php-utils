<?php
/**
 * mychaelstyle\storage\providers\Mysql
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\storage\providers;
require_once dirname(dirname(dirname(__FILE__))).'/ProviderAws.php';
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * ファイルをAmazon Web Service S3に保存するストレージプロバイダ
 * 
 * [DSN] AmazonS3://[region]/[bucket name]
 *
 * e.g. AmazonS3://REGION_TOKYO/logs_archives
 *
 * [Initialize Options] same with aws php sdk credentials.
 * - key    ... Amazon Web Services Key.
 * - secret ... Amazon Web Services Secret.
 * - default_cache_config ... see the aws php sdk document.
 * - certificate_autority ... see the aws php sdk document.
 *
 * - curl.options ... curl options.
 * - acl      ... acl. see the aws php sdk.
 * - contentType ... content-type
 *
 * e.g)
 * $options = array(
 *   'key' => 'your key',
 *   'secret' => 'your secret',
 *   'default_cache_config => '',
 *   'certificate_autority' => false,
 *   'curl.options' => array(CURLOPT_SSL_VERIFYPEER => false),
 *   'acl' => AmazonS3::ACL_PUBLIC,
 *   'contentType' => 'image/png'
 * );
 * $provider = new AmazonS3();
 * $provider->connect($uri,$options);
 *
 * [File options]
 * If is not set, use the initialize options value.
 * 
 * - curl.options ... curl options.
 * - acl      ... acl. see the aws php sdk.
 * - contentType ... content-type
 *
 * @package mychaelstyle
 * @subpackage storage
 * @auther Masanori Nakashima
 */
class AmazonS3 extends \mychaelstyle\ProviderAws implements \mychaelstyle\storage\Provider {
  /**
   * @var base path to save files
   */
  private $bucket_name = null;
  /**
   * @var $options
   */
  private $options = array();
  /**
   * constructor
   */
  public function __construct(){
    $this->bucket_name = null;
  }
  /**
   * get AWS Service name
   * @return string service client name e.g. 'Sqs'
   */
  public function getServiceName(){
    return 'S3';
  }
	/**
	 * prepare connect an AmazonS3 bucket.
   * @param string $uri '[region name]/[bucket_name]/'
   * @param array $options map. see the AmazonS3 options.
	 * @see Provider::connect()
	 */
	public function connect($uri,$options=array()){
    parent::connect($uri,$options);
    if(strpos($uri,'/')===false){
      throw new \mychaelstyle\Exception('Fail to connect amazon s3! bucket_name is required!',
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
    }
    $this->bucket_name = substr($uri,strpos($uri,'/')+1);
  }
  /**
   * disconnect.
   * reset the member variables.
	 * @see Provider::disconnect()
   */
  public function disconnect(){
    parent::disconnect();
    $this->bucket_name = null;
  }
  /**
   * get contents from uri
   */
  public function get($uri,$path=null){
    $uri = $this->__formatUri($uri);
    $localPath = (!is_null($path) ) ?
      $path :tempnam(sys_get_temp_dir(),'mychaelstyle_aws_s3_tmp_');
    try {
      $this->client->getObject(array(
        'Bucket' => $this->bucket_name,
        'Key'    => $uri,
        'SaveAs' => $localPath
      ));
      if(is_null($path)){
        $contents = file_get_contents($localPath);
        @unlink($localPath);
        return $contents;
      } else {
        return true;
      }
    } catch(\Exception $e){
      if(file_exists($path)){
        @unlink($path);
      }
      throw new \mychaelstyle\Exception('Fail to download from amazon s3!',
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
    }
  }
	/**
	 * put file
	 * @param string $srcPath
	 * @param string $dstUri
   * @param array $options
	 */
	public function put($srcPath,$dstUri,$options=array()){
    $dstUri = $this->__formatUri($dstUri);
		$this->remove($dstUri);
    $options = $this->__mergePutOptions($options);
    $fh = null;
    try {
      $fh = fopen($srcPath,'r+');
      if($fh){
        $this->client->setConfig((isset($options['curl.options']) ? $options['curl.options'] : null));
        $this->client->putObject(array(
          'Bucket'   => $this->bucket_name,
          'Key'      => $dstUri,
          'Body'     => $fh,
          'Metadata' => isset($options['Metadata']) ? $options['Metadata'] : null,
        ));
        if(!is_null($fh) && is_resource($fh)){
          fclose($fh);
        }
      } else {
        throw new \mychaelstyle\Exception('Fail to upload to amazon s3!',
          \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
      }
    } catch(\Exception $e){
      if(!is_null($fh) && is_resource($fh)){
        fclose($fh);
      }
      throw new \mychaelstyle\Exception('Fail to upload to amazon s3! '.$e->getMessage(),
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e);
    }
  }
	/**
	 * remove file or folder
	 * @param string $dstUri
	 * @param boolean $recursive
	 */
	public function remove($dstUri,$recursive=false){
    $dstUri = $this->__formatUri($dstUri);
    try {
      $this->client->deleteObject(array(
        'Bucket' => $this->bucket_name,
        'Key'    => $dstUri
      ));
    } catch(\Exception $e){
      throw new \mychaelstyle\Exception('Fail to delete object on amazon s3! '.$e->getMessage(),
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e);
    }
  }
  /**
   * merge options for put.
   * @param array $options
   * @return array
   */
  private function __mergePutOptions($options){
    $mergedOpts = array_merge($this->options,$options);
    if(isset($options['acl'])){
      $mergedOpts['acl'] = $options['acl'];
    } else if(!isset($options['acl']) || strlen($options['acl'])==0){
      $mergedOpts['acl'] = 'private';
    }
    if(isset($options['contentType'])){
      $mergedOpts['contentType'] = $options['contentType'];
    } else if(!isset($options['contentType']) || strlen($options['contentType'])==0){
      $mergedOpts['contentType'] = 'text/plain';
    }
    if(isset($options['curl.options'])){
      $mergedOpts['curl.options'] = $options['curl.options'];
    } else if(!isset($options['curl.options']) ||
      !is_array($options['curl.options']) || count($options['curl.options'])==0){
      $mergedOpts['curl.options'] = array(CURLOPT_SSL_VERIFYPEER => false);
    }
    return $mergedOpts;
  }
  /**
   * format uri for S3
   */
  private function __formatUri($uri){
		if( strpos($uri,'/')===0 ){
			$uri = substr($uri,1);
		}
    return $uri;
  }
}

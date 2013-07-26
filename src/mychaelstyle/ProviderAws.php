<?php
/**
 * ProviderAws
 * Abstract class for Amazon web services provider
 * @package mychaelstyle
 */
namespace mychaelstyle;
require_once dirname(__FILE__).'/Provider.php';
/**
 * ProviderAws
 * @package mychaelstyle
 */
abstract class ProviderAws implements Provider {
  /**
   * @var string $region
   */
  protected $region = null;
  /**
   * @var Aws\Common\Client\AbstractClient
   */
  protected $client = null;
  /**
   * connect
   */
  public function connect($uri,$options=array()){
    list($region) = explode('/',$uri);
    if(is_null($region) || strlen($region)==0){
      throw new \mychaelstyle\Exception('Fail to connect aws! option \'region\' is required!',
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION);
    }
    $defRegion = @constant('Aws\Common\Enum\Region::'.$region);
    $this->region = (is_null($defRegion)) ? $region : $defRegion;
    $options['region'] = $this->region;
    try {
    $aws = \Aws\Common\Aws::factory($options);
    $this->client = $aws->get($this->getServiceName());
    } catch(\Exception $e){
      throw new \mychaelstyle\Exception('Fail to connect aws!',
        \mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e);
    }
  }
  /**
   * disconnect
   */
  public function disconnect(){
    $this->region = null;
    $this->client = null;
  }
  /**
   * get AWS Service name
   * @return string service client name e.g. 'Sqs'
   */
  abstract public function getServiceName();
}

<?php
/**
 * Queue provider of Amazon SQS
 * @package mychaelstyle
 * @subpackage queue
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\queue\providers;
require_once dirname(dirname(dirname(__FILE__))).'/ProviderAws.php';
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * Queue provider of Amazon SQS
 * @package mychaelstyle
 * @subpackage queue
 * @auther Masanori Nakashima
 */
class AmazonSQS extends \mychaelstyle\ProviderAws implements \mychaelstyle\queue\Provider {
  /**
   * @var string queue name
   */
  private $queue = null;
  /**
   * @var url of this queue
   */
  private $url = null;
  /**
   * @var string receipt handle of a last read queue
   */
  private $receipt = null;
  /**
   * @var string message id of last read
   */
  private $message_id = null;
  /**
   * constructor
   */
  public function __construct(){
  }
  /**
   * get AWS Service name
   * @return string service client name e.g. 'Sqs'
   */
  public function getServiceName(){
    return 'Sqs';
  }
  /**
   * connection create
   */
  public function connect($uri,$options=array()){
    parent::connect($uri,$options);
    $this->queue = substr($uri,strpos($uri,'/')+1);
    $result = $this->client->createQueue(array('QueueName'=>(string)$this->queue));
    $this->url = $result->get('QueueUrl');
  }
  /**
   * disconnect
   */
  public function disconnect(){
    parent::disconnect();
    $this->queue = null;
    $this->url = null;
    $this->receipt = null;
    $this->message_id = null;
  }
  /**
   * push to queue
   */
  public function offer($body){
    if(!is_scalar($body)){
      $body = json_encode($body);
    }
    try{
      $result = $this->client->sendMessage(
        array(
          'QueueUrl' => $this->url,
          'MessageBody' => $body
        ));
    } catch(\Exception $e){
      throw new \mychaelstyle\Exception('AWS SQS Fail to offer message! ',\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
    }
  }
  /**
   * poll from queue
   */
  public function poll($callback=null,$callbackParams=array()){
    $result = $this->peek($callback,$callbackParams);
    $this->remove();
    return $result;
  }

  /**
   * peek a head from this queue
   */
  public function peek($callback=null,$callbackParams=array()){
    try{
      $result = $this->client->receiveMessage(array('QueueUrl'=>$this->url));
      $messages = $result->getPath('Messages/*/Body');
      $receipts  = $result->getPath('Messages/*/ReceiptHandle');
      $this->receipt = $receipts[0];
      $decoded = json_decode($messages[0]);
      if(is_null($decoded)){
        return $messages[0];
      } else {
        return $decoded;
      }
    } catch(\Exception $e){
      throw new \mychaelstyle\Exception('AWS SQS Fail to receive message! ',\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
    }
  }

  /**
   * remove a head of this queue
   */
  public function remove(){
    if(is_null($this->receipt)){
      $this->peek();
    }
    if(!is_null($this->receipt)){
      $handle = (string) $this->receipt;
      try {
        $result = $this->client->deleteMessage(array('QueueUrl'=>$this->url,'ReceiptHandle'=>$handle));
      } catch(\Exception $e){
        throw new \mychaelstyle\Exception('AWS SQS Fail to remove message! ',\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
      }
    }
  }
}

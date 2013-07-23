<?php
/**
 * Queue provider of Amazon SQS
 * @package snb
 * @subpackage queue
 * @auther Masanori Nakashima
 */
namespace snb\queue\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * Queue provider of Amazon SQS
 * @package snb
 * @subpackage queue
 * @auther Masanori Nakashima
 */
class AmazonSQS extends \snb\queue\Provider {
  /**
   * @var \AmazonSQS
   */
  private $sqs = null;
  /**
   * @var string region
   */
  private $region = null;
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
  public function __construct(){
  }
  /**
   * connection create
   */
  public function connect($uri,$options=array()){
    $this->sqs = new \AmazonSQS($options);
    list($this->region,$this->queue) = explode('/',$uri);
    $region = constant('\AmazonSQS::'.$this->region);
    $this->region = (is_null($region)) ? $this->region : $region;
		$this->sqs->set_region($this->region);
    // get url
    $this->url = $this->sqs->create_queue($this->queue)->body->CreateQueueResult->QueueUrl;
  }
  /**
   * push to queue
   */
  public function offer($body){
    if(!is_scalar($body)){
      $body = json_encode($body);
    }
    $result = $this->sqs->send_message($this->url,$body);
    if($result->isOK()){
      return true;
    } else if(isset($result->body) && isset($result->body->Error)){
      throw new \snb\Exception('Fail to offer! '.$result->body->Error->Message,\snb\Exception::ERROR_PROVIDER_CONNECTION); 
    } else {
      throw new \snb\Exception('Fail to connect SQS! '.print_r($result,true),\snb\Exception::ERROR_PROVIDER_CONNECTION); 
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
    $result = $this->sqs->receive_message($this->url);
    if($result->isOK()){
      if(isset($result->body->ReceiveMessageResult->Message)){
        $msgObj = $result->body->ReceiveMessageResult->Message;
        $this->receipt = $msgObj->ReceiptHandle;
        $this->message_id = $msgObj->MessageId;;
        if(is_callable($callback)){
          $params = array();
          if(is_array($callbackParams)){
            $params = $callbackParams;
          } else {
            $params[] = $callbackParams;
          }
          $params[] = $result;
          call_user_func_array($callback,$params);
        }
        $message = $msgObj->Body;
        if($ret = json_decode($message)){
          return $ret;
        } else {
          return $message;
        }
      } else {
        return null;
      }
    } else if(isset($result->body) && isset($result->body->Error)){
      throw new \snb\Exception('Fail to peek queue! '.$result->body->Error->Message,\snb\Exception::ERROR_PROVIDER_CONNECTION); 
    } else {
      throw new \snb\Exception('Fail to peek queue! '.print_r($result,true),\snb\Exception::ERROR_PROVIDER_CONNECTION); 
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
      $result = $this->sqs->delete_message($this->url,$handle);
      if($result->isOK()){
        $this->receipt = null;
      } else if(isset($result->body) && isset($result->body->Error)){
        throw new \snb\Exception('Fail to remove queue! '.$result->body->Error->Message,\snb\Exception::ERROR_PROVIDER_CONNECTION); 
      } else {
        throw new \snb\Exception('Fail to remove queue! '.print_r($result,true),\snb\Exception::ERROR_PROVIDER_CONNECTION); 
      }
    }
  }
}

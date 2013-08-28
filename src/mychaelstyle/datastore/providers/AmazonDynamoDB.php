<?php
/**
 * Queue provider of Amazon SQS
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\datastore\providers;
require_once dirname(dirname(dirname(__FILE__))).'/ProviderAws.php';
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * Queue provider of Amazon SQS
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
class AmazonDynamoDB extends \mychaelstyle\ProviderAws implements \mychaelstyle\datastore\Provider {
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
    return 'DynamoDb';
  }
  /**
   * connection create
   */
  public function connect($uri,$options=array()){
    parent::connect($uri,$options);
  }
  /**
   * batch write datas
   * @param array $datas
   */
  public function batchWrite(array $datas){
    $requestData = array('RequestItems'=>array());
    // carete request format
    foreach($datas as $table => $rows){
      if(!isset($requestData['RequestItems'][$table])){
        $requestData['RequestItems'][$table] = array();
      }
      foreach($rows as $row){
        $item = array(
          'PutRequest' => array(
            'Item' => $this->client->formatAttributes($row)
          ),
        );
        $requestData['RequestItems'][$table][] = $item;
      }
    }
    // request
    try {
      $this->client->batchWriteItem($requestData);
    } catch( \Exception $e ){
      throw new \mychaelstyle\Exception('AWS DynamoDB Fail to write! '.$e->getMessage(),\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
    }
  }
  public function batchGet(array $keys){
    $requestData = array(
      'RequestItems'=>array(
      )
    );
    // carete request format
    foreach($keys as $table => $rows){
      $tableRequest = array(
      );
      $items = array();
      foreach($rows as $row){
        $items[] = $this->client->formatAttributes($row);;
      }
      $requestData['RequestItems'][$table] = array(
        'Keys' => $items,
        'ConsistentRead' => true
        );
    }
    // request
    $response = null;
    try{
      $response = $this->client->batchGetItem($requestData);
    } catch(\Exception $e){
      throw new \mychaelstyle\Exception('AWS DynamoDB Fail to batchGetItem! '.$e->getMessage(),\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
    }
    $tables = array_keys($keys);
    $retMap = array();
    foreach($tables as $table){
      $rows = $response->getPath("Responses/{$table}");
      $items = array();
      foreach($rows as $row){
        $item = array();
        foreach($row as $f => $colinfo){
          foreach($colinfo as $type => $val){
            $item[$f] = $val;
          }
        }
        // sort 
        $kfm = $keys[$table][0];
        $kfs = array_keys($kfm);
        $sorted = array();
        foreach($kfs as $f){
          $sorted[$f] = $item[$f];
        }
        foreach($item as $f => $v){
          if(!in_array($f,$kfs)){
            $sorted[$f] = $v;
          }
        }
        $items[] = $sorted;
      }
      $retMap[$table] = $items;
    }
    return $retMap;
  }
  /**
   * batch remove
   */
  public function batchRemove(array $keys){
    $requestData = array('RequestItems'=>array());
    // carete request format
    foreach($keys as $table => $rows){
      if(!isset($requestData['RequestItems'][$table])){
        $requestData['RequestItems'][$table] = array();
      }
      foreach($rows as $row){
        $item = array(
          'DeleteRequest' => array(
            'Key' => $this->client->formatAttributes($row)
          ),
        );
        $requestData['RequestItems'][$table][] = $item;
      }
    }
    // request
    try {
      $this->client->batchWriteItem($requestData);
    } catch( \Exception $e ){
      throw new \mychaelstyle\Exception('AWS DynamoDB Fail to remove! '.$e->getMessage(),\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
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

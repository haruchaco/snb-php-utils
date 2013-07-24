<?php
/**
 * Queue provider of Amazon SQS
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\datastore\providers;
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * Queue provider of Amazon SQS
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
class AmazonDynamoDB extends \mychaelstyle\datastore\Provider {
  /**
   * @var \AmazonDynamoDB
   */
  private $dynamo = null;
  /**
   * @var string region
   */
  private $region = null;
  /**
   * @var string table name
   */
  private $table = null;
  /**
   * constructor
   */
  public function __construct(){
  }
  /**
   * connection create
   */
  public function connect($uri,$options=array()){
//    $this->dynamo = new \AmazonDynamoDB($options);
    list($this->region,$this->table) = explode('/',$uri);
    $region = constant('Aws\Common\Enum\Region::'.$this->region);
    $this->region = (is_null($region)) ? $this->region : $region;
    $options['region'] = $this->region;
    $this->dynamo = \Aws\DynamoDb\DynamoDbClient::factory($options);
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
            'Item' => $this->dynamo->formatAttributes($row)
          ),
        );
        $requestData['RequestItems'][$table][] = $item;
      }
    }
    // request
    try {
      $this->dynamo->batchWriteItem($requestData);
    } catch( \Exception $e ){
      throw new \mychaelstyle\Exception('AWS DynamoDB Fail to write! ',\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
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
        $items[] = $this->dynamo->formatAttributes($row);;
      }
      $requestData['RequestItems'][$table] = array(
        'Keys' => $items,
        'ConsistentRead' => true
        );
    }
    // request
    $response = null;
    try{
      $response = $this->dynamo->batchGetItem($requestData);
    } catch(\Exception $e){
      throw new \mychaelstyle\Exception('AWS DynamoDB Fail to batchGetItem! ',\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
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
        $items[] = $item;
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
            'Key' => $this->dynamo->formatAttributes($row)
          ),
        );
        $requestData['RequestItems'][$table][] = $item;
      }
    }
    // request
    try {
      $this->dynamo->batchWriteItem($requestData);
    } catch( \Exception $e ){
      throw new \mychaelstyle\Exception('AWS DynamoDB Fail to remove! ',\mychaelstyle\Exception::ERROR_PROVIDER_CONNECTION,$e); 
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

  public function remove($table,$key){
    $keys = array(
      $table => array(
        $key
      )
    );
    $this->batchRemove($keys);
  }
}

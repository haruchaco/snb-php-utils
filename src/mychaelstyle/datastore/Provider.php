<?php
/**
 * mychaelstyle\datastore\Provider
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\datastore;
require_once dirname(dirname(__FILE__)).'/Provider.php';
/**
 * mychaelstyle\datastore\Provider
 * @package mychaelstyle
 * @subpackage datastore
 * @auther Masanori Nakashima
 */
interface Provider extends \mychaelstyle\Provider {
  /**
   * batch write
   * write some datas at once.
   * 
   * -Parameter datas e.g.
   * <pre>
   * array(
   *   '[table name]' => array(
   *     array( ... row data map ),
   *     array( ... row data map ),
   *   )
   * )
   * </pre>
   * @param array $datas
   * @return boolean
   */
  public function batchWrite(array $datas);
  /**
   * batch get
   * get some datas at once.
   * - Parameter keys
   * <pre>
   * array(
   *   '[table name]' => array(
   *     array( ... key field value map ),
   *     array( ... key field value map ),
   *   )
   * )
   * </pre>
   * -Return datas e.g.
   * <pre>
   * array(
   *   '[table name]' => array(
   *     array( ... row data map ),
   *     array( ... row data map ),
   *   )
   * )
   * </pre>
   * @param array $keys
   * @return array
   */
  public function batchGet(array $keys);
  /**
   * batch get
   * get some datas at once.
   * - Parameter keys
   * <pre>
   * array(
   *   '[table name]' => array(
   *     array( ... key field value map ),
   *     array( ... key field value map ),
   *   )
   * )
   * </pre>
   * @param array $keys
   * @return boolean
   */
  public function batchRemove(array $keys);
  /**
   * write data
   * - Parameter data e.g.
   * <pre>
   * array(
   *   'field1' => 'value1',  // this is used as primary key
   *   'field2' => 'value2',
   *   ....
   * )
   * </pre>
   * @param string $table table name
   * @param boolean
   */
  public function write($table,array $data);
  /**
   * get a data
   * - Parameter key e.g.
   * <pre>
   * array('field_name'=>'value')
   * </pre>
   * - Return
   * <pre>
   * array(
   *   'field1' => value,
   *   'field2' => value,
   *   ...
   * )
   * </pre>
   * @param string $table
   * @param array key map
   * @return array
   */
  public function get($table,$key);
  /**
   * remove a data
   * - Parameter key e.g.
   * <pre>
   * array('field_name'=>'value')
   * </pre>
   * @param string $table
   * @param array key map
   * @return boolean
   */
  public function remove($table,$key);
}

<?php
namespace snb\storage\providers;
require_once 'storage/providers/Mysql.php';

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2013-07-17 at 08:35:18.
 */
class MysqlTest extends \snb\TestBase
{
  /**
   * @var Mysql
   */
  protected $object;
  /**
   * @var string
   */
  protected $dsn;
  /**
   * @var array options
   */
  protected $options = array();

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  public function setUp()
  {
    parent::setUp();
    $this->object = new Mysql;
    $this->dsn = 'mysql://localhost/test/m_files';
    $this->options = array(
      'user' => 'root',
      'pass' => '',
    );
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown()
  {
    parent::tearDown();
  }

  /**
   * @covers snb\storage\providers\Mysql::connect
   * @todo   Implement testConnect().
   */
  public function testConnect()
  {
    $this->object->connect($this->dsn,$this->options);
print_r($this->object);
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers snb\storage\providers\Mysql::disconnect
   * @todo   Implement testDisconnect().
   */
  public function testDisconnect()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers snb\storage\providers\Mysql::get
   * @todo   Implement testGet().
   */
  public function testGet()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers snb\storage\providers\Mysql::put
   * @todo   Implement testPut().
   */
  public function testPut()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }

  /**
   * @covers snb\storage\providers\Mysql::remove
   * @todo   Implement testRemove().
   */
  public function testRemove()
  {
    // Remove the following lines when you implement this test.
    $this->markTestIncomplete(
      'This test has not been implemented yet.'
    );
  }
}

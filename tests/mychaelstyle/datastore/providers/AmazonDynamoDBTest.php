<?php
namespace mychaelstyle\datastore\providers;
require_once 'datastore/providers/AmazonDynamoDB.php';

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2013-07-23 at 20:50:14.
 */
class AmazonDynamoDBTest extends \mychaelstyle\TestBase
{
  /**
   * @var AmazonDynamoDB
   */
  protected $object;
  /**
   * @var string provider dsn
   */
  protected $dsn;
  /**
   * @var string $uri
   */
  protected $uri;
  /**
   * @var string provider connect options
   */
  protected $options = array();

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    parent::setUp();
    $this->object = new AmazonDynamoDB;
    // set your aws key and secret to your env
    $this->dsn = 'AmazonDynamoDB://REGION_'.$_SERVER['AWS_REGION_NAME'].'/tests';
    $this->uri = $_SERVER['AWS_REGION_NAME'].'/tests';
    $this->options = array(
      'key' => $_SERVER['AWS_ACCESS_KEY'],
      'secret' => $_SERVER['AWS_SECRET_KEY'],
      'default_cache_config' => '',
      'certificate_autority' => false
    );
    // curl options
    $this->options['curlopts'] = array(CURLOPT_SSL_VERIFYPEER => false);
    $this->object = new AmazonDynamoDB;
    $this->object->connect($this->uri,$this->options);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown()
  {
    parent::tearDown();
  }

  /**
   * @covers mychaelstyle\datastore\providers\AmazonDynamoDB::write
   * @covers mychaelstyle\datastore\providers\AmazonDynamoDB::batchWrite
   * @covers mychaelstyle\datastore\providers\AmazonDynamoDB::get
   * @covers mychaelstyle\datastore\providers\AmazonDynamoDB::batchGet
   * @covers mychaelstyle\datastore\providers\AmazonDynamoDB::connect
   */
  public function testFlow()
  {
    $t = time();
    $expected = array(
      'tests' => array(
        array(
          'id' => 'AAAA0001',
          'updated_at' => (int) $t,
          'body' => 'This is test!',
        )
      )
    );

    // write
    $this->object->write('tests',$expected['tests'][0]);

    // get
    $result = $this->object->get('tests',array('id'=>'AAAA0001'));
    $this->assertEquals($expected['tests'][0],$result);

    // remove
    $this->object->remove('tests',array('id'=>'AAAA0001'));
    $result = $this->object->get('tests',array('id'=>'AAAA0001'));
    $this->assertNull($result);

    // batch write
    $this->object->batchWrite($expected);

    // batchGet
    $result = $this->object->batchGet(
      array(
        'tests'=>array(
          array('id'=>'AAAA0001')
        )
      )
    );
    $this->assertEquals($expected,$result);

    // batch remove
    $result = $this->object->batchRemove(
      array(
        'tests'=>array(
          array('id'=>'AAAA0001')
        )
      )
    );
    $result = $this->object->get('tests',array('id'=>'AAAA0001'));
    $this->assertNull($result);
  }
}

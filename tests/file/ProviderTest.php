<?php
namespace snb\file;
require_once 'file/Provider.php';
/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2013-07-10 at 18:47:19.
 */
class ProviderTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @var Provider
   */
  public $object;
  /**
   * dsn map
   */
  private $dsnMap = array();

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  public function setUp()
  {
    $this->dsnMap = array(
      'Local' => array(
        'dsn' => 'local:///Users/masanori/work/snb-php-utils/tests/work',
        'options' => array('permission'=>0644),
      ),
      'Local2' => array(
        'dsn' => 'local:///Users/masanori/work/snb-php-utils/tests/tmp',
        'options' => array('permission'=>0644),
      ),
      'AmazonS3' => array(
        'dsn' => 'amazon_s3://REGION_'.$_SERVER['SNB_AWS_S3_REGION_NAME'].'/'.$_SERVER['SNB_AWS_S3_BUCKET'],
        'options' => array(
          'key' => $_SERVER['SNB_AWS_KEY'],
          'secret' => $_SERVER['SNB_AWS_SECRET'],
          'default_cache_config' => '',
          'certificate_autority' => false
        )
      )
    );
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown()
  {
  }

  /**
   * @covers snb\file\Provider::getInstance
   */
  public function testGetInstance()
  {
    // valid
    $def = $this->dsnMap['Local'];
    $this->object = Provider::getInstance($def['dsn'],$def['options']);
    // invalid
    $this->setExpectedException('snb\file\Exception');
    $this->object = Provider::getInstance('',$def['options']);
  }
}

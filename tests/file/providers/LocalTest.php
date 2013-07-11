<?php
namespace snb\file\providers;
require_once 'file/providers/Local.php';

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2013-07-11 at 08:11:45.
 */
class LocalTest extends \PHPUnit_Framework_TestCase
{
  /**
   * @var Local
   */
  protected $object;
  /**
   * テスト用example.txtのURI
   */
  protected $uri_example;
  /**
   * test file path
   */
  protected $path_example;
  /**
   * Valid DSN
   * 正常接続できるDSN
   */
  protected $dsn;

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  protected function setUp()
  {
    $this->uri_example  = 'example.txt';
    $this->path_example = DIR_TEST.'/fixtures/example.txt';
    $this->dsn = 'local://'.DIR_TEST.'/work';
    // workフォルダにコピー
    @copy($this->path_example,DIR_TEST.'/work/'.$this->uri_example);
    // new
    $this->object = new Local;
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  protected function tearDown()
  {
    $fpath = DIR_TEST.'/work/'.$this->uri_example;
    if(file_exists($fpath)){
      unlink($fpath);
    }
  }

  /**
   * connect
   */
  protected function connect(){
    $options = array('permission' => 0644);
    $this->object->connect($this->dsn,$options);
  }

  /**
   * @covers snb\file\providers\Local::connect
   * @covers snb\file\providers\Local::__construct
   */
  public function testConnect()
  {
    $this->object = new Local;
    // valid
    $this->connect();
  }
  /**
   * @covers snb\file\providers\Local::connect
   */
  public function testConnectInvalid1()
  {
    // Exceptions
    $this->setExpectedException('snb\file\Exception');
    // invalid name
    $dsn = 'invalidname://'.DIR_TEST;
    $options = array('permission' => 0644);
    $this->object->connect($dsn,$options);
  }
  /**
   * @covers snb\file\providers\Local::connect
   */
  public function testConnectInvalid2()
  {
    // Exceptions
    $this->setExpectedException('snb\file\Exception');
    // no path
    $dsn = 'local://';
    $options = array('permission' => 0644);
    $this->object->connect($dsn,$options);
  }
  /**
   * @covers snb\file\providers\Local::connect
   */
  public function testConnectInvalid3()
  {
    // Exceptions
    $this->setExpectedException('snb\file\Exception');
    // no path dir
    $dsn = 'local:///foo/foo/foo';
    $options = array('permission' => 0644);
    $this->object->connect($dsn,$options);
  }

  /**
   * @covers snb\file\providers\Local::connect
   * @covers snb\file\providers\Local::disxonnect
   */
  public function testDisconnect()
  {
    $this->object = new Local;
    $expected = new Local;
    // valid
    $this->connect();
    $this->assertNotEquals($expected,$this->object);
    $this->object->disconnect();
    $this->assertEquals($expected,$this->object);
    // reconnect
    $this->connect();
  }

  /**
   * @covers snb\file\providers\Local::get
   */
  public function testGet()
  {
    $this->connect();
    // 存在しないファイルの読み込みテスト
    $result = $this->object->get('noexist.txt');
    $this->assertNull($result);
    // 正常読み込みテスト
    $expect = file_get_contents($this->path_example);
    $result = $this->object->get($this->uri_example);
    $this->assertEquals($expect,$result,'Get fail.');
    // 保存
    $copyPath = DIR_TEST.'/work/copy.txt';
    $expect = file_get_contents($this->path_example);
    $result = $this->object->get($this->uri_example,$copyPath);
    $this->assertTrue($result);
    $this->assertEquals($expect,file_get_contents($copyPath));
    unlink($copyPath);
  }

  /**
   * @covers snb\file\providers\Local::put
   */
  public function testPut()
  {
    $putUri = 'put.txt';
    $expectedPath = DIR_TEST.'/work/put.txt';
    $this->connect();
    // 転送テスト
    $this->object->put($this->path_example,$putUri);
    $this->assertTrue(file_exists($expectedPath));
    $this->assertEquals(file_get_contents($this->path_example),file_get_contents($expectedPath));
    $perms = fileperms($expectedPath);
    $this->assertEquals('0644',substr(sprintf('%o',$perms),-4));
    unlink($expectedPath);
    // パーミッションテスト
    $this->object->put($this->path_example,$putUri,array('permission'=>0666));
    $perms = fileperms($expectedPath);
    $this->assertEquals('0666',substr(sprintf('%o',$perms),-4));
    unlink($expectedPath);
    $this->object->put($this->path_example,$putUri,array('permission'=>0600));
    $perms = fileperms($expectedPath);
    $this->assertEquals('0600',substr(sprintf('%o',$perms),-4));
    unlink($expectedPath);
  }

  /**
   * @covers snb\file\providers\Local::remove
   */
  public function testRemove()
  {
    $putUri = 'put.txt';
    $expectedPath = DIR_TEST.'/work/put.txt';
    $this->connect();
    $this->object->put($this->path_example,$putUri);
    // 最初はある
    $result = $this->object->get($putUri);
    $this->assertNotNull($result);
    // 削除後はない
    $this->object->remove($putUri);
    $result = $this->object->get($putUri);
    $this->assertNull($result);
    $this->assertFalse(file_exists($expectedPath));
  }
}

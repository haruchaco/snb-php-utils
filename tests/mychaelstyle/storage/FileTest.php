<?php
namespace mychaelstyle\storage;
require_once 'storage/File.php';
/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.1 on 2013-07-10 at 18:46:39.
 */
class FileTest extends \mychaelstyle\TestBase
{
  /**
   * @var File
   */
  public $object;
  /**
   * @var Strage
   */
  private $storage;
  /**
   * @var dsn
   */
  private $dsn;
  /**
   * @var array options common
   */
  private $options = array('permission'=>0666);
  /**
   * @var string uri
   */
  private $uri = 'tmp.txt';
  /**
   * @var string test text for writing to the tile.
   */
  private $test_string = "This is test!\nThis is test line 2.\nThis is test line 3!\n";

  /**
   * Sets up the fixture, for example, opens a network connection.
   * This method is called before a test is executed.
   */
  public function setUp()
  {
    parent::setUp();
    $this->dsn = 'Local://'.DIR_WORK;
    $this->options = array('permission'=>0666);
    $this->uri = 'tmp.txt';
    $this->storage = new \mychaelstyle\Storage($this->dsn);
    $this->object = $this->storage->createFile($this->uri,$this->options);
  }

  /**
   * Tears down the fixture, for example, closes a network connection.
   * This method is called after a test is executed.
   */
  public function tearDown()
  {
  }

  /**
   * @covers mychaelstyle\storage\File::__construct
   * @covers mychaelstyle\storage\File::open
   * @covers mychaelstyle\storage\File::initialize
   * @covers mychaelstyle\storage\File::close
   */
  public function testOpen()
  {
    $this->object->open('r');
    $this->object->close();
  }
  /**
   * @covers mychaelstyle\storage\File::__construct
   * @covers mychaelstyle\storage\File::open
   * @covers mychaelstyle\storage\File::initialize
   * @covers mychaelstyle\storage\File::close
   * @expectedException mychaelstyle\Exception
   */
  public function testOpenException()
  {
    $this->object->open('r');
    $this->object->open('r');
    $this->object->close();
  }

  /**
   * @covers mychaelstyle\storage\File::__construct
   * @covers mychaelstyle\storage\File::open
   * @covers mychaelstyle\storage\File::write
   * @covers mychaelstyle\storage\File::isOpened
   * @covers mychaelstyle\storage\File::close
   * @covers mychaelstyle\storage\File::getContents
   * @covers mychaelstyle\storage\File::commit
   * @covers mychaelstyle\storage\File::clean
   */
  public function testWriteWithoutTransaction()
  {
    // create new storage without transaction
    $this->storage = new \mychaelstyle\Storage($this->dsn,$this->options,true);
    $this->object = $this->storage->createFile($this->uri,$this->options,true);
    // none transaxtion
    $expected = $this->test_string;
    $this->object->open('w');
    $this->object->write($expected);
    $this->object->close();
    $this->assertLocalWritten($this->dsn,$expected,$this->uri);
    // get contents
    $result = $this->object->getContents();
    $this->assertEquals($expected,$result);
  }
  
  /**
   * @covers mychaelstyle\storage\File::gets
   * @covers mychaelstyle\storage\File::checkOpen
   * @covers mychaelstyle\storage\File::checkOpen
   */
  public function testGets()
  {
    $expected = $this->test_string;
    $lines = explode("\n",$expected);
    $this->object->open('r');
    $result = $this->object->gets();
    $this->assertEquals($lines[0]."\n",$result);
    $result = $this->object->gets();
    $this->assertEquals($lines[1]."\n",$result);
    $result = $this->object->gets();
    $this->assertEquals($lines[2]."\n",$result);
    $result = $this->object->gets();
    $this->object->close();
    // length
    $expected = substr($this->test_string,0,5);
    $this->object->open('r');
    $result = $this->object->gets(5);
    $this->assertEquals($expected,$result);
    $this->object->close();

  }

  /**
   * @covers mychaelstyle\storage\File::gets
   * @covers mychaelstyle\storage\File::checkOpen
   * @covers mychaelstyle\storage\File::checkOpen
   * @expectedException mychaelstyle\Exception
   */
  public function testGetsExceptionCheckOpen()
  {
    $this->object->gets();
  }

  /**
   * @covers mychaelstyle\storage\File::eof
   */
  public function testEof()
  {
    // opened and final line
    $this->object->open('r');
    while(!$this->object->eof()){
      $result = $this->object->gets();
    }
    $result = $this->object->eof();
    $this->assertEquals(true,$result);
    $this->object->close();
  }

  /**
   * @covers mychaelstyle\storage\File::__construct
   * @covers mychaelstyle\storage\File::open
   * @covers mychaelstyle\storage\File::write
   * @covers mychaelstyle\storage\File::isOpened
   * @covers mychaelstyle\storage\File::checkOpen
   * @covers mychaelstyle\storage\File::close
   * @covers mychaelstyle\storage\File::commit
   * @covers mychaelstyle\storage\File::clean
   * @covers mychaelstyle\storage\File::initialize
   * @covers mychaelstyle\storage\Storage::commit
   * @covers mychaelstyle\storage\File::remove
   * @covers mychaelstyle\storage\File::getContents
   */
  public function testWriteWithTransaction()
  {
    // remove first
    $this->object->remove();
    // create new storage without transaction
    $this->storage = new \mychaelstyle\Storage($this->dsn,$this->options,false);
    $this->object = $this->storage->createFile($this->uri,$this->options,false);
    // none transaxtion
    $expected = $this->test_string;
    $this->object->open('w');
    $this->object->write($expected);
    $this->object->close();
    $localPath = $this->getLocalPathUsingUri($this->dsn,$this->uri);
    $this->assertFalse(file_exists($localPath));
    $this->object->commit();
    $this->assertTrue(file_exists($localPath));
    $this->assertLocalWritten($this->dsn,$expected,$this->uri);
    // get contents
    $result = $this->object->getContents();
    $this->assertEquals($expected,$result);
    // remove finaly
    $this->object->remove();

  }
 
  /**
   * @covers mychaelstyle\storage\File::rollback
   * @covers mychaelstyle\storage\Storage::rollback
   * @covers mychaelstyle\storage\File::initialize
   * @covers mychaelstyle\storage\File::clean
   * @covers mychaelstyle\storage\File::remove
   * @covers mychaelstyle\storage\File::isOpened
   * @covers mychaelstyle\storage\File::checkOpen
   */
  public function testRollback()
  {
    // remove first
    $this->object->remove();
    // create new storage without transaction
    $this->storage = new \mychaelstyle\Storage($this->dsn,$this->options,false);
    $this->object = $this->storage->createFile($this->uri,$this->options,false);
    // none transaxtion
    $expected = $this->test_string;
    $this->object->open('w');
    $this->object->write($expected);
    $this->object->close();
    $localPath = $this->getLocalPathUsingUri($this->dsn,$this->uri);
    $this->assertFalse(file_exists($localPath));
    $this->object->rollback();
    $this->assertFalse(file_exists($localPath));
  }

  /**
   * @covers mychaelstyle\storage\File::import
   */
  public function testImport()
  {
    $expected = $this->getExampleContents();
    $this->object->import($this->org_example);
    $this->assertLocalWritten($this->dsn,$expected,$this->uri);
  }

  /**
   * @covers mychaelstyle\storage\File::putContents
   */
  public function testPutContents()
  {
    $expected = $this->getExampleContents();
    $this->object->putContents($expected);
    $this->assertLocalWritten($this->dsn,$expected,$this->uri);
  }

}

<?php
/**
 * File utility
 *
 * @package mychaelstyle
 * @subpackage utils
 * @auther Masanori Nakashima
 */
namespace mychaelstyle\utils;

/**
 * File utility
 *
 * @package mychaelstyle
 * @subpackage utils
 * @auther Masanori Nakashima
 */
class File {
  /**
   * @const encode bytes at once
   */
  const ENCODE_BYTE_ONCE = 240000;
 /**
   * encode binary file for saving as text
   * @param string $path
   * @param string $to
   */
  public static function base64encode($path,$to){
    self::base64($path,$to,true);
  }
  /**
   * decode encoded strings 
   * @param string $path
   * @param string $to
   */
  public static function base64decode($path,$to){
    self::base64($path,$to,false);
  }
  /**
   * base64
   */
  private static function base64($path,$to,$encode=true){
    $fp = fopen($path,'r');
    $fw = fopen($to,'w');
    if($fw && $fp){
      flock($fw,LOCK_EX);
      while(!feof($fp)){
        $bin = fread($fp,self::ENCODE_BYTE_ONCE);
        $str = null;
        if($encode){
          $str = base64_encode($bin);
        } else {
          $str = base64_decode($bin);
        }
        fwrite($fw,$str);
      }
      flock($fw,LOCK_UN);
      fclose($fp);
      fclose($fw);
    } else {
      throw new \mychaelstyle\Exception('File storage provider fail to open file!',0);
    }
  }

}

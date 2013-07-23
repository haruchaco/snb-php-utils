<?php
/**
 * string utility
 * @package mychaelstyle
 * @subpackage utils
 */
namespace mychaelstyle\utils;
/**
 * String
 * @package mychaelstyle
 * @subpackage utils
 */
class String {
  /** random elments key : number */
  const NUMBERS = 1;
  /** random elments key : lower alphabets */
  const LOWERS = 2;
  /** random elments key : upper alphabets */
  const UPPERS = 4;
  /** random elements key : marks that can be used in url strings  */
  const MARKS_URL = 8;
  /** random elments key : upper marks */
  const MARKS_ETC = 16;
  /** random elements */
  private static $RANDOM_SEEDS = array(
    self::NUMBERS     => '0123456789',
    self::UPPERS      => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
    self::LOWERS      => 'abcdefghijklmnopqrstuvwxyz',
    self::MARKS_URL   => '-_.',
    self::MARKS_ETC   => '!"#$%&`()*+,/:;<=>?@[]^\'{|}~',
  );
  /**
   * get random strings
   * @param $length
   * @param $seeds util_String::NUMBER, util_String::LOWER, util_String::UPPER, util_String::MARKS and these 'OR'
   * @return random strings
   */
  public static function getRandom($length=16,$seeds=null){
    $strings  = '';
    $seeds = (is_null($seeds) || !is_numeric($seeds))
      ? (self::NUMBERS|self::LOWERS|self::UPPERS) : $seeds;
    $seedString = '';
    foreach(self::$RANDOM_SEEDS as $key => $val){
      if(($seeds & $key) == $key){
        $seedString .= $val;
      }
    }
    $elms = str_split($seedString);
    shuffle($elms);
    for($n=0; $n<$length; $n++){
      $key  = array_rand($elms, 1);
      $strings  .= $elms[$key];
    }
    return $strings;
  }
}

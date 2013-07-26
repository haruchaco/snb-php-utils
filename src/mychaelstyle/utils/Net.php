<?php
/**
 * network utility
 * @package mychaelstyle
 * @subpackage utils
 */
namespace mychaelstyle\utils;
/**
 * mychaelstyle\Net
 * @package mychaelstyle
 * @subpackage utils
 */
class Net {
  /** Language parameter name */
  const PARAM_LANGUAGE = 'lang';
  /** Remote address */
  private static $REMOTE_ADDRESS;
  /** Remote host */
  private static $REMOTE_HOST;
  /** Language */
  private static $LANGUAGE;
  /**
   * get user agent
   * @return string user agent string
   */
  public static function getUserAgent(){
    return (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
  }
  /**
   * get remote ip address
   * @return string remote ip address
   * @access public
   */
  public static function getRemoteAddress() {
    if(!is_null(self::$REMOTE_ADDRESS)){
      return self::$REMOTE_ADDRESS;
    }
    if( isset($_SERVER['HTTP_X_FORWARDED_FOR'])
    && strlen($_SERVER['HTTP_X_FORWARDED_FOR']) > 0 ) {
      $remoteAddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
      if(strpos($remoteAddress,',')>0){
        list($remoteAddress) = explode(',',$remoteAddress);
      } else if(strpos($remoteAddress,':')>0){
        list($remoteAddress) = explode(':',$remoteAddress);
      } else if(strpos($remoteAddress,';')>0){
        list($remoteAddress) = explode(';',$remoteAddress);
      }
      self::$REMOTE_ADDRESS = trim($remoteAddress);
    } else {
      self::$REMOTE_ADDRESS = isset($_SERVER['REMOTE_ADDR'])
        ? $_SERVER['REMOTE_ADDR'] : null;
    }
    return self::$REMOTE_ADDRESS;
  }
  /**
   * get remote host
   * ApacheのHostnameLookupが無効な場合でも取得できます。
   * @return string リモートホスト名文字列
   * @access public
   */
  public static function getRemoteHost() {
    if(!is_null(self::$REMOTE_HOST)){
      return self::$REMOTE_HOST;
    }
    $remoteAddress = self::getRemoteAddress();
    if( isset($_SERVER['HTTP_X_FORWARDED_FOR'])
      && strlen($_SERVER['HTTP_X_FORWARDED_FOR']) > 0 ) {
      if(function_exists('gethostbyaddr')){
        self::$REMOTE_HOST = gethostbyaddr($remoteAddress);
      } else {
        self::$REMOTE_HOST = $remoteAddress;
      }
    } else if( isset($_SERVER['REMOTE_HOST'])
      && strlen($_SERVER['REMOTE_HOST']) > 0 ) {
      self::$REMOTE_HOST = $_SERVER['REMOTE_HOST'];
    } else if(!is_null($remoteAddress)){
      if(function_exists('gethostbyaddr')){
        self::$REMOTE_HOST = gethostbyaddr($remoteAddress);
      } else {
        self::$REMOTE_HOST = $remoteAddress;
      }
    }
    return self::$REMOTE_HOST;
  }
  /**
   * get language
   */
  public static function getLanguage(){
    if(!is_null(self::$LANGUAGE)){
      return self::$LANGUAGE;
    }
    $language = isset($_POST[self::PARAM_LANGUAGE])?$_POST[self::PARAM_LANGUAGE]:null;
    if(is_null($language) || strlen($language)==0){
      $language = isset($_GET[self::PARAM_LANGUAGE])?$_GET[self::PARAM_LANGUAGE]:null;
    }
    if(is_null($language) || strlen($language)==0){
      $language = isset($_COOKIE[self::PARAM_LANGUAGE])?$_COOKIE[self::PARAM_LANGUAGE]:null;
    }
    if((is_null($language) || strlen($language)==0) && isset($_SERVER)){
      $language = isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])?$_SERVER['HTTP_ACCEPT_LANGUAGE']:null;
      $language = strpos($language,',')>0 ? trim(substr($language,0,strpos($language,','))) : trim($language);
    }
    if(is_null($language) || strlen($language)==0){
      $language = 'en';
    }
    if( strpos($language,'_') > 0 ){
      $language  = substr($language,0,strpos($language,'_'));
    } else if ( strpos($language,'-') > 0 ) {
      $language  = substr($language,0,strpos($language,'-'));
    }
    self::$LANGUAGE = $language;
    $domain = (defined('COOKIE_DOMAIN') && strlen(COOKIE_DOMAIN)>0) ? COOKIE_DOMAIN :
      (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost');
    setcookie(self::PARAM_LANGUAGE, $language, time()+(60*60*24*30),'/',$domain);
    return self::$LANGUAGE;
  }
  /**
   * is ssl in client side
   */
  public static function isSSL(){
    if(isset($_SERVER['HTTPS']) && 'off'!=$_SERVER['HTTPS']){
      return true;
    } else if(isset($_SERVER['X_FORWARDED_PROTO'])
      && 'https'==strtolower($_SERVER['X_FORWARD_PROTO'])){
      return true;
    }
    return false;
  }
  /**
   * clear static vars
   */
  public static function clear(){
    self::$REMOTE_ADDRESS = null;
    self::$REMOTE_HOST = null;
    self::$LANGUAGE = null;
 
  }
}

<?php
namespace snb\file;
/**
 * ユーティリティパッケージ用の基底例外クラス
 * 不要かもしれない
 * 
 * @package snb\file
 */
class Exception extends \Exception {
  /**
   * Error code : unkown error
   */
  const ERROR_UNKOWN = 0;
  /**
   * Error code: file io
   */
  const ERROR_IO = 10;
  /**
   * error code: provider connection.
   */
  const ERROR_PROVIDER_CONNECTION = 20;
  /**
   * related exceptions
   */
  public $related_exceptions = array();
  /**
   * Constructor
   */
  public function __construct($message=null,$code=0,$previous=null,$relatedExceptions=array()){
    parent::__construct($message,$code,$previous);
    $this->related_exceptions = $relatedExceptions;
  }
}

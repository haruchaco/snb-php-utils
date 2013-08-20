<?php
/**
 * Mail simple wrapper
 * @package mychaelstyle
 */
namespace mychaelstyle;

/**
 * Mail simple wrapper
 * @package mychaelstyle
 */
class Mail {
  /**
   * @var array $headers
   */
  private $headers = array();
  /**
   * @var array $bodies
   */
  private $bodies  = array();
  /**
   * constructor
   */
  public function __construct($subject=null,$body=null){
    $this->headers = array();
    $this->bodies  = array();
    if(!is_null($subject)){
      $this->headers['Subject'] = $subject;
    }
    if(!is_null($body)){
      $this->bodies[] = array(
        'Content-Type' => 'text/plain;charset=UTF-8',
        'body'         => $body
      );
    }
  }
}

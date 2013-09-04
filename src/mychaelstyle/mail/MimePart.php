<?php
/**
 * Mail MIME Part object class
 * @package mychaelstyle
 * @subpackage mail
 */
namespace mychaelstyle\mail;

/**
 * Mail MIME Part object class
 * @package mychaelstyle
 * @subpackage mail
 */
class MimePart {
  /**
   * Transfer-Encoding BASE64
   */
  const ENC_BASE64 = 'base64';
  /**
   * Transfer-Encoding quoted-printable
   */
  const ENC_QP = 'quoted-printable';
  /**
   * Content-Type: text/plain
   */
  const T_TEXT = 'text/plain';
  /**
   * Content-Type text/html 
   */
  const T_HTML = 'text/html';
  /**
   * Content-Type: Multipart/Alternative
   */
  const T_ALTERNATIVE = 'Multipart/Altanative';
  /**
   * Content-Type: Multipart/Related
   */
  const T_RELATED = 'Multipart/Related';
  /**
   * Content-Type: Multipart/Mixed
   */
  const T_MIXED = 'Multipart/Mixed';
  /**
   * character set iso-2022-jp
   */
  const C_JP    = 'ISO-2022-JP';
  public $headers = array();
  public $contentType = self::T_TEXT;
  public $encSendText = self::C_JP;
  public $encTransfer = self::ENC_QP;
  public $body    = null;
  /**
   * constructor
   * @param string $contentType Content-Type
   * @param array $headers 追加ヘッダ ヘッダ名 => 値の連想配列
   */
  public function __construct($contentType=self::T_TEXT,$headers=array()){
    if(!defined('CR')){define('CR',"\r"); }
    if(!defined('LF')){ define('LF',"\n"); }
    if(!defined('CRLF')){ define('CRLF',"\r\n"); }
    $this->contentType = $contentType;
  }
  /**
   * 子MIMEパートを追加
   * @param myshaelstyle\mail\MimePart
   */
  public function addChild(MimePart $entity){
    if(!is_array($this->body)){
      $this->body = array();
    }
    $this->body[] = $entity;
  }
  /**
   * このパートに直接ボディを設定
   * @param mixed $body
   * @param string $contentType
   * @param string $encTransfer
   * @param string $encText
   */
  public function setContent($body,$contentType=null,$encTransfer=null,$encText=null){
    $this->body = $body;
    if(!is_null($contentType)){
      $this->contentType = $contentType;
    }
    if(!is_null($encTransfer)){
      $this->encTransfer = $encTransfer;
    }
    if(!is_null($encText)){
      $this->encSendText = $encText;
    }
  }
 /**
   * このパートをすべて送信用文字列として取得
   * @param string $bprefix boundaryのプレフィックス文字列
   * @return string
   */
  public function get($bprefix='MychaelStyleMailer_'){
    $text = '';
    $body = $this->getBody();
    foreach($this->headers as $name => $value){
      $text .= $name.': '.$this->getEncodedHeader($name).CRLF;
    }
    if(!is_array($body)){
      // single engity
      // create headers
      $text .= 'Content-Type: '.$this->contentType
        .'; charset="'.$this->getSendEncoding().'"'.CRLF;
      if(!is_null($this->encTransfer) && strlen($this->encTransfer)>0){ 
        $text .= 'Content-Transfer-Encoding: '.$this->encTransfer.CRLF;
      }
      $text .= CRLF;
      // create body
      if(self::ENC_QP==$this->encTransfer){
        $text .= \mychaelstyle\Mail::quotePrintable($body);
      } else if(self::ENC_BASE64==$this->encTransfer){
        $text .= base64_encode($body);
      } else {
        $text .= $body;
      }
      return $text;
    } else {
      // multipart entities
      $boundary = $bprefix.substr(uniqid("b"),0,5);
      if($this->hasHtmlPart()){
        $this->contentType = self::T_ALTERNATIVE;
      } else if($this->hasRelatedContents()){
        $this->contentType = self::T_RELATED;
      } else {
        $this->contentType = self::T_MIXED;
      }
      $text .= 'Content-Type: '.$this->contentType.'; boundary="'.$boundary.'"'.CRLF;
      $text .= CRLF;
      foreach($body as $entity){
        $text .= '--'.$boundary.CRLF;
        $text .= $entity->get($boundary);
        $text .= CRLF.CRLF;
      }
      $text .= '--'.$boundary.'--'.CRLF.CRLF;
      return $text;
    }
  }
  /**
   * 設定されているヘッダ内容をエンコードして取得
   * @return string エンコード済みヘッダ文字列
   */
  public function getEncodedHeader($name){
    $value = $this->headers[$name];
    if('subject'==strtolower($name)){
      $value = $this->encodeHeader($value);
    } else {
      $value = mb_encode_mimeheader($value);
    }
    return $value;
  }
  /**
   * ヘッダエンコード
   */
  public function encodeHeader($val){
    if($this->encSendText != mb_detect_encoding($val)){
      $val = mb_convert_encoding($val,$this->encSendText,'auto');
    }
    $val = base64_encode($val);
    return '=?'.$this->getSendEncoding().'?B?'.$val.'?=';
  }
  /**
   * メールボディを取得
   * @return mixed 子要素がある場合は配列。シングルコンテンツの場合は文字列
   */
  protected function getBody(){
    if($this->isTextFormat()){
      if(!is_array($this->body)
        && strtolower($this->encSendText)!=strtolower(mb_detect_encoding($this->body))){
        return mb_convert_encoding($this->body,$this->encSendText,'auto');
      }
    }
    return $this->body;
  }
  /**
   * ボディの文字コード変換が必要か確認のためボディがテキスト形式かヘッダから確認
   * @return boolean
   */
  protected function isTextFormat(){
    return (self::T_TEXT==$this->contentType
      || self::T_HTML==$this->contentType);
  }
  /**
   * 関連のあるコンテンツを保持しているか確認(inline image or others)
   * @return boolean
   */
  protected function hasRelatedContents(){
    $body = $this->getBody();
    if(is_array($body)){
      foreach($body as $child){
        if(array_key_exists('Content-Id',$child->headers)){
          return true;
        }
      }
    }
    return false;
  }
  /**
   * 子要素がHTMLコンテンツを保持しているか確認
   * @return boolean
   */
  protected function hasHtmlPart(){
    $body = $this->getBody();
    if(is_array($body)){
      foreach($body as $child){
        if(preg_match('/text\\/html/',strtolower($child->contentType))>0){
          return true;
        }
      }
    }
    return false;
  }
  /**
   * 送信時の文字コードを取得。テキストかHTMLの場合のみ
   * @return boolean
   */
  protected function getSendEncoding(){
    if(preg_match('/^SJIS/',$this->encSendText)>0){
      return 'Shift_JIS';
    }
    return $this->encSendText;
  }
}

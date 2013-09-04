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
  const XMAILER = 'Mychaelstyle PHP Mail; https://github.com/mychaelstyle/;';
  private $from   = null;
  private $reply  = null;
  private $return = null;
  private $textBody = null;
  private $htmlBody = null;
  private $headers  = array();
  /**
   * @var array $attatchements
   */
  private $attachments = array();
  /**
   * @var array $implements
   */
  private $implements = array();
  /**
   * boundary
   */
  private $boundary;
  /**
   * constructor
   */
  public function __construct($xMailer=self::XMAILER){
    $this->headers = array(
      'MIME-Version'              => '1.0',
      'Content-Transfer-Encoding' => '7bit',
      'X-Mailer'                  => $xMailer
    );
    $this->boundary = substr(uniqid("b"),0,10);
  }

  protected function build(){

  }
  protected function addMimeTypeHeaders(){
    $this->headers['MIME-Version'] = '1.0';
    $this->headers['Content-Transfer-Encoding'] = '7bit';
  }
  protected function type(){
    if(!empty($this->htmlBody)){
      $this->addMimeTypeHeaders();
      // html mail
      if(count($this->attachements)>0){

      } else if(count($this->implements)>0){

      } else {
        
      }
    } else {
      // text mail
      if(count($this->attachements)>0){
        $this->addMimeTypeHeaders();

      } else {

      }
    }
  }

  /**
   * 文字列をquoted printableエンコードして返します。
   * @param string $str エンコード前文字列
   * @return string エンコード後文字列
   */
  public static function quotePrintable($str){
    $str    = trim($str);
    $str    = str_replace("\r\n","\n",$str);
    $lines    = preg_split("/\r?\n/", $str);
    $retStrings  = '';
    foreach ( $lines as $line ) {
      $lineDec  = '';
      for( $i=0; $i < strlen($line); $i++ ) {
        $char  = substr( $line, $i, 1 );
        $ascii  = ord( $char );
        if ( $ascii < 32 || $ascii == 61 || $ascii > 126 ) {
          $char  = '='.strtoupper( dechex( $ascii ) );
        }
        if ( ( strlen ( $lineDec ) + strlen ( $char ) ) >= 76 ) {
          $retStrings .= $lineDec.'='.CRLF;
          $lineDec  = '';
        }
        $lineDec  .= $char;
      }
      if( strlen(trim($lineDec)) > 0 ) {
        $retStrings  .= $lineDec.CRLF;
      } else {
        $retStrings  .= '=0A=0D='.CRLF;
      }
    }
    return trim($retStrings);
  }
}

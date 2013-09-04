<?php
/**
 * Mail MIME Part object class
 * @package mychaelstyle
 * @subpackage mail
 */
namespace mychaelstyle\mail;

use mychaelstyle\utils\File;

/**
 * Mail MIME Part object class
 * @package mychaelstyle
 * @subpackage mail
 */
class MimeBin extends MimePart {
  const T_PNG = 'image/png';
  const T_JPG = 'image/jpeg';
  const T_GIF = 'image/gif';
  /**
   * @var $filePath file path
   */
  private $filePath = null;
  /**
   * set file
   * @param string $filePath
   * @param string $contentType
   */
  public function setFile($filePath,$contentType,$encText=self::C_JP){
    $this->filePath = $filePath;
    $this->contentType = $contentType;
    $this->encTransfer = self::ENC_BASE64;
    $this->encSendText = $encText;
  }

  public function get($bprefix='MychaelStyleMailer_'){
    $fileName = basename($this->filePath);
    $fileName = $this->encodeHeader($fileName);
    $text = '';
    foreach($this->headers as $name => $value){
      $text .= $name.': '.$this->getEncodedHeader($name).CRLF;
    }
    $text .= 'Content-Type: '.$this->contentType.';'.CRLF;
    $text .= 'Content-Transfer-Encoding: '.$this->encTransfer.';'.CRLF;
    $text .= 'Content-Disposition: attachment; filename="'.$fileName.'"'.CRLF;
    $text .= CRLF;

    $tmp = tempnam(sys_get_temp_dir(),'mychaelstyle_mail_');
    File::base64encode($this->filePath,$tmp);
    $str = file_get_contents($tmp);
    unlink($tmp);
    $text .= $str;
    
    return $text;
  }
}

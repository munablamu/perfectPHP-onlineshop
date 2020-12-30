<?php

/**
 * Class to manage a server response.
 *
 * @var mixed  $content
 * @var int    $status_code = 200
 * @var string $status_text = 'OK'
 * @var array  $http_headers = array()
 */
class Response
{
  protected $content;
  protected $status_code  = 200;
  protected $status_text  = 'OK';
  protected $http_headers = array();

  /**
   * Send a response.
   *
   * @param  bool $debug
   * @return void
   */
  public function send($debug)
  {
    header('HTTP/2 ' . $this->status_code . ' ' . $this->status_text);

    foreach ( $this->http_headers as $name => $value ) {
      header($name . ': ' . $value);
    }
    unset($name); unset($value);

    echo $this->content;

    if ( $debug ) {
      var_dump($_SESSION);
    }
  }

  /**
   * Set a content such as HTML.
   *
   * @return void
   */
  public function setContent($content)
  {
    $this->content = $content;
  }

  /**
   * Set a status
   *
   * @param  int    $status_code
   * @param  string $status_text
   * @return void
   */
  public function setStatusCode($status_code, $status_text = '')
  {
    $this->status_code = $status_code;
    $this->status_text = $status_text;
  }

  /**
   * Set HTTP header
   *
   * @param  string $name
   * @param  string $value
   * @return void
   */
  public function setHttpHeader($name, $value)
  {
    $this->http_headers[$name] = $value;
  }
}

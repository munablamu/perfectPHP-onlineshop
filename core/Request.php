<?php

/**
 * Class to controll client request information.
 */
class Request
{
  /**
   * Check if the HTTP method is POST or not.
   *
   * @return bool
   */
  public function isPost()
  {
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
      return true;
    }

    return false;
  }

  /**
   * Get a variable passed to the current script in URL parameters (query strings).
   *
   * @param  string $key
   * @param  string $default = null
   * @return string|null
   */
  public function getGet($key, $default = null)
  {
    return $_GET[$key] ?? $default;
  }

  /**
   * Get a variable passed to the current script from the HTTP POST method.
   *
   * @param  string $key
   * @param  string $default = null
   * @return string|null
   */
  public function getPost($key, $default = null)
  {
    return $_POST[$key] ?? $default;
  }

  /**
   * Get the server hostname.
   *
   * @return string
   */
  public function getHost()
  {
    if ( !empty($_SERVER['HTTP_HOST']) ) {
      return $_SERVER['HTTP_HOST'];
    }

    return $_SERVER['SERVER_NAME'];
  }

  /**
   * Check if the site was accessed using HTTPS.
   *
   * @return bool
   */
  public function isSel()
  {
    if ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ) {
      return true;
    }

    return false;
  }

  /**
   * Get requested URI.
   *
   * @return string
   */
  public function getRequestUri()
  {
    return $_SERVER['REQUEST_URI'];
  }

  /**
   * Get requested base url. (URL: http://hostname/base-url/path-info?query)
   *
   * @return string
   */
  public function getBaseUrl()
  {
    $script_name = $_SERVER['SCRIPT_NAME'];
    $request_uri = $this->getRequestUri();

    if ( 0 === strpos($request_uri, $script_name) ) {
      return $script_name;
    } elseif ( 0 === strpos($request_uri, dirname($script_name)) ) {
      return rtrim(dirname($script_name), '/');
    }

    return '';
  }

  /**
   * Get requested path info. (URL: http://hostname/base-url/path-info?query)
   *
   * @return string
   */
  public function getPathInfo()
  {
    $base_url    = $this->getBaseUrl();
    $request_uri = $this->getRequestUri();

    if ( false !== ($pos = strpos($request_uri, '?')) ) {
      $request_uri = substr($request_uri, 0, $pos);
    }

    $path_info = (string)substr($request_uri, strlen($base_url));

    return $path_info;
  }

  /**
   * get HTTP request method.
   *
   * @return string|bool
   */
  public function getRequestMethod()
  {
    if ( $_SERVER['REQUEST_METHOD'] === 'GET' ) {
      return 'get';
    } elseif ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
      if ( $this->getPost('_method') === null ) {
        return 'post';
      } elseif ( $this->getPost('_method') === 'DELETE' ) {
        return 'delete';
      } elseif ( $this->getPost('_method') === 'PATCH' ) {
        return 'patch';
      }
    }

    return false;
  }
}

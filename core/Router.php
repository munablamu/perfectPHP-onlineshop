<?php

/**
 * Class to identify controllers and actions from PATH_INFO
 *
 * @var array $routes
 */
class Router
{
  protected $routes;

  /**
   * Constructor.
   *
   * @param array $definitions routing defined array
   */
  public function __construct($definitions)
  {
    $this->routes = $this->compileRoutes($definitions);
  }

  /**
   * Converting dynamic parameter specifications in the routing definition array to a regex format.
   *
   * @param  array $definitions routing defined array
   * @return array
   */
  public function compileRoutes($definitions)
  {
    $routes = array();

    $this->explodeRestAction($definitions);
    $this->compileHTTPRequestMethod($definitions);


    foreach ( $definitions as $url => $params ) {
      $tokens = $this->explodeTokens($url);

      $this->compileDynamicToken($tokens);

      $pattern = $this->implodeTokens($tokens);

      $routes[$pattern] = $this->setRouteParams($params);
    }
    unset($url, $params);

    return $routes;
  }

  /**
   * Identigy routing parameters from PATH_INFO.
   *
   * @param  string $path_info
   * @param  string $request_method
   * @return array|bool
   */
  public function resolve($path_info, $request_method)
  {
    if ( '/' !== substr($path_info, 0, 1) ) {
      $path_info = '/' . $path_info;
    }
    $request = $path_info . '/@' . $request_method;

    foreach ( $this->routes as $pattern => $params ) {
      if ( preg_match('#^' . $pattern . '$#', $request, $matches) ) {
        $params = array_merge($params, array_map('urldecode', $matches));

        return $params;
      }
    }
    unset($pattern); unset($params);

    return false;
  }

  /**
   * undocumented function
   *
   * @param  array &$definitions
   * @return void
   */
  protected function explodeRestAction(&$definitions)
  {
    foreach ( $definitions as $url => $params ) {
      $tokens = $this->explodeTokens($url);

      $token = end($tokens);
      if ( 0 === strpos($token, '%') ) {
        if ( substr($token, 0, 5) === '%rest' ) {
          if ( $token === '%rest' ) {
            $token .= '[index,show,new,create,edit,update,destroy]';
          } elseif ( substr($token, 0, 6) === '%rest[' ) {
            $token = str_replace('[', '[index,show,new,create,edit,update,destroy,', $token);
          }
        }

        $token = strstr(substr(strstr($token, '['), 1), ']', true);
        $actions = explode(',', $token);

        foreach ( $actions as $action ) {
          $action = trim($action);
          $tokens[count($tokens)-1] = $this->getEndToken($action);
          $params['action'] = $action;
          $_url = $this->implodeTokens($tokens);
          $definitions[$_url] = $params;
        }
        unset($action);

        unset($definitions[$url]);
      }

    }
    unset($url); unset($params);
  }

  /**
   * undocumented function
   *
   * @param  array &$tokens
   * @return void
   */
  protected function compileDynamicToken(&$tokens)
  {
    foreach ( $tokens as $i => $token ) {
      if ( 0 === strpos($token, ':') ) {
        $name  = substr($token, 1);
        $token = '(?P<' . $name . '>[^/]+)';
      }
      $tokens[$i] = $token;
    }
    unset($i); unset($token);
  }

  /**
   * undocumented function
   *
   * @param  array &$definitions
   * @return void
   */
  protected function compileHTTPRequestMethod(&$definitions)
  {
    $params_array = array_values($definitions);
    $url_array = array();

    foreach ( $definitions as $url => $params ) {
      // HTTP request method
      if ( false === strpos($url, '@') ) {
        $url = $url . '@get';
      }
      $url_array[] = str_replace('@', '/@', $url);
    }

    $definitions = array_combine($url_array, $params_array);
  }

  /**
   * undocumented function
   *
   * @param  array $params
   * @return array
   */
  protected function setRouteParams($params)
  {
    $_params = array();
    foreach ( $params as $key => $value ) {
      $_params['_' . $key] = $value;
    }
    unset($key); unset($value);

    return $_params;
  }

  /**
   * undocumented function
   *
   * @param  string $action
   * @return string
   */
  protected function getEndToken($action)
  {
    switch ($action) {
      case 'index':
        $end_token = '@get';
        break;
      case 'show':
        $end_token = ':id@get';
        break;
      case 'new':
        $end_token = 'new@get';
        break;
      case 'create':
        $end_token = 'new@post';
        break;
      case 'edit':
        $end_token = ':id/edit@get';
        break;
      case 'update':
        $end_token = ':id@patch';
        break;
      case 'destroy':
        $end_token = ':id@delete';
        break;
      default:
        if ( strpos($action, '@') === false ) {
          $end_token = $action . '@get';
        } else {
          $end_token = $action;
        }
        break;
    }

    return $end_token;
  }

  /**
   * undocumented function
   *
   * @param  string $string
   * @return array
   */
  protected function explodeTokens($string)
  {
    $tokens = explode('/', $string);
    array_shift($tokens);

    return $tokens;
  }

  /**
   * undocumented function
   *
   * @param  array  $tokens
   * @return string
   */
  protected function implodeTokens($tokens)
  {
    return '/' . implode('/', $tokens);
  }
}

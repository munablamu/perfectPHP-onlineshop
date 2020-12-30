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

    foreach ( $definitions as $url => $params ) {
      $tokens = explode('/', ltrim($url, '/'));

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

          switch ($action) {
            case 'index':
              $rest_token = '@get';
              break;
            case 'show':
              $rest_token = ':id@get';
              break;
            case 'new':
              $rest_token = 'new@get';
              break;
            case 'create':
              $rest_token = 'new@post';
              break;
            case 'edit':
              $rest_token = ':id/edit@get';
              break;
            case 'update':
              $rest_token = ':id@patch';
              break;
            case 'destroy':
              $rest_token = ':id@delete';
              break;
            default:
              if ( strpos($action, '@') === false ) {
                $rest_token = $action . '@get';
              } else {
                $rest_token = $action;
              }
              break;
          }

          $params['action'] = $action;

          $_tokens = $tokens;
          $_tokens[count($_tokens)-1] = $rest_token;
          $_url = '/' . implode('/', $_tokens);
          $definitions[$_url] = $params;
        }
        unset($action);

        unset($definitions[$url]);
      }
    }
    unset($url); unset($params);

    foreach ( $definitions as $url => $_params ) {
      $tokens = explode('/', ltrim($url, '/'));

      foreach ( $tokens as $i => $token ) {
        if ( 0 === strpos($token, ':') ) {
          $name  = substr($token, 1);
          $token = '(?P<' . $name . '>[^/]+)';
        }
        $tokens[$i] = $token;
      }
      unset($i); unset($token);

      // HTTP request method
      $end_token = end($tokens);
      if ( false === strpos($end_token, '@') ) {
        $end_token .= '@get';
      }
      $end_token = str_replace('@', '/@', $end_token);
      $tokens[count($tokens)-1] = $end_token;

      $pattern = '/' . implode('/', $tokens);

      $params = array();
      foreach ( $_params as $key => $value ) {
        $params['_' . $key] = $value;
      }
      unset($key); unset($value);

      $routes[$pattern] = $params;
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
}

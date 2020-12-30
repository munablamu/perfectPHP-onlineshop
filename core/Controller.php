<?php

/**
 * Class to manage related actions.
 *
 * @var string      $controller_name
 * @var string      $action_name
 * @var Application $application
 * @var Request     $request
 * @var Response    $response
 * @var Session     $session
 * @var DbManager   $db_manager
 * @var array|bool  $auth_actions array of $action or true.
 * @var array $flash
 * @var array $before_actions = array()
 * @var array $after_actions = array()
 */
abstract class Controller
{
  protected $controller_name;
  protected $action_name;
  protected $application;
  protected $request;
  protected $response;
  protected $session;
  protected $db_manager;
  protected $auth_actions = array();
  protected $flash = array();
  protected $before_actions = array();
  protected $after_actions = array();
  protected const FLASH_TYPES
    = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
  protected const CSRF_TOKEN_SAVE_NUM = 10;
  protected const CSRF_TOKEN_EXPIRE = 1800;
  protected const CSRF_TOKEN_LENGTH = 32;

  /**
    * Constructor.
    *
    * @param Application $application
    */
  public function __construct($application)
  {
    $controller_len = strlen('Controller');
    $this->controller_name = strtolower(substr(get_class($this), 0, -$controller_len));

    $this->application = $application;
    $this->request     = $application->getRequest();
    $this->response    = $application->getResponse();
    $this->session     = $application->getSession();
    $this->db_manager  = $application->getDbManager();
  }

  /**
   * Execute action
   *
   * @param  string $action
   * @param  array  $params = array()
   * @return string
   */
  public function run($action, $params = array())
  {
    $this->action_name = $action;
    $this->sessionFlashData2flash();

    $action_method = $this->action_name . 'Action';
    if ( ! method_exists($this, $action_method) ) {
      $this->forward404();
    }

    if ( $this->needsAuthentication($action) && !$this->session->isAuthenticated() )
    {
      throw new UnauthorizedActionException();
    }

    foreach ( $this->before_actions as $before_action => $actions ) {
      if ( in_array($action, $actions, true) ) {
        $this->$before_action();
      }
    }

    $content = $this->$action_method($params);

    foreach ( $this->after_actions as $after_action => $actions ) {
      if ( in_array($action, $actions, true) ) {
        $this->$after_action();
      }
    }

    return $content;
  }

  /**
   * Load the view file corresponding to $this->action_name or $template. Wrapper for View.render()
   * method.
   *
   * @param  array  $variables = array()
   * @param  string $template = null
   * @param  string $layout = 'layout'
   * @return string
   */
  protected function render($variables = array(), $template = null, $layout = 'layout')
  {
    $defaults = [
      'request'         => $this->request,
      'base_url'        => $this->request->getBaseUrl(),
      'session'         => $this->session,
      'root_dir'        => $this->application->getRootDir(),
      'stylesheets_dir' => '../app/assets/stylesheets/',
      'css_dir'         => 'css/',
      'flash'           => $this->flash,
    ];

    $view = new View($this->application->getViewDir(), $defaults);

    if ( is_null($template) ) {
      $template = $this->action_name;
    }

    $path = $this->controller_name . '/' . $template;

    return $view->render($path, $variables, $layout);
  }

  /**
   * Notify HttpNotFoundException and prompt for transition to 404 error page.
   *
   * @throws HttpNotFoundException
   * @return void
   */
  protected function forward404()
  {
    throw new HttpNotFoundException(
      'Forward 404 page from ' . $this->controller_name . '/' . $this->action_name
    );
  }

  /**
   * Set a redirection to the specified URL in Response instance.
   * If you want to redirect different actions in the same application, you can specify PATH_INFO
   * only.
   *
   * @param  string $url
   * @return void
   */
  protected function redirect($url)
  {
    // in the case of relative URL
    if ( !preg_match('#https?://#', $url) ) {
      $protocol = $this->request->isSel() ? 'https://' : 'http://';
      $host     = $this->request->getHost();
      $base_url = $this->request->getBaseUrl();

      $url = $protocol . $host . $base_url . $url;
    }

    $this->response->setStatusCode(302, 'Found');
    $this->response->setHttpHeader('Location', $url);
  }

  /**
   * Check if a login authentication is required.
   *
   * @param  string $action
   * @return bool
   */
  protected function needsAuthentication($action)
  {
    if ( $this->auth_actions === true
        || (is_array($this->auth_actions) && in_array($action, $this->auth_actions, true)) ) {
      return true;
    }

    return false;
  }

  /**
   * Generate a token, store it in the session, and then return it.
   *
   * @param  string $form_name
   * @param  int    $token_length
   * @return string
   */
  protected function generateCsrfToken($form_name, $token_length=self::CSRF_TOKEN_LENGTH)
  {
    $key    = 'csrf_tokens/' . $form_name;
    $tokens = $this->session->get($key, array());

    if ( count($tokens) >= self::CSRF_TOKEN_SAVE_NUM ) {
      array_shift($tokens);
    }

    $token    = bin2hex(random_bytes($token_length));
    $now      = new DateTimeImmutable();
    $tokens[] = ['token' => $token, 'datetime' => $now];

    $this->session->set($key, $tokens);

    return $token;
  }

  /**
   * Return the result of comparing the token in the request with the one stored in the session, and
   * remove it from the session.
   *
   * @param  string $form_name
   * @param  string $token
   * $param  int    $expire
   * @return bool
   */
  protected function checkCsrfToken($form_name, $token, $expire=self::CSRF_TOKEN_EXPIRE)
  {
    $key    = 'csrf_tokens/' . $form_name;
    $tokens = $this->session->get($key, array());

    $now = new DateTimeImmutable();
    $_tokens = array_column($tokens, 'token');

    if ( false !== ($pos = array_search($token, $_tokens, true)) ) {
      $token_time = $tokens[$pos]['datetime'];
      unset($tokens[$pos]);
      $this->session->set($key, array_values($tokens));
      if ( $now->getTimestamp() - $token_time->getTimestamp() < $expire ) {
        $this->session->unset($key);

        return [true, null];
      }
      return [false, 'expired'];
    }

    return[false, 'unauthorized'];
  }

  /**
   * Set flash message now.
   *
   * @param  string $type
   * @param  string $message
   * @return void
   * @throws InvalidArgumentException
   */
  public function setFlashNow($type, $message)
  {
    if ( in_array($type, self::FLASH_TYPES, true) ) {
      $this->flash[$type] = $message;
      $this->session->unset('flash/' . $type);
    } else {
      throw new InvalidArgumentException('ERROR: Flash message type is invalid.' . $type);
    }
  }

  /**
   * Set flash message.
   *
   * @param  string $type
   * @param  string $message
   * @return void
   * @throws InvalidArgumentException
   */
  public function setFlash($type, $message)
  {
    if ( in_array($type, self::FLASH_TYPES, true) ) {
      $this->session->set('flash/' . $type, $message);
    } else {
      throw new InvalidArgumentException('ERROR: Flash message type is invalid.' . $type);
    }
  }

  /**
   * undocumented function
   *
   * @return void
   */
  public function sessionFlashData2flash()
  {
    foreach ( self::FLASH_TYPES as $flash_type ) {
      if ( $this->session->isset('flash/' . $flash_type) ) {
        $this->flash[$flash_type] = $this->session->get('flash/' . $flash_type);
        $this->session->unset('flash/' . $flash_type);
      }
    }
  }
}

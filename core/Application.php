<?php

/**
 * [Abstract] Class to manage the entire application.
 * - manage instance of Request, Router, Response, Session, DbManager.
 * - define routing, execute controller, send response, etc.
 * - manage the path to the application directory.
 * - manage debug mode.
 *
 * @var bool      $debug = false
 * @var Request   $request
 * @var Response  $response
 * @var Session   $session
 * @var DbManager $db_manager
 * @var Router    $router
 * @var array     $login_action = array() [$controller_name, $action]
 */
abstract class Application
{
  protected $debug = false;
  protected $request;
  protected $response;
  protected $session;
  protected $db_manager;
  protected $router;
  protected $login_action = array();

  /**
   * Constructor.
   *
   * @param bool $debug = false
   */
  public function __construct($debug = false)
  {
    $this->setDebugMode($debug);
    $this->initialize();
    $this->configure();
  }

  /**
   * Set debug mode. Change the error display process.
   *
   * @param  bool $debug
   * @return void
   */
  protected function setDebugMode($debug)
  {
    if ( $debug ) {
      $this->debug = true;
      ini_set('display_errors', 'On');
      error_reporting(E_ALL);
    } else {
      $this->debug = false;
      ini_set('display_errors', 'Off');
    }
  }

  /**
   * Initialize application.
   *
   * @return void
   */
  protected function initialize()
  {
    $this->request    = new Request();
    $this->response   = new Response();
    $this->session    = new Session();
    $this->db_manager = new DbManager();
    $this->router     = new Router($this->registerRoutes());
  }

  /**
   * Set by individual application.
   *
   * @return void
   */
  protected function configure()
  {
    // pass
  }

  /**
   * [Abstract] Return the path to root directory of the application. It is provided so that the
   * sdirectory tructure can be changed arbitrarily if necessary.
   */
  abstract public function getRootDir();

  /**
   * [Abstract] Return a routing definition array for each individual application.
   */
  abstract public function registerRoutes();

  /**
   * Check if it is debug mode.
   *
   * @return bool
   */
  public function isDebugMode()
  {
    return $this->debug;
  }

  /**
   * Get request instance.
   *
   * @return Request
   */
  public function getRequest()
  {
    return $this->request;
  }

  /**
   * Get response instance.
   *
   * @return Response
   */
  public function getResponse()
  {
    return $this->response;
  }

  /**
   * Get session instance.
   *
   * @return Session
   */
  public function getSession()
  {
    return $this->session;
  }

  /**
   * Get DbManager instance.
   *
   * @return DbManager
   */
  public function getDbManager()
  {
    return $this->db_manager;
  }

  /**
   * Get Router instance.
   *
   * @return Router
   */
  public function getRouter()
  {
    return $this->router;
  }

  /**
   * Get controller directory.
   *
   * @return string
   */
  public function getControllerDir()
  {
    return $this->getRootDir() . '/app/controllers';
  }

  /**
   * Get view directory.
   *
   * @return string
   */
  public function getViewDir()
  {
    return $this->getRootDir() . '/app/views';
  }

  /**
   * Get model directory.
   *
   * @return string
   */
  public function getModelDir()
  {
    return $this->getRootDir() . '/app/models';
  }

  /**
   * Get web directory.
   *
   * @return string
   */
  public function getWebDir()
  {
    return $this->getRootDir() . '/web';
  }

  /**
   * Trigger the application to respond to the user request and return a response.
   *
   * @return void
   */
  public function run()
  {
    try {
      $path_info      = $this->request->getPathInfo();
      $request_method = $this->request->getRequestMethod();
      $params         = $this->router->resolve($path_info, $request_method);

      if ( $params === false ) {
        throw new HttpNotFoundException('No route found for ' . $path_info .
                                        ', method: ' . $request_method);
      }

      $controller = $params['_controller'];
      $action     = $params['_action'];

      $this->runAction($controller, $action, $params);

    } catch ( HttpNotFoundException $e ) {
      $this->render404Page($e);

    } catch ( UnauthorizedActionException $e ) {
      $this->runAction($this->login_action['controller'],
                       $this->login_action['action']);
    }

    $this->response->send($this->debug);
  }

  /**
   * Take a controller name and an action name, and execute the action.
   *
   * @param  string $controller_name
   * @param  string $action
   * @param  array  $params = array()
   * @return void
   */
  public function runAction($controller_name, $action, $params = array())
  {
    $controller_class = ucfirst($controller_name) . 'Controller';

    $controller = $this->findController($controller_class);
    if ( $controller === false ) {
      throw new HttpNotFoundException($controller_class . ' controller is not found.');
    }

    $content = $controller->run($action, $params);
    $this->response->setContent($content);
  }

  /**
   * Load the class file when the controller class is not loaded.
   *
   * @param  string     $controller_class
   * @return Controller
   */
  protected function findController($controller_class)
  {
    if ( !class_exists($controller_class) ) {
      $controller_file = $this->getControllerDir() . '/' . $controller_class . '.php';

      if ( !is_readable($controller_file) ) {
        return false;
      } else {
        require_once $controller_file;

        if ( !class_exists($controller_class) ) {
          return false;
        }
      }
    }

    return new $controller_class($this);
  }

  /**
   * Render 404 Not Found Page
   *
   * @param  Exception $e
   * @return void
   */
  protected function render404Page($e)
  {
    $this->response->setStatusCode(404, 'Not Found');
    $message = $this->isDebugMode() ? $e->getMessage() : 'Page not found.';
    $variables = [
      'request'         => $this->request,
      'base_url'        => $this->request->getBaseUrl(),
      'session'         => $this->session,
      'root_dir'        => $this->getRootDir(),
      'stylesheets_dir' => '../app/assets/stylesheets/',
      'css_dir'         => 'css/',
      'flash'           => array(),
      'message'         => $message,
    ];

    $content = (new View($this->getViewDir(), $variables))
      ->render('error/404_notfound', array(), 'error/error_layout');
    $this->response->setContent($content);
  }
}

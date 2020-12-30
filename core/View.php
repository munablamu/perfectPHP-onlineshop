<?php

/**
 * Class to controll view file laoding and variables passed to the view file.
 *
 * @var string $base_dir
 * @var array  $defaults
 * $var array  $layout_variables = array()
 */
class View
{
  protected $base_dir;
  protected $defaults;
  protected $layout_variables = array();

  /**
   * Constructor.
   *
   * @param string $base_dir the absolute path to the views directory (i.e. path/to/views)
   * @param array  $defaults
   */
  public function __construct($base_dir, $defaults = array())
  {
    // the absolute path to the views directory (i.e. path/to/views/user)
    $this->base_dir = rtrim($base_dir, '/');
    // when render() method is called in the view file, specify the values you want to use in all
    // loaded view files, in $defaults.
    $this->defaults = $defaults;
  }

  /**
   * Set the values you want to include in the layout file. (i.e. title)
   *
   * @param  string $name
   * @param  string $value
   * @return void
   */
  public function setLayoutVar($name, $value)
  {
    $this->layout_variables[$name] = $value;
  }

  /**
   * Load the view file.
   * [NOTICE]
   * To avoid collisions of variable names when extracting variables by extract() in render(), all
   * variables are marked with underscores.
   *
   * @param  string $_path
   * @param  array  $_variables
   * @param  bool   $_layout
   * @return string
   */
  public function render($_path, $_variables = array(), $_layout = false)
  {
    $_file = $this->base_dir . '/' . $_path . '.php';

    extract(array_merge($this->defaults, $_variables));

    ob_start();
    ob_implicit_flush(0);

    // store the contents of the view file in the output buffer
    require $_file;

    // extract buffer contents to $content
    $_content = ob_get_clean();

    // Combine the layout files if necessary
    if ( $_layout ) {
      $_content = $this->render($_layout,
                                array_merge($this->layout_variables,
                                            ['__content' => $_content]));
    }

    return $_content;
  }

  /**
   * HTML special character escape.
   *
   * @param  string $string
   * @return string
   */
  public function escape($string)
  {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
  }
}

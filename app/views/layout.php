<?php
require $root_dir . '/vendor/autoload.php';

if($_SERVER['SERVER_NAME'] == 'localhost') {
  SassCompiler::run($stylesheets_dir, $css_dir);
}
?>

<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>WebApplication</title>
    <link rel="stylesheet" type="text/css" media="screen" href="/css/modern-css-reset.css">
    <link rel="stylesheet" type="text/css" media="screen" href="/css/style.css">
  </head>
  <body>
    <main>
      <?= $this->render('_flash'); ?>
      <?= $__content; ?>
    </main>
  </body>
</html>

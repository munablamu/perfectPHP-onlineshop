<?php
require 'vendor/autoload.php';
try{
  $pdo = new PDO('mysql:dbname=',
    'root', '',
    array(PDO::ATTR_PERSISTENT => true));
  $pdo->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

  $seeder = new \munablamu\dbseeder\Seeder($pdo);
  $generator = $seeder->getGeneratorConfigurator();
  $faker = $generator->getFakerConfigurator();

  $stmt = $pdo->prepare("SET FOREIGN_KEY_CHECKS=0");
  $stmt->execute();

  #$seeder->table('user')->columns([
  #  'id', //automatic pk
  #  'user_name'  => $faker->name,
  #  'password'   => password_hash('password', PASSWORD_ARGON2ID),
  #  'created_at' => function(){
  #                    return date('Y-m-d H:i:s');
  #                  },
  #])->rowQuantity(30);
  #$seeder->allowDuplicate('user', true);

  $seeder->refill();

  $stmt = $pdo->prepare("SET FOREIGN_KEY_CHECKS=1");
  $stmt->execute();

} catch ( Exception $e ) {
  echo '<pre>';print_r($e);echo '</pre>';
}


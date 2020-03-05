<?php

// Настройки
ini_set('display_errors',1);
error_reporting(E_ALL);

define('ROOT', dirname(__FILE__));
require_once(ROOT.'/Autoload.php');

try
{
  // Запускаем Router
  $router = new Router();
  $router->run();
}
catch (Exception $exc)
{
  // Выводим сообщение об ошибке, если выброшено исключение
  echo json_encode(Array('error' => $exc->getMessage()));
}

?>

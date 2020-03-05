<?php

require_once(ROOT . '/Config/db_params.php');

/**
 * Класс Db для работы с базой данных
 */
class Db
{

  /**
   * Устанавливает соединение с базой данных
   * @return \PDO Объект класса PDO для работы с БД
   */
  public static function getConnection()
  {
    // Устанавливаем соединение
    $dsn = "mysql:host=" . HOST . ";dbname=" . DATABASE . ";port=" . PORT;
    $db = new PDO($dsn, USER, PASSWORD, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Задаем кодировку
    $db->exec("set names utf8");

    return $db;
  }

  /**
   * Проверяет, существует ли таблица
   */
  public static function checkExistTable($nameTable)
  {

    $db = self::getConnection();

    // Текст запроса к БД
    $sql = "SHOW TABLES FROM `" . DATABASE. "` like '" . $nameTable . "'";

    // Используется подготовленный запрос
    $result = $db->prepare($sql);

    // Указываем, что хотим получить данные в виде массива
    $result->setFetchMode(PDO::FETCH_ASSOC);

    // Выполнение коменды
    $result->execute();

    // Проверка пустой массив или нет
    if(!empty($result->fetch()))
    {
      return true;
    }
    else
    {
      return false;
    }
  }

  /**
   * Получает все поля запрошенной таблицы
   */
  public static function getAllFieldOfTable($nameTable)
  {

    $db = self::getConnection();

    // Текст запроса к БД
    $sql = "SHOW COLUMNS FROM `" . $nameTable . "`";

    // Используется подготовленный запрос
    $result = $db->prepare($sql);

    // Указываем, что хотим получить данные в виде массива
    $result->setFetchMode(PDO::FETCH_ASSOC);

    // Выполнение коменды
    $result->execute();

    // Получаем массив
    $fields = $result->fetchAll();

    return $fields;
  }

  /**
   * Проверяет, существует ли строка в запрошенной таблице с указанном id
   */
  public static function getExistItemById($nameTable,$idItem)
  {

    $db = self::getConnection();

    // Текст запроса к БД
    $sql = "SELECT * FROM " . $nameTable . " WHERE id = :id";

    // Используется подготовленный запрос
    $result = $db->prepare($sql);
    $result->bindParam(':id', $idItem, PDO::PARAM_INT);

    // Указываем, что хотим получить данные в виде массива
    $result->setFetchMode(PDO::FETCH_ASSOC);

    // Выполнение коменды
    $result->execute();

    // Получаем массив
    $fields = $result->fetchAll();

    // Проверка пустой массив или нет
    if(!empty($fields))
    {
      return true;
    }
    else
    {
      return false;
    }
  }
}


?>

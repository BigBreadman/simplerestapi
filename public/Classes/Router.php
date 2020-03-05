<?php

/**
 * Класс Router для работы с маршрутами
 */
Class Router
{

  /**
   * Возвращает строку запроса
   */
  private function getURI()
  {
    if (!empty($_SERVER['REQUEST_URI']))
    {
      return trim($_SERVER['REQUEST_URI'], '/');
    }
  }

  /**
   * Запускает проверку существования запрошенной таблицы
   * Позволяет использовать отдельные классы для получения более сложных объектов
   */
  public function run()
  {
    // Получаем строку запроса
    $uri = $this->getURI();

    // Палучаем название таблицы
    $arrPath = explode("/", trim($uri,'/'));
    $tableName = array_shift($arrPath);

    // Получает ответ от функции проверки существования таблицы в базе данных
    $existTableBoolean = Db::checkExistTable($tableName);

    // Проверяем true/false - запускаем api или вывбрасываем исключение
    if($existTableBoolean)
    {
      // Вызывается общий класс для вывода данных из запрошенной таблицы.
      // При необходимости получения более сложных элементов, не подходящих под универсальную логику - вызывается свой отдельный класс
      $Api = new ApiAction();
      $Api->run($tableName);
    }
    else
    {
      throw new RuntimeException('API Not Found', 404);
    }
  }
}

?>

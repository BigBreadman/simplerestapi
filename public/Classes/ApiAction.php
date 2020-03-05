<?php

 /**
  * Класс ApiAction для работы с данными, наследуюет абстрактный класс API
  */
 Class ApiAction extends Api
 {

   /**
    * Метод GET для вывода всех записей
    * /users
    */
   public function viewAll($tableName)
   {
      // Соединение с БД
      $db = Db::getConnection();

      // Текст запроса к БД
      $sql = 'SELECT * FROM ' . $tableName;

      // Используется подготовленный запрос
      $result = $db->prepare($sql);

      // Указываем, что хотим получить данные в виде массива
      $result->setFetchMode(PDO::FETCH_ASSOC);

      // Выполнение коменды
      $result->execute();

      // Получение и возврат результатов
      $fetched = $result->fetchAll();

      // Проверка, есть ли в массиве элементы
      if($fetched){
        return $this->response($fetched, 200);
      }

      // Выводим сообщение об ошибке в случае отсутствия данных
      return $this->response('Data not found', 404);
   }

   /**
    * Метод GET для вывода конкретного пользователя по ID
    * /users/2
    */
   public function viewItemById($tableName)
   {

      // Получение ID элемента
      $idItem = array_shift($this->requestUri);

      // Соединение с БД
      $db = Db::getConnection();

      // Текст запроса к БД
      $sql = 'SELECT * FROM ' . $tableName . ' WHERE id = :id';

      // Используется подготовленный запрос
      $result = $db->prepare($sql);
      $result->bindParam(':id', $idItem, PDO::PARAM_INT);

      // Указываем, что хотим получить данные в виде массива
      $result->setFetchMode(PDO::FETCH_ASSOC);

      // Выполнение коменды
      $result->execute();

      // Получение и возврат результатов
      $fetched = $result->fetch();

      // Проверка, есть ли в массиве элементы
      if($fetched){
       return $this->response($fetched, 200);
      }

      // Выводим сообщение об ошибке в случае отсутствия данных
      return $this->response('Data not found', 404);
   }

   /**
    * Метод POST для создания новой записи. Возвращает ID новой строки
    * /users + параметры name, age
    */
   public function createNewItem($tableName)
   {
      // Массивы, которые в дальнейшем будем использовать для подготовки SQL запроса
      $arrFieldsAndValues = [];
      $arrValuesForSql = [];

      // Получаем все поля, что имеет таблица, ищем и удаляем их нее поле 'id' его не нужно заполнять пользователю
      $fieldsOfTableAllColumns = Db::getAllFieldOfTable($tableName);
      $fieldsOfTable = array_column($fieldsOfTableAllColumns, "Field");
      unset($fieldsOfTable[array_search("id",$fieldsOfTable)]);

      // Перебираем поля таблицы чтобы убедиться, что пользователь отправил данные для всех столбцов таблицы
      foreach($fieldsOfTable as $field)
      {
        // Проверяем, есть ли поле таблице в массиве параметров запроса, если отутствует - прерываем цикл в выводим сообщение об ошибке
        if(!array_key_exists($field, $this->requestParams))
        {
          return $this->response("Saving error, you must fill in all the fields (" . implode(",",$fieldsOfTable) . ")", 400);
          break;
        }
        else
        {
          // Поле имеется - проверяем не пустое ли значенине после добавляем поле в массивы ключей и значений для подгтовленного запроса PDO
          if(empty($this->requestParams[$field]))
          {
            return $this->response("Saving error, empty value ".$field, 400);
            break;
          }
          $arrFieldsAndValues[$field] = ":" . $field;
          $arrValuesForSql[":" . $field] = $this->requestParams[$field];
        }
      }

      // Подготавливаем строку ключей для запроса sql
      $arrFields = array_keys($arrFieldsAndValues);
      $stringFields = implode(",", $arrFields);

      // Подготавливаем строку значений для запроса sql
      $arrValues = array_values($arrFieldsAndValues);
      $stringValues = implode(",", $arrValues);

      // Соединение с БД
      $db = Db::getConnection();

      // Текст запроса к БД
      $sql = "INSERT INTO " . $tableName . " (" . $stringFields . ") VALUES (" . $stringValues . ")";

      // Используется подготовленный запрос
      $result = $db->prepare($sql);

      // Проверка - выполнился ли запрос
      if($result->execute($arrValuesForSql)){
        // Вывод id созданной строки
        $idNewItem = $db->lastInsertId();
        return $this->response(["id" => $idNewItem], 200);
      }

      // Вывод сообщения об ошибке сохранения, если она произошла
      return $this->response("Saving error", 500);
   }

   /**
    * Метод PUT для изменения записи
    * /users/2 + параметры name, age
    */
   public function updateItem($tableName)
   {
     // Массивы, которые в дальнейшем будем использовать для подготовки SQL запроса
     $arrFieldsForSql = [];
     $arrValuesForSql = [];

     // Получаем id запрошенного элемента
     $parse_url = parse_url($this->requestUri[0]);
     $idItem = $parse_url['path'] ?? null;

     // Запрос к функции проверки существования элемента в таблице
     $resultCheckExistItem = DB::getExistItemById($tableName, $idItem);

     // Проверка - существует ли элемент с таким id в запрошенной таблице
     if(!$resultCheckExistItem)
     {
       // Если не существует - выводим сообщение об ошибке
       return $this->response("Item with id=$idItem not found", 404);
     }

     // Получаем все поля таблицы и удаляем из нее поле 'id'
     $fieldsOfTableAllColumns = Db::getAllFieldOfTable($tableName);
     $fieldsOfTable = array_column($fieldsOfTableAllColumns, "Field");
     unset($fieldsOfTable[array_search("id",$fieldsOfTable)]);

     // Перебираем поля таблицы чтобы убедиться, что пользователь отправил данные для всех столбцов таблицы
     foreach($this->requestParams as $keyParam => $valueParam)
     {
       // Проверка есть ли параметр из запроса в массиве полей таблицы, если такого поля в таблице нет - выводим ошибку
       if(!array_search($keyParam, $fieldsOfTable))
       {
         return $this->response("Update error, " . $keyParam . " no exist in table", 400);
         break;
       }
       else
       {
         // Поле имеется - проверяем не пустое ли значенине после добавляем поле в массивы ключей и значений для подгтовленного запроса PDO
         if(empty($valueParam))
         {
           return $this->response("Saving error, empty value ".$keyParam, 400);
           break;
         }
         $arrFieldsForSql[] = $keyParam . " = :" . $keyParam;
         $arrValuesForSql[":" . $keyParam] = $valueParam;
       }

     }

     // Проверка - не пустой ли массив
     if(empty($arrFieldsForSql))
     {
       return $this->response("Update error, no parameters", 400);
     }

     // Добавляем поле id в массив для подготовленного запроса (для выборки WHERE)
     $arrValuesForSql[':id'] = $idItem;

     // Получаем строку запроса из массива
     $stringFieldsForSql = implode(",",$arrFieldsForSql);

     // Соединение с БД
     $db = Db::getConnection();

     // Текст запроса к БД
     $sql = "UPDATE " . $tableName . " SET " . $stringFieldsForSql . " WHERE id = :id";

     // Используется подготовленный запрос
     $result = $db->prepare($sql);

     // Выполнение коменды
     if($result->execute($arrValuesForSql))
     {
       // Вывод сообщения о успешной операции
       return $this->response('Data updated', 200);
     }
     else
     {
       // Вывод сообщения об ошибке сохранения, если она произошла
       return $this->response("Update error", 400);
     }
   }

   /**
    * Метод DELETE для удаления записи по ee id
    * /users/2
    */
   public function deleteItem($tableName)
   {

     // Полкчение id элемента
     $idItem = array_shift($this->requestUri);

     // Запрос к функции проверки существования элемента в таблице
     $resultCheckExistItem = DB::getExistItemById($tableName, $idItem);

     if(!$resultCheckExistItem)
     {
       // Если не существует - выводим сообщение об ошибке
       return $this->response("Item with id=$idItem not found", 404);
     }

     // Соединение с БД
     $db = Db::getConnection();

     // Текст запроса к БД
     $sql = "DELETE FROM " . $tableName . " WHERE id = :id";

     // Используется подготовленный запрос
     $result = $db->prepare($sql);
     $result->bindParam(':id', $idItem, PDO::PARAM_INT);

     // Выполнение коменды
     if($result->execute())
     {
       // Вывод сообщения о успешной операции
       return $this->response('Data deleted.', 200);
     }
     else
     {
       // Вывод сообщения об ошибке сохранения, если она произошла
       return $this->response("Delete error", 500);
     }

   }

 }

?>

<?php

  /**
   * Абстрактный класс Api для определения запроса и действия.
   */
  abstract class Api
  {
    public $apiName = ''; // Название API

    protected $method = ''; //GET|POST|PUT|DELETE

    public $requestUri = []; // URI
    public $requestParams = []; // Параметры запроса

    protected $action = ''; // Название метода для выполнения


    public function __construct() {
      // Выставляем заголовки
      header("Access-Control-Allow-Orgin: *");
      header("Access-Control-Allow-Methods: *");
      header("Content-Type: application/json");

      //Массив GET параметров разделенных слешем
      $this->requestUri = explode('/', trim($_SERVER['REQUEST_URI'],'/'));
      $this->requestParams = $_REQUEST;

      //Определение метода запроса
      $this->method = $_SERVER['REQUEST_METHOD'];
      if ($this->method == 'POST' && array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER))
      {
        if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'DELETE')
        {
          $this->method = 'DELETE';
        }
        else if ($_SERVER['HTTP_X_HTTP_METHOD'] == 'PUT')
        {
          $this->method = 'PUT';
        }
        else
        {
          // Если метод запроса неизвестен - выбрасываем исключение
          throw new Exception("Unexpected Header");
        }
      }
    }

    /**
     *  Запуск работы API
     */
    public function run($tableName)
    {
      
      //Первый элемент должен быть названием таблицы
      $this->apiName = array_shift($this->requestUri);

      //Определение действия для обработки
      $this->action = $this->getAction();

      //Если метод(действие) определен в дочернем классе API (ApiAction), то запускаем его
      if (method_exists($this, $this->action))
      {
        echo $this->{$this->action}($tableName);
      }
      else
      {
        // Если метод(действие) неизвестно - выбрасываем исключение
        throw new RuntimeException('Invalid Method', 405);
      }
    }

    /**
     * Ответ на запрос
     */
    protected function response($data, $status = 500)
    {
      header("HTTP/1.1 " . $status . " " . $this->requestStatus($status));
      return json_encode($data);
    }

    /**
     * Определение ошибки по коду
     */
    private function requestStatus($code)
    {
      $status = array(
        200 => 'OK',
        400 => 'Bad Request',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        500 => 'Internal Server Error',
      );
      return ($status[$code]) ? $status[$code] : $status[500];
    }

    /**
     * Определение необходимого действия
     */
    protected function getAction()
    {
      $method = $this->method;
      switch ($method)
      {
        case 'GET':
          if($this->requestUri) // Проверка на наличие id элемента
          {
            return 'viewItemById'; // Вывод элемента по id
          }
          else
          {
            return 'viewAll'; // Вывод всех элементов
          }
          break;
        case 'POST':
          return 'createNewItem'; // Создание нового элемента
          break;
        case 'PUT':
          return 'updateItem'; // Обновление данных элемента
          break;
        case 'DELETE':
          return 'deleteItem'; // Удаление элемента
          break;
        default:
          return null;
      }
    }

    /**
     * Методы, наследуемые дочерним классом
     */
    abstract protected function viewAll($tableName);
    abstract protected function viewItemById($tableName);
    abstract protected function createNewItem($tableName);
    abstract protected function updateItem($tableName);
    abstract protected function deleteItem($tableName);
  }

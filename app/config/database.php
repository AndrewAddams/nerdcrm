<?php
/**
 * Конфигурация подключения к базе данных
 * 
 * Этот файл находится вне публичной директории (выше public/)
 * для безопасности — данные доступа к БД не видны из браузера
 */

// Параметры подключения — ЗАМЕНИ НА СВОИ!
define('DB_HOST', 'localhost:3308');           // Хост базы данных (обычно localhost)
define('DB_NAME', 'andrewadda');              // Имя базы данных
define('DB_USER', 'andrewadda');                // Имя пользователя MySQL
define('DB_PASS', '13*FLlfVC*13');                    // Пароль пользователя MySQL
define('DB_CHARSET', 'utf8mb4');          // Кодировка

/**
 * Класс Database — Singleton для PDO подключения
 * Используется во всех моделях для работы с базой данных
 */
class Database
{
    private static $instance = null;
    private $pdo;
    
    /**
     * Приватный конструктор — создаёт подключение к БД
     */
    private function __construct()
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // В продакшене логировать ошибку, не показывать пользователю
            die('Ошибка подключения к базе данных: ' . $e->getMessage());
        }
    }
    
    /**
     * Получить экземпляр класса (Singleton)
     * 
     * @return Database
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Получить PDO объект для выполнения запросов
     * 
     * @return PDO
     */
    public function getConnection()
    {
        return $this->pdo;
    }
    
    /**
     * Выполнить запрос с подготовкой
     * 
     * @param string $sql SQL-запрос с плейсхолдерами
     * @param array $params Параметры для подстановки
     * @return PDOStatement
     */
    public function query($sql, $params = [])
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Получить ID последней вставленной записи
     * 
     * @return int
     */
    public function lastInsertId()
    {
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Начать транзакцию
     */
    public function beginTransaction()
    {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Зафиксировать транзакцию
     */
    public function commit()
    {
        return $this->pdo->commit();
    }
    
    /**
     * Откатить транзакцию
     */
    public function rollback()
    {
        return $this->pdo->rollBack();
    }
    
    /**
     * Запрещаем клонирование (Singleton)
     */
    private function __clone() {}
    
    /**
     * Запрещаем десериализацию (Singleton)
     */
    public function __wakeup() {}
}
<?php
/**
 * Маршрутизатор приложения
 */

class Router
{
    private $routes = [];
    private $uri;
    private $method;
    private $params = [];
    
    public function __construct($uri, $method)
    {
        $this->uri = parse_url($uri, PHP_URL_PATH);
        $this->method = strtoupper($method);
    }
    
    public function get($path, $controller)
    {
        $this->addRoute('GET', $path, $controller);
    }
    
    public function post($path, $controller)
    {
        $this->addRoute('POST', $path, $controller);
    }
    
    public function put($path, $controller)
    {
        $this->addRoute('PUT', $path, $controller);
    }
    
    public function delete($path, $controller)
    {
        $this->addRoute('DELETE', $path, $controller);
    }
    
    private function addRoute($method, $path, $controller)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller
        ];
    }
    
    public function run()
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $this->method) {
                continue;
            }
            
            if ($this->matchPath($route['path'])) {
                list($controllerName, $methodName) = explode('@', $route['controller']);
                
                $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';
                if (!file_exists($controllerFile)) {
                    $this->notFound("Контроллер $controllerName не найден");
                }
                
                require_once $controllerFile;
                
                if (!class_exists($controllerName)) {
                    $this->notFound("Класс $controllerName не найден");
                }
                
                $controller = new $controllerName();
                
                if (!method_exists($controller, $methodName)) {
                    $this->notFound("Метод $methodName не найден");
                }
                
                return call_user_func_array([$controller, $methodName], [$this->params]);
            }
        }
        
        $this->notFound('Маршрут не найден');
    }
    
    private function matchPath($pattern)
    {
        $regex = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        
        if (preg_match($regex, $this->uri, $matches)) {
            array_shift($matches);
            
            preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $pattern, $paramNames);
            
            $this->params = [];
            foreach ($paramNames[1] as $index => $name) {
                if (isset($matches[$index])) {
                    $this->params[$name] = $matches[$index];
                }
            }
            
            return true;
        }
        
        return false;
    }
    
    private function notFound($message = 'Страница не найдена')
    {
        http_response_code(404);
        
        // Проверяем, авторизован ли пользователь
        $isAuthenticated = isset($_SESSION['user_id']);
        
        // Если это страница (не API) и пользователь не авторизован - редирект на логин
        if (strpos($this->uri, '/api/') !== 0 && !$isAuthenticated && $this->uri !== '/login' && $this->uri !== '/') {
            header('Location: /login');
            exit;
        }
        
        // Для API или уже авторизованных пользователей показываем 404
        if (strpos($this->uri, '/api/') === 0) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $message]);
        } else {
            // Пробуем загрузить шаблон 404
            $viewPath = __DIR__ . '/../views/404.php';
            if (file_exists($viewPath)) {
                require_once $viewPath;
            } else {
                echo '<h1>404 - Страница не найдена</h1>';
                echo '<p>' . htmlspecialchars($message) . '</p>';
            }
        }
        exit;
    }
}
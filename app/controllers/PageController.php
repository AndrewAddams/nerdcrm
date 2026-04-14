<?php
/**
 * Контроллер страниц
 * 
 * Отвечает за отображение HTML-страниц приложения
 */

require_once __DIR__ . '/../core/Controller.php';

class PageController extends Controller
{
    /**
     * Главная страница
     */
    public function index()
    {
        if ($this->currentUser) {
            $this->redirect('/dashboard');
        } else {
            $this->redirect('/login');
        }
    }
    
    /**
     * Страница входа
     */
    public function login()
    {
        // Если уже авторизован — на дашборд
        if ($this->currentUser) {
            $this->redirect('/dashboard');
        }
        
        $this->view('login');
    }
    
    /**
     * Главная страница CRM (дашборд с заказами)
     */
    public function dashboard()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $this->view('dashboard', [
            'user' => $this->currentUser
        ]);
    }
    
    /**
     * Страница "Список для производства"
     */
    public function production()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $this->view('production', [
            'user' => $this->currentUser
        ]);
    }
    
    /**
     * Админ-панель
     */
    public function admin()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $this->view('admin', [
            'user' => $this->currentUser
        ]);
    }
    
    /**
     * Страница профиля пользователя
     */
    public function profile()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $this->view('profile', [
            'user' => $this->currentUser
        ]);
    }
    
    /**
     * Страница настроек пользователя
     */
    public function settings()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $this->view('settings', [
            'user' => $this->currentUser
        ]);
    }
    
    /**
     * Страница отчётов
     */
    public function reports()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $this->view('reports', [
            'user' => $this->currentUser
        ]);
    }
    
    /**
    * Страница справочников
    */
    public function references()
    {
        if (!$this->requireAuth()) {
        return;
        }
    
        $this->view('references', [
        'user' => $this->currentUser
        ]);
    }
    
    /**
     * Страница справки / документации
     */
    public function help()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $this->view('help', [
            'user' => $this->currentUser
        ]);
    }
    
    /**
     * Страница с ошибкой 404
     */
    public function notFound()
    {
        http_response_code(404);
        $this->view('404');
    }
    
    /**
     * Страница с ошибкой 403 (доступ запрещён)
     */
    public function forbidden()
    {
        http_response_code(403);
        $this->view('403');
    }
    
    /**
     * Страница с ошибкой 500 (внутренняя ошибка сервера)
     */
    public function serverError()
    {
        http_response_code(500);
        $this->view('500');
    }
    
    /**
     * Страница Tilda
     */
    public function tilda()
    {
    if (!$this->requireAuth()) {
        return;
    }
    
    $this->view('tilda', [
        'user' => $this->currentUser
    ]);
    }
}
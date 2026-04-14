<?php
/**
 * Контроллер товаров
 * 
 * Отвечает за все операции с товарами: получение списка, создание,
 * редактирование, удаление, импорт, экспорт в CSV
 */

require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/OrderItem.php';

class ProductController extends Controller
{
    private $productModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }
    
    /**
     * Получить список товаров
     * GET /api/products
     */
    public function index()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $search = $_GET['search'] ?? '';
        
        if ($search) {
            $products = $this->productModel->search($search);
        } else {
            $products = $this->productModel->getAll();
        }
        
        $this->success($products);
    }
    
    /**
     * Получить товары для выпадающего списка (автодополнение)
     * GET /api/products/select
     */
    public function select()
    {
        if (!$this->requireAuth()) {
            return;
        }
        
        $search = $_GET['q'] ?? $_GET['search'] ?? '';
        $products = $this->productModel->getForSelect($search);
        
        $this->success($products);
    }
    
    /**
     * Получить товар по ID
     * GET /api/products/{id}
     */
    public function show($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $product = $this->productModel->getById($id);
        
        if (!$product) {
            $this->error('Товар не найден', 404);
            return;
        }
        
        $this->success($product);
    }
    
    /**
     * Создать товар
     * POST /api/products
     */
    public function store()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Наименование товара обязательно');
            return;
        }
        
        // Проверяем уникальность названия
        if ($this->productModel->nameExists($data['name'])) {
            $this->error('Товар с таким наименованием уже существует');
            return;
        }
        
        $id = $this->productModel->save($data);
        
        if (!$id) {
            $this->error('Ошибка при создании товара', 500);
            return;
        }
        
        $product = $this->productModel->getById($id);
        $this->success($product, 'Товар успешно создан');
    }
    
    /**
     * Обновить товар
     * PUT /api/products/{id}
     */
    public function update($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        $data = $this->getRequestData();
        
        if (empty($data['name'])) {
            $this->error('Наименование товара обязательно');
            return;
        }
        
        // Проверяем существование товара
        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->error('Товар не найден', 404);
            return;
        }
        
        // Проверяем уникальность названия (исключая текущий)
        if ($this->productModel->nameExists($data['name'], $id)) {
            $this->error('Товар с таким наименованием уже существует');
            return;
        }
        
        $result = $this->productModel->save($data, $id);
        
        if (!$result) {
            $this->error('Ошибка при обновлении товара', 500);
            return;
        }
        
        $updated = $this->productModel->getById($id);
        $this->success($updated, 'Товар успешно обновлён');
    }
    
    /**
     * Удалить товар (soft delete)
     * DELETE /api/products/{id}
     */
    public function delete($params)
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $id = (int)$params['id'];
        
        // Проверяем, используется ли товар в заказах
        $orderItemModel = new OrderItem();
        $sql = "SELECT COUNT(*) as count FROM order_items WHERE product_id = :product_id";
        $stmt = $this->productModel->query($sql, ['product_id' => $id]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $this->error('Невозможно удалить товар: он используется в ' . $result['count'] . ' заказах');
            return;
        }
        
        $result = $this->productModel->delete($id);
        
        if (!$result) {
            $this->error('Ошибка при удалении товара', 500);
            return;
        }
        
        $this->success(null, 'Товар успешно удалён');
    }
    
    /**
     * Импорт товаров из CSV
     * POST /api/products/import
     */
    public function import()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        // Проверяем, что файл загружен
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            $this->error('Файл не загружен');
            return;
        }
        
        $file = $_FILES['file'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($extension !== 'csv') {
            $this->error('Поддерживаются только файлы .csv');
            return;
        }
        
        // Временно сохраняем файл
        $tempFile = ROOT_PATH . '/uploads/temp/' . uniqid() . '.csv';
        if (!move_uploaded_file($file['tmp_name'], $tempFile)) {
            $this->error('Ошибка сохранения файла');
            return;
        }
        
        try {
            $rows = [];
            $handle = fopen($tempFile, 'r');
            if ($handle) {
                // Определяем разделитель (пробуем ; или ,)
                $firstLine = fgets($handle);
                rewind($handle);
                $delimiter = (strpos($firstLine, ';') !== false) ? ';' : ',';
                
                while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                    // Обрабатываем BOM
                    if (strpos($row[0], "\xEF\xBB\xBF") === 0) {
                        $row[0] = substr($row[0], 3);
                    }
                    $rows[] = $row;
                }
                fclose($handle);
            }
            
            if (empty($rows)) {
                throw new Exception('Файл пуст');
            }
            
            // Определяем заголовки
            $headers = array_shift($rows);
            
            // Нормализуем заголовки
            $headers = array_map(function($h) {
                $h = mb_strtolower(trim($h));
                $h = str_replace([' ', '-', '_'], '', $h);
                return $h;
            }, $headers);
            
            // Ищем индексы колонок
            $idIndex = array_search('id', $headers);
            $nameIndex = array_search('наименование', $headers);
            $shortDescIndex = array_search('краткоеописание', $headers);
            $fullDescIndex = array_search('полноеописание', $headers);
            
            if ($nameIndex === false) {
                throw new Exception('Не найден столбец "Наименование"');
            }
            
            $added = 0;
            $updated = 0;
            $errors = [];
            
            foreach ($rows as $rowIndex => $row) {
                // Проверяем, что строка не пустая
                if (empty(array_filter($row))) {
                    continue;
                }
                
                $name = trim($row[$nameIndex] ?? '');
                
                if (empty($name)) {
                    $errors[] = "Строка " . ($rowIndex + 2) . ": пустое наименование";
                    continue;
                }
                
                $shortDescription = ($shortDescIndex !== false) ? trim($row[$shortDescIndex] ?? '') : '';
                $fullDescription = ($fullDescIndex !== false) ? trim($row[$fullDescIndex] ?? '') : '';
                
                // Проверяем, есть ли ID в файле
                $id = ($idIndex !== false && !empty($row[$idIndex])) ? (int)$row[$idIndex] : null;
                
                try {
                    if ($id) {
                        // Если ID указан, пробуем обновить существующий товар
                        $existing = $this->productModel->getById($id);
                        if ($existing) {
                            $this->productModel->update($id, [
                                'name' => $name,
                                'short_description' => $shortDescription,
                                'full_description' => $fullDescription
                            ]);
                            $updated++;
                        } else {
                            // ID указан, но товар не найден — создаём новый
                            $this->productModel->create([
                                'name' => $name,
                                'short_description' => $shortDescription,
                                'full_description' => $fullDescription
                            ]);
                            $added++;
                        }
                    } else {
                        // ID не указан, ищем по имени
                        $existing = $this->productModel->findBy('name', $name);
                        if ($existing) {
                            $this->productModel->update($existing['id'], [
                                'name' => $name,
                                'short_description' => $shortDescription,
                                'full_description' => $fullDescription
                            ]);
                            $updated++;
                        } else {
                            $this->productModel->create([
                                'name' => $name,
                                'short_description' => $shortDescription,
                                'full_description' => $fullDescription
                            ]);
                            $added++;
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "Строка " . ($rowIndex + 2) . ": " . $e->getMessage();
                }
            }
            
            // Удаляем временный файл
            unlink($tempFile);
            
            $this->success([
                'added' => $added,
                'updated' => $updated,
                'errors' => $errors
            ], "Импорт завершён: добавлено {$added}, обновлено {$updated}" . (count($errors) ? ", ошибок: " . count($errors) : ""));
            
        } catch (Exception $e) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            $this->error('Ошибка импорта: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Экспорт товаров в CSV
     * GET /api/products/export
     */
    public function export()
    {
        if (!$this->requireAdmin()) {
            return;
        }
        
        $products = $this->productModel->getForExport();
        
        // Очищаем буфер вывода
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        // Устанавливаем заголовки для скачивания CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="products_export_' . date('Y-m-d') . '.csv"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        
        // Открываем поток вывода
        $output = fopen('php://output', 'w');
        
        // Добавляем BOM для корректного отображения UTF-8 в Excel
        fwrite($output, "\xEF\xBB\xBF");
        
        // Заголовки
        $headers = ['ID', 'Наименование', 'Краткое описание', 'Полное описание'];
        fputcsv($output, $headers, ';', '"', '\\');
        
        // Данные
        foreach ($products as $product) {
            $row = [
                $product['id'],
                $this->escapeCsvValue($product['name']),
                $this->escapeCsvValue($product['short_description'] ?? ''),
                $this->escapeCsvValue($product['full_description'] ?? '')
            ];
            fputcsv($output, $row, ';', '"', '\\');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Экранирование значения для CSV
     */
    private function escapeCsvValue($value)
    {
        if ($value === null || $value === '') {
            return '';
        }
        
        // Заменяем двойные кавычки на две двойные кавычки
        $value = str_replace('"', '""', $value);
        
        // Если есть переносы строк, запятые, кавычки или точка с запятой — оборачиваем в кавычки
        if (strpos($value, "\n") !== false || strpos($value, "\r") !== false || strpos($value, ';') !== false || strpos($value, '"') !== false) {
            $value = '"' . $value . '"';
        }
        
        return $value;
    }
}
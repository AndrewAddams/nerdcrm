<?php
// save_data.php – работа с MySQL через класс Database

require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

ini_set('memory_limit', '256M');
set_time_limit(300);

try {
    $db = Database::getInstance();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$prefix = 'calc_';

// -------------------------------------------------------------------
// GET – отдаём все данные
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Получаем все форматы
        $formats = [];
        $stmt = $pdo->query("SELECT id, name, coefficient FROM {$prefix}formats");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $formats[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'coefficient' => (float)$row['coefficient'],
                'ingredients' => []
            ];
        }

        // Получаем ингредиенты форматов
        $stmt = $pdo->query("SELECT format_id, name, grams FROM {$prefix}format_ingredients");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $formatId = $row['format_id'];
            if (isset($formats[$formatId])) {
                $grams = $row['grams'];
                if (is_numeric($grams)) $grams = (float)$grams;
                $formats[$formatId]['ingredients'][] = [
                    'name' => $row['name'],
                    'grams' => $grams
                ];
            }
        }

        // Получаем все ароматы
        $aromats = [];
        $stmt = $pdo->query("SELECT id, name FROM {$prefix}aromats");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $aromats[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'ingredients' => []
            ];
        }

        // Получаем ингредиенты ароматов
        $stmt = $pdo->query("SELECT aromat_id, name, grams FROM {$prefix}aromat_ingredients");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $aromatId = $row['aromat_id'];
            if (isset($aromats[$aromatId])) {
                $aromats[$aromatId]['ingredients'][] = [
                    'name' => $row['name'],
                    'grams' => (float)$row['grams']
                ];
            }
        }

        echo json_encode(['formats' => $formats, 'aromats' => $aromats], JSON_UNESCAPED_UNICODE);
        exit;
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch data: ' . $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------------
// POST – сохраняем данные
// -------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if ($data === null || !isset($data['formats']) || !isset($data['aromats'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON: missing formats or aromats']);
        exit;
    }

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("SET sql_mode = ''");

        $db->beginTransaction();

        // Очищаем таблицы
        $pdo->exec("DELETE FROM {$prefix}format_ingredients");
        $pdo->exec("DELETE FROM {$prefix}formats");
        $pdo->exec("DELETE FROM {$prefix}aromat_ingredients");
        $pdo->exec("DELETE FROM {$prefix}aromats");

        // Вставляем форматы
        $stmtFormat = $pdo->prepare("INSERT INTO {$prefix}formats (id, name, coefficient) VALUES (?, ?, ?)");
        $stmtIngFormat = $pdo->prepare("INSERT INTO {$prefix}format_ingredients (format_id, name, grams) VALUES (?, ?, ?)");

        foreach ($data['formats'] as $id => $format) {
            $stmtFormat->execute([$id, $format['name'], $format['coefficient']]);
            foreach ($format['ingredients'] as $ing) {
                $grams = (string)$ing['grams'];
                $stmtIngFormat->execute([$id, $ing['name'], $grams]);
            }
        }

        // Вставляем ароматы
        $stmtAromat = $pdo->prepare("INSERT INTO {$prefix}aromats (id, name) VALUES (?, ?)");
        $stmtIngAromat = $pdo->prepare("INSERT INTO {$prefix}aromat_ingredients (aromat_id, name, grams) VALUES (?, ?, ?)");

        foreach ($data['aromats'] as $id => $aromat) {
            $stmtAromat->execute([$id, $aromat['name']]);
            foreach ($aromat['ingredients'] as $ing) {
                $grams = isset($ing['grams']) ? (float)$ing['grams'] : 0;
                $stmtIngAromat->execute([$id, $ing['name'], $grams]);
            }
        }

        $db->commit();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo json_encode(['success' => true]);
        exit;
    } catch (PDOException $e) {
        $db->rollback();
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        exit;
    }
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
<?php
header('Content-Type: application/json');

$requestMethod = $_SERVER["REQUEST_METHOD"];
switch($requestMethod) {
    case 'GET':
        handleGetRequest();
        break;
    case 'POST':
        handlePostRequest();
        break;
    case 'PUT':
        handlePutRequest();
        break;
    case 'DELETE':
        handleDeleteRequest();
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        break;
}

function handleGetRequest() {
    $json = file_get_contents('../config/config.json'); 

    if ($json === false) {
        http_response_code(500);
        echo json_encode(['error' => 'Error reading the JSON file']);
        return;
    }

    $json_data = json_decode($json, true); 

    if ($json_data === null) {
        http_response_code(500);
        echo json_encode(['error' => 'Error decoding the JSON file']);
        return;
    }

    try {
        $pdo = new PDO(
            "mysql:host={$json_data['host']};dbname={$json_data['dbname']}",
            $json_data['user'],
            $json_data['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Get quadrants
        $stmt = $pdo->query("SELECT id, name FROM quadrants ORDER BY id");
        $quadrants = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get rings
        $stmt = $pdo->query("SELECT id, name, color, radius FROM rings ORDER BY id");
        $rings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get entries
        $stmt = $pdo->query("
            SELECT 
                e.id, e.label, e.quadrant_id, e.ring_id, 
                e.active, e.moved, e.link
            FROM entries e
            ORDER BY e.id
        ");
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Format data for the radar
        $radarData = [
            'svg_id' => 'radar',
            'width' => 1450,
            'height' => 1000,
            'colors' => [
                'background' => '#fff',
                'grid' => '#bbb',
                'inactive' => '#ddd'
            ],
            'quadrants' => array_map(function($q) {
                return ['name' => $q['name']];
            }, $quadrants),
            'rings' => array_map(function($r) {
                return [
                    'name' => $r['name'],
                    'color' => $r['color']
                ];
            }, $rings),
            'entries' => array_map(function($e) {
                return [
                    'label' => $e['label'],
                    'quadrant' => $e['quadrant_id'] - 1, // Convert to 0-based index
                    'ring' => $e['ring_id'] - 1, // Convert to 0-based index
                    'active' => (bool)$e['active'],
                    'moved' => (int)$e['moved'],
                    'link' => $e['link']
                ];
            }, $entries)
        ];

        echo json_encode($radarData);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}

function handlePostRequest() {
    // Implement POST request handling
    http_response_code(501);
    echo json_encode(['error' => 'Not Implemented']);
}

function handlePutRequest() {
    // Implement PUT request handling
    http_response_code(501);
    echo json_encode(['error' => 'Not Implemented']);
}

function handleDeleteRequest() {
    // Implement DELETE request handling
    http_response_code(501);
    echo json_encode(['error' => 'Not Implemented']);
}
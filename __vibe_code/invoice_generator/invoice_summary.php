<?php
header('Content-Type: application/json; charset=UTF-8');
mb_internal_encoding('UTF-8');

define('QUARTER_LIMIT', 10813.50);

function errorResponse($message, $status = 500)
{
    http_response_code($status);
    echo json_encode(['error' => $message]);
    exit;
}

$configPath = realpath(__DIR__ . '/../../config/config.json');
if (!$configPath || !file_exists($configPath)) {
    errorResponse('Brak pliku konfiguracyjnego bazy danych.');
}

$config = json_decode(file_get_contents($configPath), true);
if ($config === null) {
    errorResponse('Nie można odczytać konfiguracji bazy danych.');
}

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']}",
        $config['user'],
        $config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    errorResponse('Błąd połączenia z bazą danych.');
}

$today = new DateTime('now');

// Miesięczne podsumowanie
$monthStart = (clone $today)->modify('first day of this month')->setTime(0, 0, 0);
$monthEnd = (clone $monthStart)->modify('+1 month');

// Ustalenie kwartału (1: sty-mar, 2: kwi-cze, 3: lip-wrz, 4: paź-gru)
$currentMonth = (int)$today->format('n');
$quarterStartMonth = (int)(floor(($currentMonth - 1) / 3) * 3 + 1);
$quarterStart = DateTime::createFromFormat('Y-n-j H:i:s', $today->format('Y') . '-' . $quarterStartMonth . '-1 00:00:00');
$quarterEnd = (clone $quarterStart)->modify('+3 months');

try {
    $monthStmt = $pdo->prepare('SELECT COALESCE(SUM(total), 0) as monthly_total FROM invoices WHERE issue_date >= :start AND issue_date < :end');
    $monthStmt->execute([
        ':start' => $monthStart->format('Y-m-d'),
        ':end' => $monthEnd->format('Y-m-d'),
    ]);
    $monthlyTotal = (float)$monthStmt->fetchColumn();

    $quarterStmt = $pdo->prepare('SELECT COALESCE(SUM(total), 0) as quarter_total FROM invoices WHERE issue_date >= :start AND issue_date < :end');
    $quarterStmt->execute([
        ':start' => $quarterStart->format('Y-m-d'),
        ':end' => $quarterEnd->format('Y-m-d'),
    ]);
    $quarterTotal = (float)$quarterStmt->fetchColumn();
} catch (PDOException $e) {
    errorResponse('Błąd zapytania do bazy danych.');
}

$quarterRemaining = max(QUARTER_LIMIT - $quarterTotal, 0);

$response = [
    'monthly_total' => round($monthlyTotal, 2),
    'quarter_total' => round($quarterTotal, 2),
    'quarter_remaining' => round($quarterRemaining, 2),
    'quarter_limit' => QUARTER_LIMIT,
];

echo json_encode($response);

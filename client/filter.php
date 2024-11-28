<?php
header("Access-Control-Allow-Origin: http://localhost:8080"); // Указываем точный домен
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['action']) && $data['action'] === 'fetch_filters') {
    $sql = "SELECT DISTINCT Country FROM employees";
    $countriesStmt = $pdo->query($sql);
    $countries = $countriesStmt->fetchAll(PDO::FETCH_COLUMN);

    $sql = "SELECT DISTINCT City FROM employees";
    $citiesStmt = $pdo->query($sql);
    $cities = $citiesStmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['countries' => $countries, 'cities' => $cities]);
    exit;
}

$sortColumn = $data['sortColumn'] ?? 'id';
$sortOrder = $data['sortOrder'] ?? 'ASC';
$filterCountry = $data['country'] ?? [];
$filterCity = $data['city'] ?? [];

$whereClauses = [];
if (!empty($filterCountry)) {
    $placeholders = implode(',', array_fill(0, count($filterCountry), '?'));
    $whereClauses[] = "Country IN ($placeholders)";
}
if (!empty($filterCity)) {
    $placeholders = implode(',', array_fill(0, count($filterCity), '?'));
    $whereClauses[] = "City IN ($placeholders)";
}

$whereSQL = $whereClauses ? "WHERE " . implode(' AND ', $whereClauses) : "";

$sql = "SELECT * FROM employees $whereSQL ORDER BY $sortColumn $sortOrder";
$stmt = $pdo->prepare($sql);
$stmt->execute(array_merge($filterCountry, $filterCity));

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

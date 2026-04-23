<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$ensemble_id = $_GET['ensemble_id'] ?? 0;

$sql = "SELECT * FROM songs WHERE ensemble_id = ? ORDER BY song_id DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $ensemble_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode(["status" => "success", "data" => $data]);
$stmt->close();
$conn->close();
?>
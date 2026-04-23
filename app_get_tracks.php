<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$song_id = $_GET['song_id'] ?? 0;

$sql = "SELECT * FROM tracks WHERE song_id = ? ORDER BY sort_order ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $song_id);
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
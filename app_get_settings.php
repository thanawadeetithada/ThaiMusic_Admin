<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$sql = "SELECT * FROM app_settings LIMIT 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo json_encode([
        "status" => "success",
        "data" => $result->fetch_assoc()
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "ไม่พบข้อมูลการตั้งค่า"
    ]);
}

$conn->close();
?>
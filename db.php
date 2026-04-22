<?php
// db.php
$host = "127.0.0.1";
$user = "root"; // ใส่ username ของ MySQL
$pass = "";     // ใส่ password ของ MySQL (ถ้า XAMPP ปกติจะว่าง)
$dbname = "thaimusicdb";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
?>
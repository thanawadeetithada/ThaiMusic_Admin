<?php
// db.php
$host = "thaimusic-admin.com";
$user = "u547809419_admthaimusic"; // ใส่ username ของ MySQL
$pass = "3@Bq1!ej";     // ใส่ password ของ MySQL (ถ้า XAMPP ปกติจะว่าง)
$dbname = "u547809419_thaimusicdb";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
?>
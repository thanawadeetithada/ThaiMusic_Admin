<?php
// db.php
$host = "localhost";
$user = "root";      // ใส่ 'root' (เป็นค่าเริ่มต้นของ XAMPP)
$pass = "";          // เว้นว่างไว้ (ถ้าไม่ได้ไปตั้งรหัสผ่านเพิ่ม)
$dbname = "thaimusicdb";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Connection failed: " . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
?>
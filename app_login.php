<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if(empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "กรุณากรอกอีเมลและรหัสผ่าน"]);
    exit();
}

$sql = "SELECT user_id, first_name, last_name, profile_image, password, userrole FROM users WHERE email = ? AND deleted_at IS NULL";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        unset($user['password']);
        
        echo json_encode([
            "status" => "success", 
            "message" => "เข้าสู่ระบบสำเร็จ",
            "user_data" => $user
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "รหัสผ่านไม่ถูกต้อง"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "ไม่พบอีเมลนี้ในระบบ"]);
}

$stmt->close();
$conn->close();
?>
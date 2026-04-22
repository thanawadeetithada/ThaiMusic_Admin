<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if(empty($fname) || empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "ข้อมูลไม่ครบถ้วน"]);
    exit();
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$profile_image_path = NULL;

if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/profiles/';
    
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
    if(empty($file_extension)) $file_extension = "jpg"; // เผื่อกรณีไม่ได้นามสกุลมา
    
    $new_filename = uniqid('profile_') . '.' . $file_extension;
    $target_file = $upload_dir . $new_filename;

    if (move_uploaded_file($_FILES['img']['tmp_name'], $target_file)) {
        $profile_image_path = $target_file; // เก็บ path นี้เพื่อเอาไปลง DB
    }
}

$sql = "INSERT INTO users (first_name, last_name, email, password, profile_image) VALUES (?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssss", $fname, $lname, $email, $hashed_password, $profile_image_path);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "สมัครสมาชิกสำเร็จ"]);
} else {
    if ($conn->errno == 1062) {
        echo json_encode(["status" => "error", "message" => "อีเมลนี้มีในระบบแล้ว"]);
    } else {
        echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด: " . $conn->error]);
    }
}

$stmt->close();
$conn->close();
?>
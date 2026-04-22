<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");
require 'db.php';

$user_id = $_POST['user_id'] ?? '';
$fname = $_POST['fname'] ?? '';
$lname = $_POST['lname'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if(empty($user_id)) { echo json_encode(["status" => "error", "message" => "ไม่มี User ID"]); exit(); }

$profile_image_path = null;
if (isset($_FILES['img']) && $_FILES['img']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/profiles/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
    
    $file_extension = pathinfo($_FILES['img']['name'], PATHINFO_EXTENSION);
    if(empty($file_extension)) $file_extension = "jpg";
    
    $new_filename = uniqid('profile_') . '.' . $file_extension;
    $target_file = $upload_dir . $new_filename;

    if (move_uploaded_file($_FILES['img']['tmp_name'], $target_file)) {
        $profile_image_path = $target_file;
    }
}

$update_fields = ["first_name=?", "last_name=?", "email=?"];
$params = [$fname, $lname, $email];
$types = "sss";

if (!empty($password)) {
    $update_fields[] = "password=?";
    $params[] = password_hash($password, PASSWORD_DEFAULT);
    $types .= "s";
}

if (!empty($profile_image_path)) {
    $update_fields[] = "profile_image=?";
    $params[] = $profile_image_path;
    $types .= "s";
}

$params[] = $user_id;
$types .= "i";

$sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE user_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "อัปเดตข้อมูลสำเร็จ"]);
} else {
    if ($conn->errno == 1062) {
        echo json_encode(["status" => "error", "message" => "อีเมลนี้มีผู้ใช้แล้ว"]);
    } else {
        echo json_encode(["status" => "error", "message" => "เกิดข้อผิดพลาด"]);
    }
}

$stmt->close();
$conn->close();
?>
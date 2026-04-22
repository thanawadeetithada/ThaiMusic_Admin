<?php
session_start();
require 'db.php'; // นำเข้าไฟล์เชื่อมต่อฐานข้อมูล

if (!isset($_SESSION['user_id']) || $_SESSION['userrole'] === 'user') {
    header("Location: index.php"); // เด้งกลับหน้าล็อกอิน
    exit();
}

$alert_message = '';
$alert_type = '';

function uploadProfileImage($file, $idstudent_or_prefix) {
    $target_dir = "uploads/profiles/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $allowed = ["jpg", "jpeg", "png"];
    
    if (in_array($ext, $allowed) && $file["size"] <= 2097152) { // ไม่เกิน 2MB
        $new_filename = "profile_" . $idstudent_or_prefix . "_" . time() . "." . $ext;
        if (move_uploaded_file($file["tmp_name"], $target_dir . $new_filename)) {
            return $new_filename; // คืนค่ากลับไปแค่ชื่อไฟล์
        }
    }
    return false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    if ($_POST['action'] === 'add') {
        $fname = trim($_POST['first_name']);
        $lname = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT);
        $role = $_POST['userrole'];
        
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND deleted_at IS NULL");
        $check->bind_param("s", $email);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $alert_message = "อีเมลนี้มีอยู่ในระบบแล้ว!";
            $alert_type = "danger";
        } else {
            $profile_image = "uploads/profiles/default.png"; 
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $uploaded_img = uploadProfileImage($_FILES['profile_image'], uniqid());
                if ($uploaded_img) {
                    $profile_image = "uploads/profiles/" . $uploaded_img; // 📌 เอาโฟลเดอร์มาต่อก่อนเซฟลง DB
                }
            }

            $sql = "INSERT INTO users (first_name, last_name, email, password, userrole, profile_image) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssss", $fname, $lname, $email, $password, $role, $profile_image);
            if ($stmt->execute()) {
                $alert_message = "เพิ่มผู้ใช้งานสำเร็จ!";
                $alert_type = "success";
            }
            $stmt->close();
        }
        $check->close();
    }
    
    elseif ($_POST['action'] === 'edit') {
        $edit_id = $_POST['user_id'];
        $fname = trim($_POST['first_name']);
        $lname = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $role = $_POST['userrole'];
        $current_image = $_POST['current_image'];

        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ? AND deleted_at IS NULL");
        $check->bind_param("si", $email, $edit_id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $alert_message = "อีเมลนี้ถูกใช้โดยผู้ใช้อื่นแล้ว!";
            $alert_type = "danger";
        } else {
            $profile_image = $current_image;
            if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                $uploaded_img = uploadProfileImage($_FILES['profile_image'], $edit_id);
                if ($uploaded_img) {
                    $profile_image = "uploads/profiles/" . $uploaded_img; // 📌 เอาโฟลเดอร์มาต่อก่อนอัปเดตลง DB
                }
            }

            $sql = "UPDATE users SET first_name=?, last_name=?, email=?, userrole=?, profile_image=? WHERE user_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssi", $fname, $lname, $email, $role, $profile_image, $edit_id);
            if ($stmt->execute()) {
                $alert_message = "แก้ไขข้อมูลสำเร็จ!";
                $alert_type = "success";
            }
            $stmt->close();
        }
        $check->close();
    }

    elseif ($_POST['action'] === 'delete') {
        $del_id = $_POST['delete_user_id'];
        if ($del_id != $_SESSION['user_id']) {
            $sql = "UPDATE users SET deleted_at = NOW() WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $del_id);
            if ($stmt->execute()) {
                $alert_message = "ลบผู้ใช้งานสำเร็จ!";
                $alert_type = "success";
            }
            $stmt->close();
        } else {
            $alert_message = "คุณไม่สามารถลบบัญชีของตัวเองได้!";
            $alert_type = "danger";
        }
    }
}

$sql_users = "SELECT * FROM users WHERE deleted_at IS NULL ORDER BY user_id DESC";
$result_users = $conn->query($sql_users);
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - Thai Music Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Prompt', sans-serif;
        }
        .btn-gold {
            background-color: #d4af37;
            color: white;
            font-weight: 500;
        }
        .btn-gold:hover {
            background-color: #b5952f;
            color: white;
        }
        .profile-img-table {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ddd;
            background-color: #fff;
        }
        .card-header-custom {
            background-color: #2c5364;
            color: white;
            font-weight: 500;
        }
        .table-responsive {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="content-area">
        
        <?php if($alert_message): ?>
            <div class="alert alert-<?= $alert_type ?> alert-dismissible fade show shadow-sm" role="alert">
                <?= $alert_message ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header card-header-custom d-flex justify-content-between align-items-center py-3">
                <h5 class="mb-0"><i class="fa-solid fa-users me-2"></i> ข้อมูลผู้ใช้งานระบบ</h5>
                <button class="btn btn-gold btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fa-solid fa-user-plus me-1"></i> เพิ่มผู้ใช้งาน
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">รูป</th>
                                <th>ชื่อ - นามสกุล</th>
                                <th>อีเมล</th>
                                <th>สิทธิ์ใช้งาน</th>
                                <th class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($result_users->num_rows > 0): ?>
                                <?php while($row = $result_users->fetch_assoc()): 
                                    $db_image = $row['profile_image'];
                                    
                                    if (empty($db_image) || $db_image == 'default.png') {
                                        $img_src = 'uploads/profiles/default.png';
                                    } else {
                                        if (strpos($db_image, 'uploads/profiles/') === false) {
                                            $img_src = 'uploads/profiles/' . htmlspecialchars($db_image);
                                        } else {
                                            $img_src = htmlspecialchars($db_image);
                                        }
                                    }
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <img src="<?= $img_src ?>" class="profile-img-table" alt="Profile" onerror="this.src='uploads/profiles/default.png'">
                                    </td>
                                    <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                    <td><?= htmlspecialchars($row['email']) ?></td>
                                    <td>
                                        <?php 
                                            if($row['userrole'] == 'superadmin') echo '<span class="badge bg-danger">Superadmin</span>';
                                            elseif($row['userrole'] == 'admin') echo '<span class="badge bg-primary">Admin</span>';
                                            else echo '<span class="badge bg-secondary">User</span>';
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <button class="btn btn-warning btn-sm edit-btn text-dark" 
                                            data-id="<?= $row['user_id'] ?>"
                                            data-fname="<?= htmlspecialchars($row['first_name']) ?>"
                                            data-lname="<?= htmlspecialchars($row['last_name']) ?>"
                                            data-email="<?= htmlspecialchars($row['email']) ?>"
                                            data-role="<?= htmlspecialchars($row['userrole']) ?>"
                                            data-image="<?= htmlspecialchars($db_image) ?>"
                                            data-bs-toggle="modal" data-bs-target="#editUserModal">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        
                                        <?php if($row['user_id'] != $_SESSION['user_id']): ?>
                                        <button class="btn btn-danger btn-sm delete-btn" 
                                            data-id="<?= $row['user_id'] ?>"
                                            data-name="<?= htmlspecialchars($row['first_name'].' '.$row['last_name']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteUserModal">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูลผู้ใช้งาน</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

</div> <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-header">
                        <h5 class="modal-title">เพิ่มผู้ใช้งานใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">นามสกุล</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">อีเมล</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">รหัสผ่านเริ่มต้น</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <div class="col-12">
                                <label class="form-label">สิทธิ์การใช้งาน</label>
                                <select name="userrole" class="form-select" required>
                                    <option value="user">User (ผู้ใช้งานทั่วไป)</option>
                                    <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                                    <?php if($_SESSION['userrole'] === 'superadmin'): ?>
                                    <option value="superadmin">Superadmin</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">รูปโปรไฟล์ (ไม่บังคับ)</label>
                                <input type="file" name="profile_image" class="form-control" accept="image/*">
                                <small class="text-muted">* หากไม่เลือก ระบบจะใช้รูป default.png</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-gold">บันทึกข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_id">
                    <input type="hidden" name="current_image" id="edit_current_image">
                    
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขข้อมูลผู้ใช้งาน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">ชื่อ</label>
                                <input type="text" name="first_name" id="edit_fname" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">นามสกุล</label>
                                <input type="text" name="last_name" id="edit_lname" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">อีเมล</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">สิทธิ์การใช้งาน</label>
                                <select name="userrole" id="edit_role" class="form-select" required>
                                    <option value="user">User (ผู้ใช้งานทั่วไป)</option>
                                    <option value="admin">Admin (ผู้ดูแลระบบ)</option>
                                    <?php if($_SESSION['userrole'] === 'superadmin'): ?>
                                    <option value="superadmin">Superadmin</option>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">เปลี่ยนรูปโปรไฟล์ (อัปโหลดใหม่เพื่อเปลี่ยน)</label>
                                <input type="file" name="profile_image" class="form-control" accept="image/*">
                            </div>
                            <div class="col-12 text-muted small mt-2">
                                <i class="fa-solid fa-circle-info"></i> ไม่อนุญาตให้แก้ไขรหัสผ่านในหน้านี้
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">อัปเดตข้อมูล</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content text-center">
                <form action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="delete_user_id" id="del_id">
                    <div class="modal-body p-4">
                        <i class="fa-solid fa-triangle-exclamation text-danger mb-3" style="font-size: 50px;"></i>
                        <h5 class="mb-3">ยืนยันการลบผู้ใช้?</h5>
                        <p class="text-muted small mb-4">คุณต้องการลบ <strong id="del_name"></strong> ออกจากระบบใช่หรือไม่?</p>
                        <button type="button" class="btn btn-secondary btn-sm px-3" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-danger btn-sm px-3">ยืนยันการลบ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.getAttribute('data-id');
                document.getElementById('edit_fname').value = this.getAttribute('data-fname');
                document.getElementById('edit_lname').value = this.getAttribute('data-lname');
                document.getElementById('edit_email').value = this.getAttribute('data-email');
                document.getElementById('edit_role').value = this.getAttribute('data-role');
                document.getElementById('edit_current_image').value = this.getAttribute('data-image');
            });
        });

        document.querySelectorAll('.delete-btn').forEach(button => {
            button.addEventListener('click', function() {
                document.getElementById('del_id').value = this.getAttribute('data-id');
                document.getElementById('del_name').innerText = this.getAttribute('data-name');
            });
        });
    </script>
</body>
</html>
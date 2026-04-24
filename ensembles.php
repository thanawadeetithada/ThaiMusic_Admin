<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['userrole'] === 'user') {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_ensemble'])) {
    $name = trim($_POST['name']);
    $cover_image = '';

    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $new_name = "ensemble_" . time() . "." . $ext;
        if (!is_dir('uploads/images')) mkdir('uploads/images', 0777, true);
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], "uploads/images/" . $new_name)) {
            $cover_image = $new_name;
        }
    }

    $stmt = $conn->prepare("INSERT INTO ensembles (name, cover_image, is_active) VALUES (?, ?, 1)");
    $stmt->bind_param("ss", $name, $cover_image);
    $stmt->execute();
    header("Location: ensembles.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_ensemble'])) {
    $id = intval($_POST['ensemble_id']);
    $name = trim($_POST['name']);
    
    $stmt = $conn->prepare("SELECT cover_image FROM ensembles WHERE ensemble_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    $cover_image = $current['cover_image'];
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $new_name = "ensemble_" . time() . "." . $ext;
        if (move_uploaded_file($_FILES['cover_image']['tmp_name'], "uploads/images/" . $new_name)) {
            // ลบรูปเก่า
            if (!empty($current['cover_image']) && file_exists("uploads/images/" . $current['cover_image'])) {
                unlink("uploads/images/" . $current['cover_image']);
            }
            $cover_image = $new_name;
        }
    }

    $stmt = $conn->prepare("UPDATE ensembles SET name = ?, cover_image = ? WHERE ensemble_id = ?");
    $stmt->bind_param("ssi", $name, $cover_image, $id);
    $stmt->execute();
    header("Location: ensembles.php");
    exit();
}

if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $current_status = intval($_GET['toggle_status']);
    $new_status = ($current_status == 1) ? 0 : 1;

    $stmt = $conn->prepare("UPDATE ensembles SET is_active = ? WHERE ensemble_id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $stmt->execute();
    header("Location: ensembles.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_ensemble'])) {
    $del_id = intval($_POST['delete_ensemble_id']);
    $ens_stmt = $conn->prepare("SELECT cover_image FROM ensembles WHERE ensemble_id = ?");
    $ens_stmt->bind_param("i", $del_id);
    $ens_stmt->execute();
    $ens_res = $ens_stmt->get_result()->fetch_assoc();
    if ($ens_res && !empty($ens_res['cover_image']) && file_exists("uploads/images/" . $ens_res['cover_image'])) {
        unlink("uploads/images/" . $ens_res['cover_image']);
    }
    $track_stmt = $conn->prepare("SELECT t.audio_file, t.instrument_icon FROM tracks t INNER JOIN songs s ON t.song_id = s.song_id WHERE s.ensemble_id = ?");
    $track_stmt->bind_param("i", $del_id);
    $track_stmt->execute();
    $track_res = $track_stmt->get_result();
    while ($track = $track_res->fetch_assoc()) {
        if (!empty($track['audio_file']) && file_exists("uploads/audio/" . $track['audio_file'])) 
            unlink("uploads/audio/" . $track['audio_file']);
        if (!empty($track['instrument_icon']) && file_exists("uploads/images/" . $track['instrument_icon'])) unlink("uploads/images/" . $track['instrument_icon']);
    }
    $del_stmt = $conn->prepare("DELETE FROM ensembles WHERE ensemble_id = ?");
    $del_stmt->bind_param("i", $del_id);
    $del_stmt->execute();
    header("Location: ensembles.php");
    exit();
}

$ensembles = $conn->query("SELECT * FROM ensembles ORDER BY ensemble_id DESC");
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการวงดนตรี - Thai Music Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; }
        .btn-gold { background-color: #d4af37; color: white; }
        .btn-gold:hover { background-color: #b5952f; color: white; }
        .card-header-custom { background-color: #2c5364; color: white; }
        .form-switch .form-check-input { width: 2.5em; height: 1.25em; cursor: pointer; }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="content-area container py-4">
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header card-header-custom py-3">
                <h5 class="mb-0"><i class="fa-solid fa-plus-circle me-2"></i> เพิ่มวงดนตรีใหม่</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                    <input type="hidden" name="add_ensemble" value="1">
                    <div class="col-md-5">
                        <label class="form-label">ชื่อวงดนตรี</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">รูปภาพหน้าปก</label>
                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-gold w-100">เพิ่มวง</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2"></i> รายการวงดนตรี</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="80" class="text-center">ปก</th>
                                <th>ชื่อวงดนตรี</th>
                                <th width="120" class="text-center">สถานะ</th>
                                <th width="300" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $ensembles->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center">
                                    <?php if($row['cover_image']): ?>
                                        <img src="uploads/images/<?= htmlspecialchars($row['cover_image']) ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background:#eee; border-radius: 8px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-solid fa-image text-muted"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars($row['name']) ?></span>
                                </td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input shadow-none" type="checkbox" 
                                               role="switch" <?= $row['is_active'] ? 'checked' : '' ?>
                                               onclick="window.location.href='?toggle_status=<?= $row['is_active'] ?>&id=<?= $row['ensemble_id'] ?>'">
                                    </div>
                                    <div class="small <?= $row['is_active'] ? 'text-success' : 'text-danger' ?>">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="songs.php?ensemble_id=<?= $row['ensemble_id'] ?>" class="btn btn-sm btn-outline-primary px-3">
                                            เพลง <i class="fa-solid fa-music"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-warning edit-btn" 
                                                data-id="<?= $row['ensemble_id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-img="<?= htmlspecialchars($row['cover_image']) ?>"
                                                data-bs-toggle="modal" data-bs-target="#editModal">
                                            <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-btn"
                                                data-id="<?= $row['ensemble_id'] ?>"
                                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                                data-bs-toggle="modal" data-bs-target="#deleteEnsembleModal">
                                            <i class="fa-solid fa-trash"></i> ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">แก้ไขวงดนตรี</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="edit_ensemble" value="1">
                        <input type="hidden" name="ensemble_id" id="edit_id">
                        <div class="mb-3">
                            <label class="form-label">ชื่อวงดนตรี</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">เปลี่ยนรูปปก (เว้นว่างถ้าไม่เปลี่ยน)</label>
                            <input type="file" name="cover_image" class="form-control" accept="image/*">
                            <div id="edit_img_preview" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                        <button type="submit" class="btn btn-primary">บันทึกการแก้ไข</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteEnsembleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="delete_ensemble" value="1">
                    <input type="hidden" name="delete_ensemble_id" id="del_id">
                    <div class="modal-body text-center p-4">
                        <i class="fa-solid fa-triangle-exclamation text-danger mb-3" style="font-size: 50px;"></i>
                        <h4 class="fw-bold">ยืนยันการลบ?</h4>
                        <p class="text-muted">วง <span id="del_name" class="fw-bold text-dark"></span> และเพลงทั้งหมดในวงจะถูกลบถาวร</p>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-secondary w-50" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-danger w-50">ลบออก</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // จัดการข้อมูลเข้า Modal แก้ไข
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                const img = this.dataset.img;
                document.getElementById('edit_img_preview').innerHTML = img 
                    ? `<img src="uploads/images/${img}" style="width:100px; border-radius:5px;">` 
                    : '';
            });
        });

        // จัดการข้อมูลเข้า Modal ลบ
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('del_id').value = this.dataset.id;
                document.getElementById('del_name').innerText = this.dataset.name;
            });
        });
    </script>
</body>
</html>
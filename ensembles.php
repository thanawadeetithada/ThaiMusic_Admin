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

    $stmt = $conn->prepare("INSERT INTO ensembles (name, cover_image) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $cover_image);
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

    $track_stmt = $conn->prepare("
        SELECT t.audio_file, t.instrument_icon 
        FROM tracks t 
        INNER JOIN songs s ON t.song_id = s.song_id 
        WHERE s.ensemble_id = ?
    ");
    $track_stmt->bind_param("i", $del_id);
    $track_stmt->execute();
    $track_res = $track_stmt->get_result();
    
    while ($track = $track_res->fetch_assoc()) {
        if (!empty($track['audio_file']) && file_exists("uploads/audio/" . $track['audio_file'])) {
            unlink("uploads/audio/" . $track['audio_file']);
        }
        if (!empty($track['instrument_icon']) && file_exists("uploads/images/" . $track['instrument_icon'])) {
            unlink("uploads/images/" . $track['instrument_icon']);
        }
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
        .btn-gold { background-color: #d4af37; color: white; font-weight: 500; }
        .btn-gold:hover { background-color: #b5952f; color: white; }
        .card-header-custom { background-color: #2c5364; color: white; font-weight: 500; }
        .table-responsive { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="content-area pb-5">
        
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header card-header-custom py-3">
                <h5 class="mb-0"><i class="fa-solid fa-compact-disc me-2"></i> เพิ่มวงดนตรีใหม่</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data" class="row g-3 align-items-end">
                    <input type="hidden" name="add_ensemble" value="1">
                    <div class="col-md-5">
                        <label class="form-label">ชื่อวงดนตรี</label>
                        <input type="text" name="name" class="form-control" placeholder="เช่น วงปี่พาทย์เครื่องคู่" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">รูปภาพหน้าปก</label>
                        <input type="file" name="cover_image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-gold w-100"><i class="fa-solid fa-plus me-1"></i> เพิ่มวง</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2"></i> รายการวงดนตรีทั้งหมด</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="10%" class="text-center">ปก</th>
                                <th class="ps-4">ชื่อวงดนตรี</th>
                                <th width="25%" class="text-center">จัดการ</th>
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
                                <td class="fw-bold text-dark ps-4"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="songs.php?ensemble_id=<?= $row['ensemble_id'] ?>" class="btn btn-sm btn-outline-primary px-3">
                                            จัดการเพลง <i class="fa-solid fa-arrow-right ms-1"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger px-3 delete-btn"
                                            data-id="<?= $row['ensemble_id'] ?>"
                                            data-name="<?= htmlspecialchars($row['name']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteEnsembleModal">
                                            <i class="fa-solid fa-trash"></i> ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($ensembles->num_rows == 0): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">ยังไม่มีวงดนตรีในระบบ</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div> <div class="modal fade" id="deleteEnsembleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow">
                <form action="" method="POST">
                    <input type="hidden" name="delete_ensemble" value="1">
                    <input type="hidden" name="delete_ensemble_id" id="del_id">
                    
                    <div class="modal-body text-center p-4 p-md-5">
                        <i class="fa-solid fa-triangle-exclamation text-danger mb-3" style="font-size: 60px;"></i>
                        <h4 class="mb-3 fw-bold text-dark">ยืนยันการลบวงดนตรี?</h4>
                        
                        <p class="text-muted mb-4" style="font-size: 15px;">
                            คุณต้องการลบวง <strong id="del_name" class="text-dark fs-5"></strong> ใช่หรือไม่?<br>
                            <span class="d-block mt-3 bg-danger-subtle text-danger p-2 rounded">
                                <i class="fa-solid fa-circle-info me-1"></i> คำเตือน! เพลงและแทร็กทั้งหมด รวมถึงไฟล์เสียงที่อยู่ในวงนี้ จะถูกลบออกแบบถาวร
                            </span>
                        </p>
                        
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-secondary w-50 py-2 fw-bold" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-danger w-50 py-2 fw-bold">ยืนยันการลบ</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll('.delete-btn').forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('del_id').value = this.getAttribute('data-id');
                    document.getElementById('del_name').innerText = this.getAttribute('data-name');
                });
            });
        });
    </script>
</body>
</html>
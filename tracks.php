<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['userrole'] === 'user') {
    header("Location: index.php");
    exit();
}

$song_id = $_GET['song_id'] ?? 0;

$stmt = $conn->prepare("SELECT title, ensemble_id FROM songs WHERE song_id = ?");
$stmt->bind_param("i", $song_id);
$stmt->execute();
$song = $stmt->get_result()->fetch_assoc();

if (!$song) die("ไม่พบเพลงที่เลือก");


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_track'])) {
    $name = trim($_POST['instrument_name']);
    $color = $_POST['track_color'];
    $sort = intval($_POST['sort_order']);
    
    $audio_file = '';
    $icon_file = '';

    if (!is_dir('uploads/audio')) mkdir('uploads/audio', 0777, true);
    if (!is_dir('uploads/images')) mkdir('uploads/images', 0777, true);

    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['wav', 'mp3'])) {
            $audio_file = "audio_" . time() . "." . $ext;
            move_uploaded_file($_FILES['audio_file']['tmp_name'], "uploads/audio/" . $audio_file);
        }
    }

    if (isset($_FILES['instrument_icon']) && $_FILES['instrument_icon']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['instrument_icon']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['png', 'jpg', 'jpeg', 'jfif', 'gif', 'webp'])) {
            $icon_file = "icon_" . time() . "." . $ext;
            move_uploaded_file($_FILES['instrument_icon']['tmp_name'], "uploads/images/" . $icon_file);
        }
    }

    if ($audio_file) {
        $stmt = $conn->prepare("INSERT INTO tracks (song_id, instrument_name, instrument_icon, track_color, audio_file, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssi", $song_id, $name, $icon_file, $color, $audio_file, $sort);
        $stmt->execute();
    }
    header("Location: tracks.php?song_id=" . $song_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_track'])) {
    $del_id = intval($_POST['delete_track_id']);
    
    $stmt = $conn->prepare("SELECT audio_file, instrument_icon FROM tracks WHERE track_id = ? AND song_id = ?");
    $stmt->bind_param("ii", $del_id, $song_id);
    $stmt->execute();
    $file_res = $stmt->get_result()->fetch_assoc();
    
    if ($file_res) {
        if (!empty($file_res['audio_file']) && file_exists("uploads/audio/" . $file_res['audio_file'])) {
            unlink("uploads/audio/" . $file_res['audio_file']);
        }
        if (!empty($file_res['instrument_icon']) && file_exists("uploads/images/" . $file_res['instrument_icon'])) {
            unlink("uploads/images/" . $file_res['instrument_icon']);
        }
        
        $stmt = $conn->prepare("DELETE FROM tracks WHERE track_id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
    }
    
    header("Location: tracks.php?song_id=" . $song_id);
    exit();
}

$tracks = $conn->query("SELECT * FROM tracks WHERE song_id = $song_id ORDER BY sort_order ASC");
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการแทร็กเสียง - Thai Music Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
    body {
        font-family: 'Prompt', sans-serif;
        background-color: #f4f6f9;
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

    .card-header-custom {
        background-color: #2c5364;
        color: white;
        font-weight: 500;
    }

    .table-responsive {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    audio {
        height: 35px;
        outline: none;
    }

    /* 📌 CSS สำหรับหน้าจอ Loading Spinner */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        /* พื้นหลังสีดำโปร่งแสง */
        z-index: 9999;
        /* ให้อยู่บนสุดเสมอ */
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(3px);
        /* เบลอพื้นหลังนิดๆ */
    }

    .spinner-gold {
        color: #d4af37;
        /* สีทองให้เข้ากับธีม */
    }

    a:focus {
        border: none;
    }
    </style>
</head>

<body>

    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="spinner-border spinner-gold" role="status" style="width: 4rem; height: 4rem; border-width: 0.3em;">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="text-white mt-3 fw-bold">กำลังประมวลผล...</h4>
        <p class="text-white-50">กรุณารอสักครู่ ระบบกำลังจัดการไฟล์</p>
    </div>

    <?php include 'sidebar.php'; ?>

    <main class="content-area pb-5">

        <div class="d-flex align-items-center mb-4">
            <a href="songs.php?ensemble_id=<?= $song['ensemble_id'] ?>" class="btn btn-m"><i
                    class="fa-solid fa-arrow-left me-1"></i></a>
            <h4 class="mb-0 fw-bold">แทร็กเครื่องดนตรี ในเพลง: <span
                    class="text-primary"><?= htmlspecialchars($song['title']) ?></span></h4>

        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header card-header-custom py-3">
                <h5 class="mb-0"><i class="fa-solid fa-sliders me-2"></i> เพิ่มแทร็กดนตรีใหม่</h5>
            </div>
            <div class="card-body">
                <form id="addTrackForm" method="POST" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="add_track" value="1">
                    <div class="col-md-3">
                        <label class="form-label">ชื่อเครื่องดนตรี</label>
                        <input type="text" name="instrument_name" class="form-control" placeholder="เช่น ระนาดเอก"
                            required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-danger fw-bold">ไฟล์เสียง (.wav, .mp3) *</label>
                        <input type="file" name="audio_file" class="form-control" accept=".wav,.mp3" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">รูปไอคอน (เช่น .png, .jpg, .jfif)</label>
                        <input type="file" name="instrument_icon" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">สี</label>
                        <input type="color" name="track_color" class="form-control form-control-color w-100"
                            value="#d4af37">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">ลำดับ</label>
                        <input type="number" name="sort_order" class="form-control" value="0">
                    </div>
                    <div class="col-12 mt-3 text-end">
                        <button type="submit" class="btn btn-gold px-4"><i class="fa-solid fa-upload me-1"></i>
                            อัปโหลดแทร็ก</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="10%" class="text-center">ลำดับ</th>
                                <th width="10%" class="text-center">สี</th>
                                <th width="15%" class="text-center">ไอคอน</th>
                                <th width="15%" class="text-center">เครื่องดนตรี</th>
                                <th width="35%" class="text-center">ไฟล์เสียง (ฟังตัวอย่าง)</th>
                                <th width="15%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $tracks->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center"><?= $row['sort_order'] ?></td>
                                <td class="text-center">
                                    <div
                                        style="width: 25px; height: 25px; background-color: <?= htmlspecialchars($row['track_color']) ?>; border-radius: 5px; margin: 0 auto; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <?php if($row['instrument_icon']): ?>
                                    <img src="uploads/images/<?= htmlspecialchars($row['instrument_icon']) ?>"
                                        width="40" height="40" style="object-fit: contain;"
                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-block';">
                                    <i class="fa-solid fa-guitar text-muted fs-4" style="display: none;"></i>
                                    <?php else: ?>
                                    <i class="fa-solid fa-guitar text-muted fs-4"></i>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center fw-bold text-dark">
                                    <?= htmlspecialchars($row['instrument_name']) ?></td>
                                <td class="text-center">
                                    <audio controls>
                                        <source src="uploads/audio/<?= htmlspecialchars($row['audio_file']) ?>"
                                            type="audio/<?= pathinfo($row['audio_file'], PATHINFO_EXTENSION) == 'wav' ? 'wav' : 'mpeg' ?>">
                                    </audio>
                                </td>
                                <td class="text-center">
                                    <button type="button"
                                        class="btn btn-outline-danger btn-sm rounded-circle delete-btn"
                                        data-id="<?= $row['track_id'] ?>"
                                        data-name="<?= htmlspecialchars($row['instrument_name']) ?>"
                                        data-bs-toggle="modal" data-bs-target="#deleteTrackModal">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($tracks->num_rows == 0): ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">ยังไม่มีแทร็กเสียงในเพลงนี้</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>

    <div class="modal fade" id="deleteTrackModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow">
                <form id="deleteTrackForm" action="" method="POST">
                    <input type="hidden" name="delete_track" value="1">
                    <input type="hidden" name="delete_track_id" id="del_id">

                    <div class="modal-body text-center p-4 p-md-5">
                        <i class="fa-solid fa-triangle-exclamation text-danger mb-3" style="font-size: 60px;"></i>
                        <h4 class="mb-3 fw-bold text-dark">ยืนยันการลบแทร็ก?</h4>

                        <p class="text-muted mb-4" style="font-size: 15px;">
                            คุณต้องการลบเครื่องดนตรี <strong id="del_name" class="text-dark fs-5"></strong>
                            ใช่หรือไม่?<br>
                            <span class="d-block mt-3 bg-danger-subtle text-danger p-2 rounded">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                ไฟล์เสียงและรูปไอคอนที่เกี่ยวข้องจะถูกลบออกแบบถาวร
                            </span>
                        </p>

                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-secondary w-50 py-2 fw-bold"
                                data-bs-dismiss="modal">ยกเลิก</button>
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

        const addForm = document.getElementById('addTrackForm');
        if (addForm) {
            addForm.addEventListener('submit', function() {
                document.getElementById('loadingOverlay').classList.remove('d-none');
            });
        }

        const deleteForm = document.getElementById('deleteTrackForm');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function() {
                var myModalEl = document.getElementById('deleteTrackModal');
                var modalInstance = bootstrap.Modal.getInstance(myModalEl);
                if (modalInstance) {
                    modalInstance.hide();
                }
                document.getElementById('loadingOverlay').classList.remove('d-none');
            });
        }

    });
    </script>
</body>

</html>
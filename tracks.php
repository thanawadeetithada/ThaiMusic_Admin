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

    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        $audio_file = "audio_" . time() . "_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['audio_file']['tmp_name'], "uploads/audio/" . $audio_file);
    }

    if (isset($_FILES['instrument_icon']) && $_FILES['instrument_icon']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['instrument_icon']['name'], PATHINFO_EXTENSION));
        $icon_file = "icon_" . time() . "_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['instrument_icon']['tmp_name'], "uploads/images/" . $icon_file);
    }

    if ($audio_file) {
        $stmt = $conn->prepare("INSERT INTO tracks (song_id, instrument_name, instrument_icon, track_color, audio_file, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->bind_param("issssi", $song_id, $name, $icon_file, $color, $audio_file, $sort);
        $stmt->execute();
    }
    header("Location: tracks.php?song_id=" . $song_id);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_track'])) {
    $track_id = intval($_POST['track_id']);
    $name = trim($_POST['instrument_name']);
    $color = $_POST['track_color'];
    $sort = intval($_POST['sort_order']);

    $stmt = $conn->prepare("SELECT audio_file, instrument_icon FROM tracks WHERE track_id = ?");
    $stmt->bind_param("i", $track_id);
    $stmt->execute();
    $current = $stmt->get_result()->fetch_assoc();

    $audio_file = $current['audio_file'];
    $icon_file = $current['instrument_icon'];

    if (isset($_FILES['audio_file']) && $_FILES['audio_file']['error'] == 0) {
        if(!empty($current['audio_file']) && file_exists("uploads/audio/".$current['audio_file'])) unlink("uploads/audio/".$current['audio_file']);
        $ext = strtolower(pathinfo($_FILES['audio_file']['name'], PATHINFO_EXTENSION));
        $audio_file = "audio_" . time() . "_" . uniqid() . "." . $ext;
        move_uploaded_file($_FILES['audio_file']['tmp_name'], "uploads/audio/" . $audio_file);
    }

    if (isset($_FILES['instrument_icon']) && $_FILES['instrument_icon']['error'] == 0) {
        if(!empty($current['instrument_icon']) && file_exists("uploads/images/".$current['instrument_icon'])) unlink("uploads/images/".$current['instrument_icon']);
        $icon_file = "icon_" . time() . "_" . uniqid() . "." . strtolower(pathinfo($_FILES['instrument_icon']['name'], PATHINFO_EXTENSION));
        move_uploaded_file($_FILES['instrument_icon']['tmp_name'], "uploads/images/" . $icon_file);
    }

    $stmt = $conn->prepare("UPDATE tracks SET instrument_name=?, instrument_icon=?, track_color=?, audio_file=?, sort_order=? WHERE track_id=?");
    $stmt->bind_param("ssssii", $name, $icon_file, $color, $audio_file, $sort, $track_id);
    $stmt->execute();
    header("Location: tracks.php?song_id=" . $song_id);
    exit();
}


if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $new_status = ($_GET['toggle_status'] == 1) ? 0 : 1;
    $stmt = $conn->prepare("UPDATE tracks SET is_active = ? WHERE track_id = ?");
    $stmt->bind_param("ii", $new_status, $id);
    $stmt->execute();
    header("Location: tracks.php?song_id=" . $song_id);
    exit();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_track'])) {
    $del_id = intval($_POST['delete_track_id']);
    $stmt = $conn->prepare("SELECT audio_file, instrument_icon FROM tracks WHERE track_id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $file_res = $stmt->get_result()->fetch_assoc();
    if ($file_res) {
        if (!empty($file_res['audio_file']) && file_exists("uploads/audio/" . $file_res['audio_file'])) unlink("uploads/audio/" . $file_res['audio_file']);
        if (!empty($file_res['instrument_icon']) && file_exists("uploads/images/" . $file_res['instrument_icon'])) unlink("uploads/images/" . $file_res['instrument_icon']);
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
    <title>จัดการแทร็ก - Thai Music</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background-color: #f4f6f9; }
        .btn-gold { background-color: #d4af37; color: white; }
        .card-header-custom { background-color: #2c5364; color: white; }
        .form-switch .form-check-input { width: 2.5em; height: 1.25em; cursor: pointer; }
        .loading-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; display: flex; flex-direction: column; justify-content: center; align-items: center; }
    </style>
</head>
<body>

    <div id="loadingOverlay" class="loading-overlay d-none">
        <div class="spinner-border text-warning" role="status"></div>
        <h4 class="text-white mt-3">กำลังประมวลผล...</h4>
    </div>

    <?php include 'sidebar.php'; ?>

    <main class="content-area container py-4">
        <div class="d-flex align-items-center mb-4">
            <a href="songs.php?ensemble_id=<?= $song['ensemble_id'] ?>" class="btn btn-m"><i class="fa-solid fa-arrow-left"></i></a>
            <h4 class="mb-0 fw-bold ms-2">แทร็กเพลง: <span class="text-primary"><?= htmlspecialchars($song['title']) ?></span></h4>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header card-header-custom py-3"><h5 class="mb-0">เพิ่มแทร็กใหม่</h5></div>
            <div class="card-body">
                <form id="addTrackForm" method="POST" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="add_track" value="1">
                    <div class="col-md-3"><label class="form-label">ชื่อเครื่องดนตรี</label><input type="text" name="instrument_name" class="form-control" required></div>
                    <div class="col-md-4"><label class="form-label text-danger fw-bold">ไฟล์เสียง *</label><input type="file" name="audio_file" class="form-control" accept=".wav,.mp3" required></div>
                    <div class="col-md-3"><label class="form-label">รูปไอคอน</label><input type="file" name="instrument_icon" class="form-control" accept="image/*"></div>
                    <div class="col-md-1"><label class="form-label">สี</label><input type="color" name="track_color" class="form-control form-control-color w-100" value="#d4af37"></div>
                    <div class="col-md-1"><label class="form-label">ลำดับ</label><input type="number" name="sort_order" class="form-control" value="0"></div>
                    <div class="col-12 text-end"><button type="submit" class="btn btn-gold">อัปโหลด</button></div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-center">
                        <thead class="table-light">
                            <tr>
                                <th width="10%">ลำดับ</th>
                                <th width="10%">สี</th>
                                <th width="10%">ไอคอน</th>
                                <th width="15%">เครื่องดนตรี</th>
                                <th width="30%">ตัวอย่างเสียง</th>
                                <th width="10%">สถานะ</th>
                                <th width="15%">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $tracks->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['sort_order'] ?></td>
                                <td><div style="width: 25px; height: 25px; background:<?= $row['track_color'] ?>; border-radius: 5px; margin: 0 auto;"></div></td>
                                <td>
                                    <?php if($row['instrument_icon']): ?>
                                        <img src="uploads/images/<?= $row['instrument_icon'] ?>" width="40" height="40" style="object-fit: contain;">
                                    <?php else: ?><i class="fa-solid fa-guitar text-muted"></i><?php endif; ?>
                                </td>
                                <td class="fw-bold"><?= htmlspecialchars($row['instrument_name']) ?></td>
                                <td><audio controls style="height: 30px;"><source src="uploads/audio/<?= $row['audio_file'] ?>"></audio></td>
                                <td>
                                    <div class="form-check form-switch d-inline-block">
                                        <input class="form-check-input" type="checkbox" role="switch" <?= $row['is_active'] ? 'checked' : '' ?>
                                               onclick="window.location.href='?song_id=<?= $song_id ?>&toggle_status=<?= $row['is_active'] ?>&id=<?= $row['track_id'] ?>'">
                                    </div>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning edit-btn" 
                                            data-id="<?= $row['track_id'] ?>" data-name="<?= htmlspecialchars($row['instrument_name']) ?>"
                                            data-color="<?= $row['track_color'] ?>" data-sort="<?= $row['sort_order'] ?>"
                                            data-bs-toggle="modal" data-bs-target="#editModal"><i class="fa-solid fa-pen"></i></button>
                                    <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?= $row['track_id'] ?>" 
                                            data-name="<?= htmlspecialchars($row['instrument_name']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteModal"><i class="fa-solid fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header"><h5 class="modal-title">แก้ไขแทร็ก</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body row g-3">
                        <input type="hidden" name="edit_track" value="1"><input type="hidden" name="track_id" id="edit_id">
                        <div class="col-md-6"><label class="form-label">ชื่อเครื่องดนตรี</label><input type="text" name="instrument_name" id="edit_name" class="form-control" required></div>
                        <div class="col-md-4"><label class="form-label">ลำดับ</label><input type="number" name="sort_order" id="edit_sort" class="form-control" required></div>
                        <div class="col-md-2"><label class="form-label">สี</label><input type="color" name="track_color" id="edit_color" class="form-control form-control-color w-100"></div>
                        <div class="col-md-6"><label class="form-label">เปลี่ยนเสียง (เว้นว่างได้)</label><input type="file" name="audio_file" class="form-control" accept=".wav,.mp3"></div>
                        <div class="col-md-6"><label class="form-label">เปลี่ยนไอคอน (เว้นว่างได้)</label><input type="file" name="instrument_icon" class="form-control" accept="image/*"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-primary" onclick="showLoading()">บันทึก</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered"><div class="modal-content"><form method="POST">
            <input type="hidden" name="delete_track" value="1"><input type="hidden" name="delete_track_id" id="del_id">
            <div class="modal-body text-center p-5">
                <i class="fa-solid fa-triangle-exclamation text-danger mb-3 fs-1"></i>
                <h4>ลบ <span id="del_name"></span>?</h4>
                <div class="mt-4"><button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">ยกเลิก</button><button type="submit" class="btn btn-danger" onclick="showLoading()">ยืนยัน</button></div>
            </div>
        </form></div></div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLoading() { document.getElementById('loadingOverlay').classList.remove('d-none'); }
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('edit_id').value = this.dataset.id;
                document.getElementById('edit_name').value = this.dataset.name;
                document.getElementById('edit_color').value = this.dataset.color;
                document.getElementById('edit_sort').value = this.dataset.sort;
            });
        });
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('del_id').value = this.dataset.id;
                document.getElementById('del_name').innerText = this.dataset.name;
            });
        });
        document.getElementById('addTrackForm').addEventListener('submit', showLoading);
    </script>
</body>
</html>
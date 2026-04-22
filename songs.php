<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['userrole'] === 'user') {
    header("Location: index.php");
    exit();
}

$ensemble_id = $_GET['ensemble_id'] ?? 0;

$stmt = $conn->prepare("SELECT name FROM ensembles WHERE ensemble_id = ?");
$stmt->bind_param("i", $ensemble_id);
$stmt->execute();
$ensemble = $stmt->get_result()->fetch_assoc();

if (!$ensemble) die("ไม่พบวงดนตรีที่เลือก <a href='ensembles.php'>กลับ</a>");

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_song'])) {
    $title = trim($_POST['title']);
    $bpm = intval($_POST['bpm']);

    $stmt = $conn->prepare("INSERT INTO songs (ensemble_id, title, bpm) VALUES (?, ?, ?)");
    $stmt->bind_param("isi", $ensemble_id, $title, $bpm);
    $stmt->execute();
    header("Location: songs.php?ensemble_id=" . $ensemble_id);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_song'])) {
    $del_id = intval($_POST['delete_song_id']);
    
    $track_stmt = $conn->prepare("SELECT audio_file, instrument_icon FROM tracks WHERE song_id = ?");
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
    
    $del_stmt = $conn->prepare("DELETE FROM songs WHERE song_id = ?");
    $del_stmt->bind_param("i", $del_id);
    $del_stmt->execute();
    
    header("Location: songs.php?ensemble_id=" . $ensemble_id);
    exit();
}

$songs = $conn->query("SELECT * FROM songs WHERE ensemble_id = $ensemble_id ORDER BY song_id DESC");
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเพลง - Thai Music Admin</title>
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

    a:focus {
        border: none;
    }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <main class="content-area pb-5">

        <div class="d-flex align-items-center mb-4">
            <a href="ensembles.php" class="btn btn-m"><i class="fa-solid fa-arrow-left me-1"></i></a>
            <h4 class="mb-0 fw-bold">เพลงในวง: <span
                    class="text-primary"><?= htmlspecialchars($ensemble['name']) ?></span></h4>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header card-header-custom py-3">
                <h5 class="mb-0"><i class="fa-solid fa-music me-2"></i> เพิ่มเพลงใหม่</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="add_song" value="1">
                    <div class="col-md-6">
                        <label class="form-label">ชื่อเพลง</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ความเร็ว (BPM)</label>
                        <input type="number" name="bpm" class="form-control" value="100">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-gold w-100"><i class="fa-solid fa-plus me-1"></i>
                            เพิ่มเพลง</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 ">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">ชื่อเพลง</th>
                                <th>BPM</th>
                                <th width="25%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $songs->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-dark ps-4"><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= $row['bpm'] ?></td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="tracks.php?song_id=<?= $row['song_id'] ?>"
                                            class="btn btn-sm btn-outline-success px-3">
                                            แทร็กเครื่องดนตรี <i class="fa-solid fa-sliders ms-1"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger px-3 delete-btn"
                                            data-id="<?= $row['song_id'] ?>"
                                            data-name="<?= htmlspecialchars($row['title']) ?>"
                                            data-bs-toggle="modal" data-bs-target="#deleteSongModal">
                                            <i class="fa-solid fa-trash"></i> ลบ
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if($songs->num_rows == 0): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">ยังไม่มีเพลงในวงนี้</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
    </div>

    <div class="modal fade" id="deleteSongModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow">
                <form action="" method="POST">
                    <input type="hidden" name="delete_song" value="1">
                    <input type="hidden" name="delete_song_id" id="del_id">
                    
                    <div class="modal-body text-center p-4 p-md-5">
                        <i class="fa-solid fa-triangle-exclamation text-danger mb-3" style="font-size: 60px;"></i>
                        <h4 class="mb-3 fw-bold text-dark">ยืนยันการลบเพลง?</h4>
                        
                        <p class="text-muted mb-4" style="font-size: 15px;">
                            คุณต้องการลบเพลง <strong id="del_name" class="text-dark fs-5"></strong> ใช่หรือไม่?<br>
                            <span class="d-block mt-3 bg-danger-subtle text-danger p-2 rounded">
                                <i class="fa-solid fa-circle-info me-1"></i> ไฟล์เสียงแทร็กและรูปภาพทั้งหมดที่อยู่ในเพลงนี้ จะถูกลบออกแบบถาวรทั้งหมด!
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
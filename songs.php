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

    <main class="content-area">

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
                                <th width="20%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $songs->fetch_assoc()): ?>
                            <tr>
                                <td class="fw-bold text-dark ps-4"><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= $row['bpm'] ?></td>
                                <td class="text-center">
                                    <a href="tracks.php?song_id=<?= $row['song_id'] ?>"
                                        class="btn btn-sm btn-outline-success px-3">
                                        แทร็กเครื่องดนตรี <i class="fa-solid fa-sliders ms-1"></i>
                                    </a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
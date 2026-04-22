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

    <main class="content-area">
        
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
                                <th>ชื่อวงดนตรี</th>
                                <th width="20%" class="text-center">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $ensembles->fetch_assoc()): ?>
                            <tr>
                                <td class="text-center">
                                    <?php if($row['cover_image']): ?>
                                        <img src="uploads/images/<?= $row['cover_image'] ?>" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px;">
                                    <?php else: ?>
                                        <div style="width: 50px; height: 50px; background:#eee; border-radius: 8px; display:inline-flex; align-items:center; justify-content:center;"><i class="fa-solid fa-image text-muted"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-center">
                                    <a href="songs.php?ensemble_id=<?= $row['ensemble_id'] ?>" class="btn btn-sm btn-outline-primary px-3">
                                        จัดการเพลง <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </main>
</div> <script src="https: 
</body>
</html>
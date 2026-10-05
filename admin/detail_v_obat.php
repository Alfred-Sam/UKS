<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit;
}

$conn = new mysqli("localhost", "root", "", "uks");

// UPDATE NAMA OBAT
if (isset($_POST['update'])) {
    $id = intval($_POST['id_obat']);
    $nama_baru = trim($_POST['nama_baru']);

    if (!empty($nama_baru)) {
        $conn->query("UPDATE obat SET nama_obat='$nama_baru' WHERE id_obat=$id");
        $_SESSION['success'] = "Nama obat berhasil diupdate";
    }
    header("Location: detail_v_obat.php");
    exit;
}

// TAMBAH / EDIT OBAT
if (isset($_POST['tambah'])) {
    $nama = trim($_POST['nama_obat']);

    if (isset($_POST['id_edit']) && !empty($_POST['id_edit'])) {
        $id = intval($_POST['id_edit']);
        $conn->query("UPDATE obat SET nama_obat='$nama' WHERE id_obat=$id");
        $_SESSION['success'] = "Nama obat berhasil diupdate";
    } else {
        $conn->query("INSERT INTO obat(nama_obat) VALUES('$nama')");
        $_SESSION['success'] = "Data obat berhasil ditambahkan";
    }

    header("Location: detail_v_obat.php");
    exit;
}

// PAGINATION & SEARCH
$limit = 20;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$start = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';

// TOTAL DATA
$total_data = $conn->query("SELECT COUNT(*) as total FROM obat WHERE nama_obat LIKE '%$search%'")->fetch_assoc()['total'];
$total_page = ceil($total_data / $limit);

// DATA OBAT + VALUE sebagai total digunakan
$data = $conn->query("
    SELECT o.id_obat, 
           o.nama_obat,
           COALESCE(SUM(po.jumlah), 0) as value
    FROM obat o
    LEFT JOIN pemeriksaan_obat po ON o.id_obat = po.id_obat
    WHERE o.nama_obat LIKE '%$search%'
    GROUP BY o.id_obat, o.nama_obat
    ORDER BY o.nama_obat ASC 
    LIMIT $start, $limit
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Detail Value Obat</title>
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI';
            background: #f4f6f9;
        }
        .header {
            background: #1b4f9c;
            color: white;
            text-align: center;
            padding: 20px;
            font-weight: bold;
        }
        .container {
            padding: 20px;
        }
        .box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #1b4f9c;
            color: white;
            padding: 12px;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
            text-align: center;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary {
            background: #1b4f9c;
            color: white;
        }
        .btn-warning {
            background: #f0ad4e;
            color: white;
        }
        input {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>

<div class="header">DETAIL VALUE OBAT</div>

<div class="container">
    <form method="GET" style="margin-bottom:20px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
        <input type="text" name="search" placeholder="Cari nama obat..." value="<?= htmlspecialchars($search) ?>"
            style="padding:8px; width:280px;">
        <button type="submit" class="btn btn-primary">🔍 Search</button>
        <a href="detail_v_obat.php" class="btn" style="background:#6c757d; color:white !important;">Reset</a>
    </form>

    <a href="../admin/dashboard.php" class="btn btn-primary">← Kembali</a>
    <br><br>

    <?php if (isset($_SESSION['success'])): ?>
        <div id="notif"
            style="background:#28a745; color:white; padding:12px; border-radius:8px; margin-bottom:20px; text-align:center;">
            <?= $_SESSION['success']; ?>
        </div>
        <?php unset($_SESSION['success']); endif; ?>

    <div class="box">
        <h3>Data Obat</h3>

        <?php
        $obat_list = [];
        while ($row = $data->fetch_assoc()) {
            $obat_list[] = $row;
        }
        $left = array_slice($obat_list, 0, 10);
        $right = array_slice($obat_list, 10, 10);
        ?>

        <div style="display:flex; gap:20px; flex-wrap:wrap;">

            <!-- KOLOM KIRI -->
            <div style="flex:1; min-width:300px;">
                <table style="width:100%; border-collapse:collapse;">
                    <tr>
                        <th style="width:40px;">No</th>
                        <th style="text-align:center;">Nama Obat</th>
                        <th style="width:70px;">Digunakan</th>
                        <th style="width:100px;">Aksi</th>
                    </tr>
                    <?php $no = $start + 1; foreach ($left as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td style="text-align:left;">
                                <input type="text" class="namaObat" data-id="<?= $row['id_obat'] ?>"
                                    value="<?= htmlspecialchars($row['nama_obat']) ?>"
                                    style="width:140px; padding:5px 8px;">
                            </td>
                            <td><?= $row['value'] ?>x</td>
                            <td>
                                <button type="button" class="btn btn-warning" style="padding:4px 8px; font-size:12px;"
                                    onclick="bukaModalUpdate(<?= $row['id_obat'] ?>, this)">
                                    Update
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <!-- KOLOM KANAN -->
            <div style="flex:1; min-width:300px;">
                <?php if (!empty($right)): ?>
                    <table style="width:100%; border-collapse:collapse;">
                        <tr>
                            <th style="width:40px;">No</th>
                            <th style="text-align:center;">Nama Obat</th>
                            <th style="width:70px;">Digunakan</th>
                            <th style="width:100px;">Aksi</th>
                        </tr>
                        <?php foreach ($right as $row): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td style="text-align:left;">
                                    <input type="text" class="namaObat" data-id="<?= $row['id_obat'] ?>"
                                        value="<?= htmlspecialchars($row['nama_obat']) ?>"
                                        style="width:140px; padding:5px 8px;">
                                </td>
                                <td><?= $row['value'] ?>x</td>
                                <td>
                                    <button type="button" class="btn btn-warning" style="padding:4px 8px; font-size:12px;"
                                        onclick="bukaModalUpdate(<?= $row['id_obat'] ?>, this)">
                                        Update
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Pagination -->
        <br>
        <div style="display:flex; justify-content:center; gap:8px;">
            <?php for ($i = 1; $i <= $total_page; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"
                    style="padding:8px 12px; border-radius:6px; text-decoration:none; background:<?= ($page == $i) ? '#1b4f9c' : '#eee' ?>; color:<?= ($page == $i) ? 'white' : 'black' ?>;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>

        <br><br>
        <h3>Tambah Obat Baru</h3>
        <form method="POST">
            <input type="text" name="nama_obat" placeholder="Nama Obat" required style="width:300px;">
            <button type="submit" name="tambah" class="btn btn-primary">Tambah</button>
        </form>
    </div>
</div>

<!-- Modal Update -->
<div id="modalUpdate"
    style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); justify-content:center; align-items:center; z-index:999;">
    <div style="background:white; padding:25px; border-radius:12px; width:350px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,0.2);">
        <h3 style="margin-top:0;">Update Nama Obat</h3>
        <p id="namaLama" style="font-weight: 400; color:#1b4f9c;"></p>
        <p>Yakin ingin mengubah nama obat ini?</p>
        <div style="margin-top:20px;">
            <button onclick="tutupModalUpdate()" class="btn" style="background:gray;">Tidak</button>
            <button id="btnYaUpdate" class="btn btn-warning">Ya, Update</button>
        </div>
    </div>
</div>

<script>
    let currentId = null;
    let currentInput = null;

    function bukaModalUpdate(id, button) {
        currentId = id;
        currentInput = button.closest('tr').querySelector('.namaObat');
        document.getElementById('namaLama').innerHTML =
            'Nama baru: <strong>' + currentInput.value + '</strong>';
        document.getElementById('modalUpdate').style.display = 'flex';
    }

    function tutupModalUpdate() {
        document.getElementById('modalUpdate').style.display = 'none';
    }

    document.getElementById('btnYaUpdate').onclick = function () {
        if (currentId && currentInput) {
            let form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            form.innerHTML = `
                <input type="hidden" name="id_obat" value="${currentId}">
                <input type="hidden" name="nama_baru" value="${currentInput.value}">
                <input type="hidden" name="update" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    };

    setTimeout(() => {
        let notif = document.getElementById("notif");
        if (notif) notif.style.display = "none";
    }, 5000);
</script>

</body>
</html>
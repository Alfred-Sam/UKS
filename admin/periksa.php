<?php
session_start();
$conn = new mysqli("localhost", "root", "", "uks");

$id = intval($_GET['id'] ?? 0);

$query = $conn->query("SELECT * FROM kunjungan WHERE id_kunjungan = '$id'");
$kunjungan = $query->fetch_assoc();

$cek = $conn->query("SELECT * FROM pemeriksaan_klinis WHERE id_kunjungan = '$id'");
$data_pemeriksaan = $cek->fetch_assoc();

$pasien = null;

if ($kunjungan) {
    if ($kunjungan['jenis_pasien'] == 'siswa') {
        $pasien = $conn->query("SELECT * FROM t_siswa WHERE NIS='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    } elseif ($kunjungan['jenis_pasien'] == 'guru') {
        $pasien = $conn->query("SELECT * FROM guru WHERE id_guru='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    } elseif ($kunjungan['jenis_pasien'] == 'karyawan') {
        $pasien = $conn->query("SELECT * FROM karyawan WHERE id_karyawan='" . $kunjungan['id_pasien'] . "'")->fetch_assoc();
    }
}

// Ambil master daftar obat
$master_obat = [];
$query_obat = $conn->query("SELECT * FROM obat ORDER BY nama_obat ASC");
while ($row = $query_obat->fetch_assoc()) {
    $master_obat[] = $row;
}

// Ambil obat yang sudah dipilih beserta jumlahnya (untuk edit)
$obat_terpilih = [];
if ($data_pemeriksaan) {
    $res = $conn->query("SELECT id_obat, jumlah FROM pemeriksaan_obat WHERE id_pemeriksaan = '" . $data_pemeriksaan['id_pemeriksaan'] . "' ORDER BY id ASC");
    while ($row = $res->fetch_assoc()) {
        $obat_terpilih[] = [
            'id_obat' => intval($row['id_obat']),
            'jumlah' => intval($row['jumlah'] ?? 1)
        ];
    }
}

// SIMPAN
if (isset($_POST['simpan'])) {

    $catatan = $conn->real_escape_string($_POST['catatan']);
    $status = $conn->real_escape_string($_POST['status']);
    $jenis_penyakit = $conn->real_escape_string($_POST['jenis_penyakit']);
    $id_obat_array = $_POST['id_obat'] ?? [];
    $jumlah_obat_array = $_POST['jumlah_obat'] ?? [];

    if ($data_pemeriksaan) {
        $id_pemeriksaan = $data_pemeriksaan['id_pemeriksaan'];

        $conn->query("UPDATE pemeriksaan_klinis SET 
                    catatan_admin = '$catatan',
                    status_pasien = '$status',
                    jenis_penyakit = '$jenis_penyakit'
                    WHERE id_kunjungan = '$id'");

        // Hapus obat lama
        $conn->query("DELETE FROM pemeriksaan_obat WHERE id_pemeriksaan = '$id_pemeriksaan'");

    } else {
        $conn->query("INSERT INTO pemeriksaan_klinis 
                    (id_kunjungan, catatan_admin, status_pasien, jenis_penyakit)
                    VALUES ('$id', '$catatan', '$status', '$jenis_penyakit')");

        $id_pemeriksaan = $conn->insert_id;
    }

    // Insert semua obat yang dipilih beserta jumlahnya
    for ($i = 0; $i < count($id_obat_array); $i++) {
        $id_obat = intval($id_obat_array[$i] ?? 0);
        $jumlah = intval($jumlah_obat_array[$i] ?? 1);
        if ($jumlah < 1) {
            $jumlah = 1;
        }

        if ($id_obat > 0) {
            $conn->query("INSERT INTO pemeriksaan_obat (id_pemeriksaan, id_obat, jumlah) 
                     VALUES ('$id_pemeriksaan', '$id_obat', '$jumlah')");
        }
    }

    header("Location: ../admin/dashboard.php?status=sukses");
    exit;
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Periksa Pasien</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        /* Style tetap sama seperti sebelumnya */
        body {
            font-family: 'Segoe UI';
            background: #1f5faa;
            margin: 0;
        }

        .header {
            background: #1b4f9c;
            color: white;
            text-align: center;
            padding: 20px;
            font-weight: bold;
            border-bottom: 5px solid #ffd700;
        }

        .form-box {
            background: white;
            max-width: 550px;
            margin: 40px auto;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        h2 {
            text-align: center;
        }

        textarea,
        input,
        select {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin: 10px 0 15px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }

        .btn-submit {
            width: 100%;
            background: #1b4f9c;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 15px;
            margin-top: 15px;
        }

        .btn-submit:hover {
            background: #ffd700;
            color: black;
        }

        .btn-tambah-obat {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 14px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-tambah-obat:hover {
            background: #218838;
        }

        .obat-item-row {
            display: flex;
            gap: 8px;
            align-items: center;
            margin-bottom: 10px;
        }

        .btn-hapus-obat {
            background: #dc3545;
            color: white;
            border: none;
            padding: 9px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 13px;
        }

        .btn-hapus-obat:hover {
            background: #c82333;
        }

        .select2-container .select2-selection--single {
            height: 40px !important;
            border: 1px solid #ccc !important;
            border-radius: 6px !important;
            display: flex !important;
            align-items: center !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
        }
    </style>
</head>

<body>

    <div class="header">INPUT PEMERIKSAAN</div>

    <div class="form-box">
        <h2>Form Pemeriksaan</h2>

        <?php if ($pasien && $kunjungan): ?>
            <?php if ($kunjungan['jenis_pasien'] == 'siswa'): ?>
                <p><b>NIS:</b> <?= $pasien['NIS']; ?></p>
                <p><b>Nama:</b> <?= $pasien['NAMA_SISWA']; ?></p>
                <p><b>Jenis Kelamin:</b> <?= $pasien['JENIS_KELAMIN']; ?></p>
            <?php elseif ($kunjungan['jenis_pasien'] == 'guru'): ?>
                <p><b>Nama:</b> <?= $pasien['nama']; ?></p>
                <p><b>Jenis Kelamin:</b> <?= $pasien['jenis_kelamin']; ?></p>
            <?php elseif ($kunjungan['jenis_pasien'] == 'karyawan'): ?>
                <p><b>Nama:</b> <?= $pasien['nama']; ?></p>
                <p><b>Jenis Kelamin:</b> <?= $pasien['jenis_kelamin']; ?></p>
            <?php endif; ?>
            <p><b>Keluhan:</b> <?= $kunjungan['keluhan']; ?></p>
        <?php else: ?>
            <p style="color:red;">Data pasien tidak ditemukan</p>
        <?php endif; ?>

        <form method="POST">
            <label>Catatan</label>
            <textarea name="catatan" required><?= $data_pemeriksaan['catatan_admin'] ?? '' ?></textarea>

            <label>Kategori Keluhan</label>
            <select name="jenis_penyakit" id="jenis_penyakit" required>
                <option value="">-- Pilih Kategori Keluhan --</option>
                <?php
                $kategori = ["Pusing", "Demam", "Batuk / Flu", "Sakit Perut", "Luka Ringan", "Nyeri Haid", "Cedera", "Kelelahan", "Mimisan", "Alergi", "Iritasi Mata", "Tegang / Stres Ringan", "Keluhan Kulit Ringan", "Sakit Gigi", "Masalah Tenggorokan", "Masuk Angin", "Lainnya"];
                foreach ($kategori as $item):
                    $selected = ($data_pemeriksaan['jenis_penyakit'] ?? '') == $item ? 'selected' : '';
                    ?>
                    <option value="<?= $item ?>" <?= $selected ?>><?= $item ?></option>
                <?php endforeach; ?>
            </select>

            <div style="margin-top: 15px; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">
                <label style="font-weight: 600; margin: 0;">Obat Yang Diberikan</label>
                <button type="button" class="btn-tambah-obat" onclick="tambahBarisObat()">+ Tambah Obat</button>
            </div>
            <small style="color: #666; display: block; margin-bottom: 10px;">
                Pilih jenis obat dan tentukan jumlahnya. Anda bisa menambahkan lebih dari satu obat atau obat yang sama dengan jumlah berbeda.
            </small>

            <div id="list-obat-container">
                <!-- Baris obat akan di-generate oleh javascript -->
            </div>

            <label style="display:block; margin-top:18px;">Status Pasien</label>
            <select name="status" required>
                <option value="Istirahat di UKS" <?= (($data_pemeriksaan['status_pasien'] ?? '') == 'Istirahat di UKS') ? 'selected' : '' ?>>Istirahat di UKS</option>
                <option value="Dipulangkan" <?= (($data_pemeriksaan['status_pasien'] ?? '') == 'Dipulangkan') ? 'selected' : '' ?>>Dipulangkan</option>
                <option value="Dirujuk" <?= (($data_pemeriksaan['status_pasien'] ?? '') == 'Dirujuk') ? 'selected' : '' ?>>
                    Dirujuk</option>
                <option value="Kembali melanjutkan aktivitas" <?= (($data_pemeriksaan['status_pasien'] ?? '') == 'Kembali melanjutkan aktivitas') ? 'selected' : '' ?>>Kembali melanjutkan aktivitas</option>
            </select>

            <button type="submit" name="simpan" class="btn-submit">Simpan Pemeriksaan</button>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        const masterObat = <?= json_encode($master_obat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
        const initialObat = <?= json_encode($obat_terpilih, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/[&<>"']/g, function(m) {
                switch (m) {
                    case '&': return '&amp;';
                    case '<': return '&lt;';
                    case '>': return '&gt;';
                    case '"': return '&quot;';
                    case "'": return '&#039;';
                }
            });
        }

        function tambahBarisObat(selectedId = '', jumlah = 1) {
            const container = document.getElementById('list-obat-container');
            const rowId = 'row-obat-' + Date.now() + '-' + Math.floor(Math.random() * 10000);

            const row = document.createElement('div');
            row.className = 'obat-item-row';
            row.id = rowId;

            let optionsHtml = '<option value="">-- Pilih Obat --</option>';
            masterObat.forEach(function(obat) {
                const isSelected = (obat.id_obat == selectedId) ? 'selected' : '';
                optionsHtml += `<option value="${obat.id_obat}" ${isSelected}>${escapeHtml(obat.nama_obat)}</option>`;
            });

            row.innerHTML = `
                <div style="flex: 1; min-width: 0;">
                    <select name="id_obat[]" class="select-obat" style="width: 100%;">
                        ${optionsHtml}
                    </select>
                </div>
                <div style="width: 90px; flex-shrink: 0;">
                    <input type="number" name="jumlah_obat[]" min="1" value="${jumlah}" placeholder="Jml" title="Jumlah obat" style="margin: 0; padding: 9px 8px; border-radius: 6px; border: 1px solid #ccc; font-size: 14px;">
                </div>
                <div style="flex-shrink: 0;">
                    <button type="button" class="btn-hapus-obat" onclick="hapusBarisObat('${rowId}')" title="Hapus obat ini">✕</button>
                </div>
            `;

            container.appendChild(row);

            // Inisialisasi Select2 pada dropdown baru
            $(row).find('.select-obat').select2({
                placeholder: "Cari obat...",
                width: '100%'
            });
        }

        function hapusBarisObat(rowId) {
            const row = document.getElementById(rowId);
            if (row) {
                $(row).find('.select-obat').select2('destroy');
                row.remove();
            }
        }

        $(document).ready(function () {
            $('#jenis_penyakit').select2({ placeholder: "Cari kategori...", width: '100%' });

            if (initialObat && initialObat.length > 0) {
                initialObat.forEach(function(item) {
                    tambahBarisObat(item.id_obat, item.jumlah);
                });
            } else {
                tambahBarisObat('', 1);
            }
        });
    </script>

</body>

</html>
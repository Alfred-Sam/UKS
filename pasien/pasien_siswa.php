<?php
require('../template/header.php');

$conn = new mysqli("localhost", "root", "", "uks");

if (isset($_POST['kirim'])) {
    $nis = $_POST['nis'];
    $keluhan = $_POST['keluhan'];

    $q = $conn->query("SELECT * FROM t_siswa WHERE NIS='$nis'");
    $siswa = $q->fetch_assoc();
    if (!$siswa) {
        echo "<script>alert('NIS tidak ditemukan!'); window.location='pasien_siswa.php';</script>";
        exit;
    }
    $nis = $siswa['NIS'];

    $conn->query("INSERT INTO kunjungan (jenis_pasien, id_pasien, keluhan) 
    VALUES ('siswa', '$nis', '$keluhan')");

    header("Location: ../ruang_tunggu/ruang_tunggu_siswa.php?nis=$nis");
    exit;
}
?>

<div class="title-box">
    <h2>Pasien Siswa</h2>
</div>

<div class="form-container">
    <form method="POST">

        <div class="form-group" style="position:relative;">
            <label>NIS</label>
            <input type="text" name="nis" id="nis" required placeholder="Masukkan / cari NIS siswa">
            <div id="suggestions"></div>
        </div>

        <div class="form-group">
            <label>Nama</label>
            <input type="text" id="nama" readonly placeholder="Terisi otomatis setelah pilih NIS">
        </div>

        <div class="form-group">
            <label>Jenis Kelamin</label>
            <input type="text" id="jk" readonly placeholder="Terisi otomatis">
        </div>

        <div class="form-group">
            <label>Keluhan</label>
            <textarea name="keluhan" required placeholder="Masukkan keluhan pasien"></textarea>
        </div>

        <div class="form-group button-group">
            <button type="button" name="kirim" id="btnKirim">Kirim</button>
        </div>

    </form>
</div>

<style>
    .form-container {
        width: 100%;
        max-width: 700px;
        /* Dipersempit sedikit agar fokus di tengah */
        margin: 10px auto;
        background: #f9f9f9;
        /* Opsional: agar form lebih menonjol */
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        display: flex;
        align-items: center;
        /* Label & Input sejajar vertikal */
        margin-bottom: 12px;
        /* Jarak antar baris lebih rapat */
    }

    .form-group label {
        width: 150px;
        /* Ukuran area label */
        text-align: left;
        /* Rata kiri sesuai permintaanmu */
        font-weight: bold;
        color: #333;
        font-size: 14px;
        flex-shrink: 0;
        /* Label tidak akan gepeng saat di-zoom */
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        flex: 1;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
        font-size: 14px;
        outline: none;
        transition: 0.2s;
    }

    .form-group input:focus,
    .form-group textarea:focus {
        border-color: #1b4f9c;
        box-shadow: 0 0 5px rgba(27, 79, 156, 0.2);
    }

    /* Textarea hemat ruang */
    textarea {
        height: 60px;
        /* Lebih pendek biar tidak makan tempat */
        resize: none;
    }

    /* Tombol */
    .button-group {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 15px;
    }

    button {
        background: #1b4f9c;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        transition: 0.3s;
    }

    button:hover {
        background: #ffd700;
        color: black;
        transform: translateY(-2px);
    }

    /* Responsif untuk HP */
    @media (max-width: 600px) {
        .form-group {
            flex-direction: column;
            align-items: flex-start;
        }

        .form-group label {
            margin-bottom: 5px;
            width: 100%;
        }
    }

    #suggestions {
        position: absolute;
        top: 100%;
        left: 150px;
        right: 0;
        background: white;
        border: 1px solid #ccc;
        border-top: none;
        z-index: 10;
    }

    #suggestions div {
        padding: 8px;
        cursor: pointer;
    }

    #suggestions div:hover {
        background: #f1f1f1;
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const nisInput = document.getElementById("nis");

    nisInput.addEventListener("keyup", function () {
        let nis = this.value;

        let list = document.getElementById("suggestions");

        if (nis.length < 1) {
            list.innerHTML = "";
            return;
        }

        fetch("get_siswa.php?nis=" + nis)
            .then(res => res.json())
            .then(data => {

                list.innerHTML = "";

                data.forEach(item => {
                    let div = document.createElement("div");
                    div.innerText = item.NIS + " - " + item.NAMA_SISWA;

                    div.onclick = function () {
                        document.getElementById("nis").value = item.NIS;
                        document.getElementById("nama").value = item.NAMA_SISWA;
                        document.getElementById("jk").value = item.JENIS_KELAMIN;
                        list.innerHTML = "";
                    };

                    list.appendChild(div);
                });

            });
    });


    // ⬇️ TARUH DI SINI (masih dalam <script>)
    document.addEventListener("click", function (e) {
        if (!e.target.closest(".form-group")) {
            document.getElementById("suggestions").innerHTML = "";
        }
    });

    document.addEventListener("click", function (e) {
        if (!e.target.closest(".form-group")) {
            document.getElementById("suggestions").innerHTML = "";
        }
    });


    // VERIFIKASI MODERN
    function verifikasiKirim() {

        // VERIFIKASI MODERN
        function verifikasiKirim() {

            Swal.fire({
                title: 'Periksa Data',
                text: 'Pastikan data pasien sudah benar.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Kirim',
                cancelButtonText: 'Batal'

            }).then((result) => {

                if (result.isConfirmed) {

                    // BUAT INPUT KIRIM AGAR PHP TERBACA
                    let input = document.createElement("input");

                    input.type = "hidden";
                    input.name = "kirim";
                    input.value = "1";

                    document.querySelector("form").appendChild(input);

                    document.querySelector("form").submit();

                }

            });

        }

        document.getElementById("btnKirim").addEventListener("click", verifikasiKirim);
    }

    document.getElementById("btnKirim").addEventListener("click", verifikasiKirim);

</script>


<?php require('../template/footer.php'); ?>
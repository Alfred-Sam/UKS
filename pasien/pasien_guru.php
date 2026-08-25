<?php
require('../template/header.php');

$conn = new mysqli("localhost", "root", "", "uks");

if (isset($_POST['kirim'])) {
    $id = $_POST['id_pasien']; // hidden
    $keluhan = $_POST['keluhan'];

    $cek = $conn->query("SELECT * FROM guru WHERE id_guru='$id'");
    if ($cek->num_rows == 0) {
        die("Data guru tidak valid!");
    }
    // VALIDASI
    if (empty($id)) {
        echo "<script>alert('Pilih nama dari daftar dulu!'); window.history.back();</script>";
        exit;
    }

    // ✅ INSERT (yang tadi hilang)
    if (!$conn->query("INSERT INTO kunjungan (jenis_pasien, id_pasien, keluhan) VALUES ('guru', '$id', '$keluhan')")) {
        die("Error: " . $conn->error);
    }

    // ✅ REDIRECT (yang tadi hilang)
    header("Location: ../ruang_tunggu/ruang_tunggu_guru.php?id=$id");
    exit;
}
?>

<div class="title-box">
    <h2>Pasien Guru</h2>
</div>

<div class="form-container">
    <form method="POST">

        <!-- NAMA -->
        <div class="form-group">
            <label>Nama</label>
            <input type="text" id="nama_input" autocomplete="off" required
                placeholder="Ketik nama lalu pilih dari daftar">
            <div id="suggestions"></div>
        </div>

        <!-- JK -->
        <div class="form-group">
            <label>Jenis Kelamin</label>
            <input type="text" id="jk" readonly placeholder="Terisi otomatis setelah pilih nama">
        </div>

        <!-- HIDDEN ID -->
        <input type="hidden" name="id_pasien" id="id_pasien">

        <!-- KELUHAN -->
        <div class="form-group">
            <label>Keluhan</label>
            <textarea name="keluhan" required placeholder="Masukkan keluhan pasien"></textarea>
        </div>

        <!-- BUTTON -->
        <div class="form-group button-group">
            <button type="button" name="kirim" id="btnKirim">Kirim</button>
        </div>

    </form>
</div>

<style>
    /* FORM */
    .form-container {
        width: 100%;
        max-width: 700px;
        margin: 10px auto;
        background: #f9f9f9;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
        position: relative;
    }

    .form-group label {
        width: 150px;
        font-weight: bold;
    }

    .form-group input,
    .form-group textarea {
        flex: 1;
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #ccc;
    }

    textarea {
        height: 60px;
        resize: none;
    }

    /* BUTTON */
    .button-group {
        display: flex;
        justify-content: flex-end;
    }

    button {
        background: #1b4f9c;
        color: white;
        border: none;
        padding: 10px 30px;
        border-radius: 8px;
        cursor: pointer;
    }

    button:hover {
        background: #ffd700;
        color: black;
    }

    /* AUTOCOMPLETE */
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
    const input = document.getElementById("nama_input");

    input.addEventListener("keyup", function () {
        let nama = this.value;

        if (nama.length < 1) {
            document.getElementById("suggestions").innerHTML = "";
            return;
        }

        fetch("get_guru.php?nama=" + nama)
            .then(res => res.json())
            .then(data => {

                let list = document.getElementById("suggestions");
                list.innerHTML = "";

                data.forEach(item => {
                    let div = document.createElement("div");
                    div.innerText = item.nama + " (" + item.jenis_kelamin + ")";

                    div.onclick = function () {
                        document.getElementById("nama_input").value = item.nama;
                        document.getElementById("jk").value = item.jenis_kelamin;
                        document.getElementById("id_pasien").value = item.id_guru;
                        list.innerHTML = "";
                    };

                    list.appendChild(div);
                });

            });
    });

    // RESET ID kalau user ngetik ulang
    document.getElementById("nama_input").addEventListener("input", function () {
        document.getElementById("id_pasien").value = "";
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

                    document.querySelector("form").submit();  //<!-- error -->

                }

            });

        }

        document.getElementById("btnKirim").addEventListener("click", verifikasiKirim);

    }

    document.getElementById("btnKirim").addEventListener("click", verifikasiKirim);
</script>

<?php require('../template/footer.php'); ?>
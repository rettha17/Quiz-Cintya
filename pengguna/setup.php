<?php
/**
 * One-time setup script: creates any missing tables/columns and seeds
 * kategori + starter soal so every kategori x level combination is playable.
 * Safe to run more than once (idempotent) and never deletes existing data.
 * Delete this file after running it once.
 */

include "koneksi.php";

if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die("Setup hanya bisa dijalankan dari localhost.");
}

function kolomAda($conn, $table, $kolom) {
    $table = mysqli_real_escape_string($conn, $table);
    $kolom = mysqli_real_escape_string($conn, $kolom);
    $r = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$kolom'");
    return mysqli_num_rows($r) > 0;
}

echo "<pre>";

/* ===========================
   TABEL DASAR (aman jika sudah ada)
=========================== */

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    foto VARCHAR(255) DEFAULT 'default.png',
    level INT DEFAULT 1,
    xp INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS kategori (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    icon VARCHAR(100),
    deskripsi TEXT
)");

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS soal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT,
    level ENUM('Mudah','Sedang','Sulit') DEFAULT 'Mudah',
    pertanyaan TEXT,
    opsi_a VARCHAR(255),
    opsi_b VARCHAR(255),
    opsi_c VARCHAR(255),
    opsi_d VARCHAR(255),
    jawaban CHAR(1),
    pembahasan TEXT,
    KEY idx_kategori_level (kategori_id, level)
)");

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS hasil_quiz (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    kategori_id INT,
    level ENUM('Mudah','Sedang','Sulit') DEFAULT 'Mudah',
    score INT,
    xp INT,
    tanggal TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_user (user_id),
    KEY idx_kategori (kategori_id)
)");

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "
CREATE TABLE IF NOT EXISTS materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori_id INT NOT NULL,
    judul VARCHAR(150) NOT NULL,
    isi TEXT NOT NULL,
    urutan INT DEFAULT 0,
    KEY idx_kategori (kategori_id)
)");

echo "Tabel dasar OK.\n";

/* ===========================
   SEED AKUN ADMIN DEFAULT
=========================== */

$cekAdmin = mysqli_query($conn, "SELECT id FROM admin WHERE username='admin'");
if (mysqli_num_rows($cekAdmin) == 0) {
    $hashAdmin = password_hash("admin123", PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, "INSERT INTO admin (nama, username, password) VALUES (?,?,?)");
    $nama = "Administrator";
    $username = "admin";
    mysqli_stmt_bind_param($stmt, "sss", $nama, $username, $hashAdmin);
    mysqli_stmt_execute($stmt);
    echo "Akun admin default dibuat -> username: admin / password: admin123 (segera ganti setelah login).\n";
} else {
    echo "Akun admin sudah ada.\n";
}

/* ===========================
   TAMBAH KOLOM YANG BELUM ADA
=========================== */

if (!kolomAda($conn, 'hasil_quiz', 'level')) {
    mysqli_query($conn, "ALTER TABLE hasil_quiz ADD COLUMN level ENUM('Mudah','Sedang','Sulit') DEFAULT 'Mudah' AFTER kategori_id");
    echo "Kolom hasil_quiz.level ditambahkan.\n";
} else {
    echo "Kolom hasil_quiz.level sudah ada.\n";
}

/* ===========================
   SEED KATEGORI
=========================== */

$kategoriSeed = [
    ['id' => 1, 'nama' => 'Statistika Dasar', 'icon' => 'fa-chart-simple', 'deskripsi' => 'Pelajari konsep dasar statistika seperti Mean, Median, Modus, Tabel, dan Diagram.'],
    ['id' => 2, 'nama' => 'Pengenalan BPS', 'icon' => 'fa-building', 'deskripsi' => 'Kenali sejarah BPS, tugas, fungsi, sensus dan survei nasional.'],
    ['id' => 3, 'nama' => 'Indikator Statistik', 'icon' => 'fa-chart-line', 'deskripsi' => 'Belajar mengenai IPM, Inflasi, Kemiskinan, Pengangguran dan PDRB.'],
];

foreach ($kategoriSeed as $k) {
    $cek = mysqli_query($conn, "SELECT id FROM kategori WHERE id={$k['id']}");
    if (mysqli_num_rows($cek) == 0) {
        $stmt = mysqli_prepare($conn, "INSERT INTO kategori (id, nama, icon, deskripsi) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($stmt, "isss", $k['id'], $k['nama'], $k['icon'], $k['deskripsi']);
        mysqli_stmt_execute($stmt);
        echo "Kategori '{$k['nama']}' ditambahkan.\n";
    }
}

/* ===========================
   SEED SOAL
   (menambah soal baru hanya jika kategori+level
   punya kurang dari 4 soal, agar tidak duplikat)
=========================== */

$MINIMAL_SOAL_PER_LEVEL = 4;

$soalSeed = [

    // ===== Kategori 1: Statistika Dasar =====
    1 => [
        'Mudah' => [
            ['Median adalah istilah untuk?', 'Rata-rata data', 'Nilai tengah data yang telah diurutkan', 'Nilai yang sering muncul', 'Jumlah seluruh data', 'B', 'Median adalah nilai tengah data setelah data diurutkan dari terkecil ke terbesar.'],
            ['Statistika adalah ilmu yang mempelajari cara?', 'Mengumpulkan, mengolah, menyajikan, dan menganalisis data', 'Menghitung uang', 'Membuat grafik saja', 'Mensurvei penduduk saja', 'A', 'Statistika mencakup seluruh proses pengumpulan hingga analisis data.'],
        ],
        'Sedang' => [
            ['Median dari data 3, 7, 5, 9, 2 adalah?', '3', '5', '7', '9', 'B', 'Setelah diurutkan menjadi 2, 3, 5, 7, 9, nilai tengahnya adalah 5.'],
            ['Diagram yang paling cocok untuk menampilkan perbandingan proporsi/persentase adalah?', 'Diagram Garis', 'Diagram Batang', 'Diagram Lingkaran', 'Tabel Distribusi', 'C', 'Diagram lingkaran (pie chart) digunakan untuk menampilkan proporsi bagian terhadap keseluruhan.'],
            ['Median lebih cocok digunakan dibanding mean ketika data memiliki?', 'Data yang simetris', 'Nilai ekstrem (outlier)', 'Jumlah data sedikit', 'Data berbentuk grafik', 'B', 'Median tidak terpengaruh oleh nilai ekstrem, sehingga lebih representatif saat ada outlier.'],
            ['Tabel distribusi frekuensi digunakan untuk?', 'Menyajikan data mentah tanpa pengelompokan', 'Mengelompokkan data dan menunjukkan banyaknya kemunculan tiap kelompok', 'Menghitung mean saja', 'Mengganti diagram batang', 'B', 'Tabel distribusi frekuensi mengelompokkan data ke dalam kelas-kelas beserta frekuensinya.'],
        ],
        'Sulit' => [
            ['Data: 4, 8, 6, 5, 3, 8, 9. Modus dari data tersebut adalah?', '4', '6', '8', '9', 'C', 'Angka 8 muncul dua kali, lebih sering dibanding angka lainnya, sehingga menjadi modus.'],
            ['Mean dari data 2, 4, 6, 8, 10 adalah?', '5', '6', '7', '8', 'B', 'Mean = (2+4+6+8+10)/5 = 30/5 = 6.'],
            ['Untuk data berjumlah genap, median dihitung dengan cara?', 'Mengambil satu nilai tengah', 'Rata-rata dua nilai tengah setelah data diurutkan', 'Mengambil modus data', 'Total data dibagi banyak data', 'B', 'Pada data genap, median adalah rata-rata dua nilai yang berada di tengah setelah data diurutkan.'],
            ['Grafik yang paling tepat untuk menunjukkan tren/perkembangan data dari waktu ke waktu adalah?', 'Diagram Lingkaran', 'Diagram Garis', 'Diagram Batang', 'Tabel Distribusi', 'B', 'Diagram garis cocok untuk menampilkan perubahan data terhadap waktu.'],
        ],
    ],

    // ===== Kategori 2: Pengenalan BPS =====
    2 => [
        'Mudah' => [
            ['BPS adalah Lembaga Pemerintah Non Kementerian yang bertanggung jawab langsung kepada?', 'Gubernur', 'Presiden', 'Menteri Dalam Negeri', 'DPR', 'B', 'BPS bertanggung jawab langsung kepada Presiden sebagai Lembaga Pemerintah Non Kementerian.'],
            ['Sensus Penduduk di Indonesia rutin dilakukan setiap berapa tahun sekali?', '5 tahun', '10 tahun', '15 tahun', '20 tahun', 'B', 'Sensus Penduduk dilaksanakan setiap 10 tahun sekali, pada tahun yang berakhiran angka 0.'],
            ['Kegiatan mengumpulkan data dari seluruh unit populasi disebut?', 'Survei', 'Sensus', 'Riset', 'Observasi', 'B', 'Sensus mencacah seluruh populasi, berbeda dengan survei yang hanya menggunakan sampel.'],
        ],
        'Sedang' => [
            ['Sensus Pertanian di Indonesia dilaksanakan pada tahun yang berakhiran angka?', '0', '3', '5', '7', 'B', 'Sensus Pertanian dilaksanakan setiap 10 tahun pada tahun berakhiran angka 3, misalnya 2013 dan 2023.'],
            ['Sensus Ekonomi di Indonesia dilaksanakan pada tahun yang berakhiran angka?', '0', '6', '8', '9', 'B', 'Sensus Ekonomi dilaksanakan setiap 10 tahun pada tahun berakhiran angka 6, misalnya 2016.'],
            ['Perbedaan utama survei dengan sensus adalah?', 'Survei mencakup seluruh populasi, sensus memakai sampel', 'Survei memakai sebagian data (sampel), sensus mencakup seluruh populasi', 'Survei dan sensus sama saja', 'Survei hanya untuk data ekonomi', 'B', 'Survei menggunakan sampel untuk mewakili populasi, sedangkan sensus mencakup seluruh populasi.'],
            ['Salah satu fungsi utama BPS adalah?', 'Menetapkan kebijakan fiskal negara', 'Menyediakan data statistik dasar bagi pemerintah dan masyarakat', 'Mengelola anggaran negara', 'Menyusun undang-undang', 'B', 'BPS berfungsi menyediakan data statistik dasar yang digunakan lintas sektor.'],
        ],
        'Sulit' => [
            ['Dasar hukum penyelenggaraan statistik dan pembentukan BPS diatur dalam?', 'UU No. 16 Tahun 1997 tentang Statistik', 'UU No. 8 Tahun 1999', 'UU No. 25 Tahun 2004', 'UU No. 23 Tahun 2014', 'A', 'UU No. 16 Tahun 1997 tentang Statistik menjadi dasar hukum penyelenggaraan kegiatan statistik dan BPS.'],
            ['Data yang dihasilkan BPS dan digunakan secara luas oleh berbagai sektor disebut?', 'Data Sektoral', 'Data Statistik Dasar', 'Data Khusus', 'Data Internal', 'B', 'BPS menghasilkan data statistik dasar yang menjadi acuan lintas sektor dan instansi.'],
            ['Survei rutin BPS yang mengukur kondisi sosial ekonomi rumah tangga adalah?', 'Susenas', 'Sensus Pertanian', 'Sensus Ekonomi', 'Sakernas', 'A', 'Susenas (Survei Sosial Ekonomi Nasional) mengukur kondisi sosial ekonomi rumah tangga secara rutin.'],
            ['Survei Angkatan Kerja Nasional yang mengukur kondisi ketenagakerjaan disebut?', 'Susenas', 'Sakernas', 'SP2020', 'SE2016', 'B', 'Sakernas (Survei Angkatan Kerja Nasional) digunakan untuk mengukur kondisi ketenagakerjaan, termasuk pengangguran.'],
        ],
    ],

    // ===== Kategori 3: Indikator Statistik =====
    3 => [
        'Mudah' => [
            ['IPM adalah singkatan dari?', 'Indeks Pembangunan Manusia', 'Indeks Produktivitas Masyarakat', 'Indikator Pendapatan Masyarakat', 'Indeks Pertumbuhan Modal', 'A', 'IPM (Indeks Pembangunan Manusia) mengukur capaian pembangunan manusia suatu wilayah.'],
            ['Kenaikan harga barang dan jasa secara umum dan terus-menerus disebut?', 'Deflasi', 'Inflasi', 'Resesi', 'Devaluasi', 'B', 'Inflasi adalah kenaikan harga barang dan jasa secara umum dan terus-menerus.'],
            ['Persentase penduduk yang berada di bawah garis kemiskinan disebut?', 'Tingkat Pengangguran', 'Tingkat Kemiskinan', 'Tingkat Inflasi', 'Tingkat Pertumbuhan Ekonomi', 'B', 'Tingkat kemiskinan menunjukkan persentase penduduk yang hidup di bawah garis kemiskinan.'],
            ['PDRB adalah singkatan dari?', 'Produk Domestik Regional Bruto', 'Pendapatan Daerah Regional Bruto', 'Produk Domestik Rata-rata Bruto', 'Pertumbuhan Domestik Regional Bruto', 'A', 'PDRB (Produk Domestik Regional Bruto) mengukur nilai tambah ekonomi suatu wilayah.'],
        ],
        'Sedang' => [
            ['IPM dihitung berdasarkan tiga dimensi dasar, yaitu?', 'Kesehatan, Pendidikan, Standar Hidup Layak', 'Kesehatan, Ekonomi, Politik', 'Pendidikan, Infrastruktur, Keamanan', 'Ekonomi, Sosial, Budaya', 'A', 'IPM dibangun dari tiga dimensi: umur panjang dan hidup sehat, pengetahuan, dan standar hidup layak.'],
            ['Tingkat Pengangguran Terbuka (TPT) menunjukkan?', 'Persentase penduduk usia kerja yang bekerja', 'Persentase angkatan kerja yang tidak memiliki pekerjaan', 'Jumlah penduduk usia produktif', 'Jumlah lapangan kerja baru', 'B', 'TPT adalah persentase angkatan kerja yang sedang mencari pekerjaan/tidak bekerja.'],
            ['PDRB dapat digunakan untuk mengukur?', 'Tingkat kebahagiaan penduduk', 'Kinerja/pertumbuhan ekonomi suatu wilayah', 'Jumlah penduduk miskin', 'Tingkat pendidikan masyarakat', 'B', 'PDRB mencerminkan kinerja dan pertumbuhan ekonomi suatu wilayah dalam periode tertentu.'],
            ['Garis kemiskinan versi BPS ditentukan berdasarkan?', 'Pendapatan minimum regional', 'Nilai pengeluaran minimum untuk kebutuhan dasar makanan dan non-makanan', 'Harga emas', 'Jumlah utang penduduk', 'B', 'Garis kemiskinan BPS dihitung dari nilai pengeluaran minimum untuk kebutuhan dasar makanan dan non-makanan.'],
        ],
        'Sulit' => [
            ['Inflasi di Indonesia umumnya diukur menggunakan perubahan?', 'Indeks Harga Konsumen (IHK)', 'Indeks Harga Produsen saja', 'Nilai Tukar Rupiah', 'Suku Bunga Bank Indonesia', 'A', 'Inflasi dihitung dari perubahan Indeks Harga Konsumen (IHK) dari waktu ke waktu.'],
            ['PDRB atas dasar harga konstan digunakan untuk melihat?', 'Pertumbuhan ekonomi riil tanpa pengaruh inflasi', 'Nilai nominal ekonomi saat ini', 'Jumlah penduduk', 'Tingkat kemiskinan', 'A', 'Harga konstan menghilangkan pengaruh inflasi sehingga mencerminkan pertumbuhan ekonomi riil.'],
            ['Semakin tinggi nilai IPM suatu wilayah menunjukkan?', 'Semakin rendah kualitas pembangunan manusia', 'Semakin tinggi kualitas pembangunan manusia di wilayah tersebut', 'Semakin tinggi inflasi', 'Semakin tinggi pengangguran', 'B', 'Nilai IPM yang lebih tinggi mencerminkan capaian pembangunan manusia yang lebih baik.'],
            ['Kondisi ekonomi dengan pengangguran dan inflasi yang tinggi secara bersamaan disebut?', 'Deflasi', 'Stagflasi', 'Resesi', 'Depresiasi', 'B', 'Stagflasi adalah kondisi stagnasi ekonomi yang disertai inflasi tinggi dan pengangguran tinggi.'],
        ],
    ],
];

$totalDitambah = 0;

foreach ($soalSeed as $kategoriId => $perLevel) {
    foreach ($perLevel as $level => $daftarSoal) {

        $cek = mysqli_query($conn, "SELECT COUNT(*) c FROM soal WHERE kategori_id=$kategoriId AND level='$level'");
        $jumlahSekarang = (int) mysqli_fetch_assoc($cek)['c'];

        if ($jumlahSekarang >= $MINIMAL_SOAL_PER_LEVEL) {
            echo "Kategori $kategoriId - $level sudah punya $jumlahSekarang soal, dilewati.\n";
            continue;
        }

        $stmt = mysqli_prepare($conn, "
            INSERT INTO soal (kategori_id, level, pertanyaan, opsi_a, opsi_b, opsi_c, opsi_d, jawaban, pembahasan)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        foreach ($daftarSoal as $s) {
            if ($jumlahSekarang >= $MINIMAL_SOAL_PER_LEVEL) break;

            [$pertanyaan, $a, $b, $c, $d, $jawaban, $pembahasan] = $s;

            mysqli_stmt_bind_param($stmt, "issssssss", $kategoriId, $level, $pertanyaan, $a, $b, $c, $d, $jawaban, $pembahasan);
            mysqli_stmt_execute($stmt);

            $jumlahSekarang++;
            $totalDitambah++;
        }

        echo "Kategori $kategoriId - $level sekarang punya $jumlahSekarang soal.\n";
    }
}

echo "\nSelesai. $totalDitambah soal baru ditambahkan.\n";

/* ===========================
   SEED MATERI PEMBELAJARAN
   (migrasi dari konten yang dulu hardcode di materi.php,
   hanya dijalankan jika kategori tsb belum punya materi sama sekali)
=========================== */

$materiSeed = [
    1 => [
        ['judul' => 'Pengertian Statistika', 'isi' => 'Statistika adalah ilmu yang mempelajari cara mengumpulkan, mengolah, menyajikan, dan menganalisis data, sehingga data tersebut dapat memberikan informasi yang bermakna untuk pengambilan keputusan.'],
        ['judul' => 'Mean, Median, dan Modus', 'isi' => '<b>Mean (rata-rata)</b> adalah jumlah seluruh data dibagi banyaknya data. Contoh: mean dari 2, 4, 6, 8, 10 adalah (2+4+6+8+10)/5 = 6.<br><br><b>Median</b> adalah nilai tengah data setelah diurutkan. Jika jumlah data ganjil, median adalah nilai tengah tunggal; jika genap, median adalah rata-rata dua nilai tengah.<br><br><b>Modus</b> adalah nilai yang paling sering muncul dalam sekumpulan data.'],
        ['judul' => 'Grafik dan Tabel', 'isi' => 'Data dapat disajikan dalam berbagai bentuk agar mudah dipahami:<br><br><b>Diagram Batang</b> untuk membandingkan nilai antar kategori.<br><b>Diagram Garis</b> untuk menunjukkan tren/perkembangan data dari waktu ke waktu.<br><b>Diagram Lingkaran</b> untuk menunjukkan proporsi/persentase bagian terhadap keseluruhan.<br><b>Tabel Distribusi Frekuensi</b> untuk mengelompokkan data dan menunjukkan banyaknya kemunculan tiap kelompok.'],
    ],
    2 => [
        ['judul' => 'Apa itu BPS?', 'isi' => 'Badan Pusat Statistik (BPS) adalah Lembaga Pemerintah Non Kementerian yang bertanggung jawab langsung kepada Presiden. BPS bertugas menyediakan data statistik dasar bagi pemerintah dan masyarakat.'],
        ['judul' => 'Tugas dan Fungsi BPS', 'isi' => 'BPS berfungsi menyelenggarakan kegiatan statistik dasar, mengoordinasikan kegiatan statistik sektoral yang diselenggarakan instansi pemerintah, serta menyediakan data statistik yang digunakan lintas sektor. Dasar hukum penyelenggaraan statistik dan pembentukan BPS diatur dalam UU No. 16 Tahun 1997 tentang Statistik.'],
        ['judul' => 'Sensus Penduduk', 'isi' => 'Sensus adalah kegiatan mengumpulkan data dari seluruh unit populasi. Sensus Penduduk dilaksanakan setiap 10 tahun sekali, pada tahun yang berakhiran angka 0 (misalnya 2020, 2030).'],
        ['judul' => 'Sensus Pertanian', 'isi' => 'Sensus Pertanian dilaksanakan setiap 10 tahun sekali, pada tahun yang berakhiran angka 3 (misalnya 2013, 2023), untuk mendata seluruh unit usaha pertanian di Indonesia.'],
        ['judul' => 'Sensus Ekonomi', 'isi' => 'Sensus Ekonomi dilaksanakan setiap 10 tahun sekali, pada tahun yang berakhiran angka 6 (misalnya 2016, 2026), untuk mendata seluruh unit usaha di luar sektor pertanian.'],
        ['judul' => 'Survei BPS', 'isi' => 'Berbeda dengan sensus yang mencakup seluruh populasi, survei hanya menggunakan sebagian data (sampel) untuk mewakili populasi. Contoh survei rutin BPS: <b>Susenas</b> (Survei Sosial Ekonomi Nasional) untuk kondisi sosial ekonomi rumah tangga, dan <b>Sakernas</b> (Survei Angkatan Kerja Nasional) untuk kondisi ketenagakerjaan.'],
    ],
    3 => [
        ['judul' => 'Indeks Pembangunan Manusia (IPM)', 'isi' => 'IPM mengukur capaian pembangunan manusia suatu wilayah berdasarkan tiga dimensi dasar: kesehatan (umur panjang dan hidup sehat), pendidikan (pengetahuan), dan standar hidup layak (ekonomi). Semakin tinggi nilai IPM, semakin tinggi kualitas pembangunan manusia di wilayah tersebut.'],
        ['judul' => 'Produk Domestik Regional Bruto (PDRB)', 'isi' => 'PDRB mengukur nilai tambah ekonomi suatu wilayah dan mencerminkan kinerja/pertumbuhan ekonominya. PDRB atas dasar harga konstan digunakan untuk melihat pertumbuhan ekonomi riil tanpa pengaruh inflasi.'],
        ['judul' => 'Tingkat Pengangguran', 'isi' => 'Tingkat Pengangguran Terbuka (TPT) menunjukkan persentase angkatan kerja yang tidak memiliki pekerjaan/sedang mencari pekerjaan, diukur melalui Sakernas.'],
        ['judul' => 'Kemiskinan', 'isi' => 'Tingkat kemiskinan menunjukkan persentase penduduk yang berada di bawah garis kemiskinan. Garis kemiskinan versi BPS ditentukan berdasarkan nilai pengeluaran minimum untuk memenuhi kebutuhan dasar makanan dan non-makanan.'],
        ['judul' => 'Inflasi', 'isi' => 'Inflasi adalah kenaikan harga barang dan jasa secara umum dan terus-menerus. Inflasi di Indonesia umumnya diukur menggunakan perubahan Indeks Harga Konsumen (IHK) dari waktu ke waktu.'],
    ],
];

$totalMateriDitambah = 0;

foreach ($materiSeed as $kategoriId => $daftarMateri) {

    $cek = mysqli_query($conn, "SELECT COUNT(*) c FROM materi WHERE kategori_id=$kategoriId");
    $jumlahSekarang = (int) mysqli_fetch_assoc($cek)['c'];

    if ($jumlahSekarang > 0) {
        echo "Kategori $kategoriId sudah punya $jumlahSekarang materi, dilewati.\n";
        continue;
    }

    $stmt = mysqli_prepare($conn, "INSERT INTO materi (kategori_id, judul, isi, urutan) VALUES (?,?,?,?)");

    $urutan = 0;
    foreach ($daftarMateri as $m) {
        mysqli_stmt_bind_param($stmt, "issi", $kategoriId, $m['judul'], $m['isi'], $urutan);
        mysqli_stmt_execute($stmt);
        $urutan++;
        $totalMateriDitambah++;
    }

    echo "Kategori $kategoriId - $urutan materi ditambahkan.\n";
}

echo "Total materi baru ditambahkan: $totalMateriDitambah.\n";
echo "Silakan hapus file setup.php ini setelah selesai dijalankan.\n";
echo "</pre>";

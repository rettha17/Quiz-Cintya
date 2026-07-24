<?php
require __DIR__ . "/auth.php";
require_once __DIR__ . "/../inc/functions.php";

$pesan = "";
$pesanTipe = "success";

/* ===========================
   UBAH INFORMASI APLIKASI
=========================== */

if (isset($_POST['simpan_informasi'])) {

    $appNama = trim($_POST['app_nama']);
    $appVersi = trim($_POST['app_versi']);
    $appOrganisasi = trim($_POST['app_organisasi']);
    $appTahun = trim($_POST['app_tahun']);

    if ($appNama === '' || $appVersi === '' || $appOrganisasi === '' || $appTahun === '') {

        $pesan = "Semua field Informasi Aplikasi wajib diisi.";
        $pesanTipe = "error";

    } else {

        setSetting($conn, 'app_nama', $appNama);
        setSetting($conn, 'app_versi', $appVersi);
        setSetting($conn, 'app_organisasi', $appOrganisasi);
        setSetting($conn, 'app_tahun', $appTahun);

        $pesan = "Informasi aplikasi berhasil diperbarui.";

    }

}

/* ===========================
   EDIT PROFIL ADMINISTRATOR
=========================== */

if (isset($_POST['simpan_profil'])) {

    $namaProfil = trim($_POST['nama']);
    $usernameProfil = trim($_POST['username']);
    $emailProfil = trim($_POST['email']);
    $noTelpProfil = trim($_POST['no_telp']);

    if ($namaProfil === '' || $usernameProfil === '') {

        $pesan = "Nama dan Username wajib diisi.";
        $pesanTipe = "error";

    } else {

        $cek = mysqli_prepare($conn, "SELECT id FROM admin WHERE username=? AND id<>?");
        mysqli_stmt_bind_param($cek, "si", $usernameProfil, $admin_id);
        mysqli_stmt_execute($cek);

        if (mysqli_stmt_get_result($cek)->num_rows > 0) {

            $pesan = "Username sudah dipakai admin lain.";
            $pesanTipe = "error";

        } else {

            $fotoProfil = $admin['foto'];

            if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {

                $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

                if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {

                    $folder = __DIR__ . "/assets/img/avatars_admin/";
                    $namaFile = 'admin_' . $admin_id . '_' . time() . '.' . $ext;

                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $folder . $namaFile)) {

                        if (!empty($admin['foto']) && file_exists($folder . basename($admin['foto']))) {
                            @unlink($folder . basename($admin['foto']));
                        }

                        $fotoProfil = 'avatars_admin/' . $namaFile;

                    }

                }

            }

            $stmt = mysqli_prepare($conn, "UPDATE admin SET nama=?, username=?, email=?, no_telp=?, foto=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sssssi", $namaProfil, $usernameProfil, $emailProfil, $noTelpProfil, $fotoProfil, $admin_id);
            mysqli_stmt_execute($stmt);

            $admin['nama'] = $namaProfil;
            $admin['username'] = $usernameProfil;
            $admin['email'] = $emailProfil;
            $admin['no_telp'] = $noTelpProfil;
            $admin['foto'] = $fotoProfil;

            $pesan = "Profil administrator berhasil diperbarui.";

        }

    }

}

/* ===========================
   KEAMANAN (PASSWORD & 2FA)
=========================== */

if (isset($_POST['simpan_password'])) {

    $passwordBaru = $_POST['password_baru'];
    $konfirmasiPassword = $_POST['konfirmasi_password'];
    $twoFaBaru = isset($_POST['two_fa_enabled']) ? 1 : 0;

    if ($passwordBaru !== '' || $konfirmasiPassword !== '') {

        if ($passwordBaru !== $konfirmasiPassword) {

            $pesan = "Konfirmasi password tidak sama.";
            $pesanTipe = "error";

        } elseif (strlen($passwordBaru) < 8) {

            $pesan = "Password baru minimal 8 karakter.";
            $pesanTipe = "error";

        } else {

            $hash = password_hash($passwordBaru, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare($conn, "UPDATE admin SET password=?, two_fa_enabled=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, "sii", $hash, $twoFaBaru, $admin_id);
            mysqli_stmt_execute($stmt);

            $admin['two_fa_enabled'] = $twoFaBaru;
            $pesan = "Password & pengaturan keamanan berhasil diperbarui.";

        }

    } else {

        $stmt = mysqli_prepare($conn, "UPDATE admin SET two_fa_enabled=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "ii", $twoFaBaru, $admin_id);
        mysqli_stmt_execute($stmt);

        $admin['two_fa_enabled'] = $twoFaBaru;
        $pesan = "Pengaturan keamanan berhasil diperbarui.";

    }

}

/* ===========================
   PENGATURAN SISTEM (Sertifikat, Gamifikasi, Notifikasi, Tampilan, Lainnya)
=========================== */

if (isset($_POST['simpan_pengaturan'])) {

    setSetting($conn, 'sertifikat_prefix', trim($_POST['sertifikat_prefix']) !== '' ? trim($_POST['sertifikat_prefix']) : 'BPS/SQBPS');
    setSetting($conn, 'sertifikat_nilai_lulus', (string) max(0, min(100, (int) $_POST['sertifikat_nilai_lulus'])));
    setSetting($conn, 'sertifikat_qr_aktif', isset($_POST['sertifikat_qr_aktif']) ? '1' : '0');
    setSetting($conn, 'sertifikat_verifikasi_online', isset($_POST['sertifikat_verifikasi_online']) ? '1' : '0');

    setSetting($conn, 'gamifikasi_xp_quiz', (string) max(0, (int) $_POST['gamifikasi_xp_quiz']));
    setSetting($conn, 'gamifikasi_xp_materi', (string) max(0, (int) $_POST['gamifikasi_xp_materi']));
    setSetting($conn, 'gamifikasi_xp_achievement', (string) max(0, (int) $_POST['gamifikasi_xp_achievement']));
    setSetting($conn, 'gamifikasi_level_max', (string) max(1, (int) $_POST['gamifikasi_level_max']));

    setSetting($conn, 'notif_email_sertifikat', isset($_POST['notif_email_sertifikat']) ? '1' : '0');
    setSetting($conn, 'notif_email_achievement', isset($_POST['notif_email_achievement']) ? '1' : '0');
    setSetting($conn, 'notif_email_reset_password', isset($_POST['notif_email_reset_password']) ? '1' : '0');
    setSetting($conn, 'notif_email_pengirim', trim($_POST['notif_email_pengirim']));

    $warnaUtamaBaru = $_POST['tampilan_warna_utama'];
    $warnaSekunderBaru = $_POST['tampilan_warna_sekunder'];
    setSetting($conn, 'tampilan_warna_utama', preg_match('/^#[0-9A-Fa-f]{6}$/', $warnaUtamaBaru) ? $warnaUtamaBaru : '#1565C0');
    setSetting($conn, 'tampilan_warna_sekunder', preg_match('/^#[0-9A-Fa-f]{6}$/', $warnaSekunderBaru) ? $warnaSekunderBaru : '#F9A825');

    setSetting($conn, 'maintenance_mode', isset($_POST['maintenance_mode']) ? '1' : '0');
    setSetting($conn, 'registrasi_terbuka', isset($_POST['registrasi_terbuka']) ? '1' : '0');
    setSetting($conn, 'bahasa_default', $_POST['bahasa_default'] === 'en' ? 'en' : 'id');
    setSetting($conn, 'zona_waktu', trim($_POST['zona_waktu']) !== '' ? trim($_POST['zona_waktu']) : 'Asia/Jakarta');

    $pesan = "Pengaturan sistem berhasil disimpan.";

}

$settings = getAllSettings($conn);

$activeMenu = 'pengaturan';
?>
<!DOCTYPE html>
<html lang="id">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Pengaturan Sistem | Admin StatQuest BPS</title>

<link rel="stylesheet" href="assets/admin.css?v=<?= filemtime(__DIR__ . "/assets/admin.css"); ?>">

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@800&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

</head>

<body>

<div class="admin-container">

<?php require __DIR__ . "/partials/nav.php"; ?>

<main class="admin-content">

<div class="page-watermark">
<div class="wm-inner">
<img src="../pengguna/assets/img/logo-bps.png" alt="">
<span>BPS</span>
</div>
</div>

<div class="admin-topbar">

<div>
<h1><i class="fa-solid fa-gear"></i> Pengaturan Sistem</h1>
<p class="admin-topbar-sub">Kelola konfigurasi aplikasi StatQuest BPS.</p>
</div>

<?php require __DIR__ . "/partials/profile_widget.php"; ?>

</div>

<?php if ($pesan != "") { ?>
<div class="alert alert-<?= $pesanTipe; ?>"><?= e($pesan); ?></div>
<?php } ?>

<div class="settings-grid-3">

<!-- Informasi Aplikasi -->
<div class="settings-card" style="--card-accent:#1565C0;--card-accent-soft:#E3F2FD;">

<div class="settings-card-head">
<span class="settings-card-icon blue"><i class="fa-solid fa-circle-info"></i></span>
<h3>Informasi Aplikasi</h3>
</div>

<div class="settings-info-thumb">
<img src="assets/img/login-badge-icon.png" alt="">
</div>

<div class="settings-info-row"><span>Nama Aplikasi</span><strong><?= e($settings['app_nama']); ?></strong></div>
<div class="settings-info-row"><span>Versi</span><strong><?= e($settings['app_versi']); ?></strong></div>
<div class="settings-info-row"><span>Organisasi</span><strong><?= e($settings['app_organisasi']); ?></strong></div>
<div class="settings-info-row"><span>Tahun</span><strong><?= e($settings['app_tahun']); ?></strong></div>

<div class="settings-card-footer">
<button type="button" class="btn btn-sm" id="btnUbahInformasi"><i class="fa-solid fa-pen"></i> Ubah Informasi</button>
</div>

</div>

<!-- Profil Administrator -->
<div class="settings-card" style="--card-accent:#7C4DFF;--card-accent-soft:#F1EBFF;">

<div class="settings-card-head">
<span class="settings-card-icon purple"><i class="fa-solid fa-user"></i></span>
<h3>Profil Administrator</h3>
</div>

<div class="settings-profile-row">
<?php if (!empty($admin['foto'])) { ?>
<img src="assets/img/<?= e($admin['foto']); ?>" class="settings-profile-avatar" alt="">
<?php } else { ?>
<span class="settings-profile-avatar"><i class="fa-solid fa-user-shield"></i></span>
<?php } ?>
<div>
<strong style="display:block;color:var(--text-strong);"><?= e($admin['nama']); ?></strong>
<span style="font-size:12.5px;color:var(--text-muted);">@<?= e($admin['username']); ?></span>
</div>
</div>

<div class="settings-info-row"><span>Email</span><strong><?= e($admin['email'] ?: '-'); ?></strong></div>
<div class="settings-info-row"><span>No. Telepon</span><strong><?= e($admin['no_telp'] ?: '-'); ?></strong></div>

<div class="settings-card-footer">
<button type="button" class="btn btn-sm" id="btnEditProfil"><i class="fa-solid fa-pen"></i> Edit Profil</button>
</div>

</div>

<!-- Keamanan -->
<div class="settings-card" style="--card-accent:#2E7D32;--card-accent-soft:#E8F5E9;">

<div class="settings-card-head">
<span class="settings-card-icon green"><i class="fa-solid fa-shield-halved"></i></span>
<h3>Keamanan</h3>
</div>

<form method="POST" class="admin-form">

<label>Password Baru</label>
<div class="input-group" style="position:relative;">
<input type="password" name="password_baru" id="passwordBaruInput" placeholder="Masukkan password baru">
<button type="button" class="toggle-password" data-target="passwordBaruInput" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);"><i class="fa-solid fa-eye"></i></button>
</div>

<label>Konfirmasi Password</label>
<div class="input-group" style="position:relative;">
<input type="password" name="konfirmasi_password" id="konfirmasiPasswordInput" placeholder="Konfirmasi password baru">
<button type="button" class="toggle-password" data-target="konfirmasiPasswordInput" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted);"><i class="fa-solid fa-eye"></i></button>
</div>

<div class="settings-toggle-row">
<label>Aktifkan Two-Factor Authentication (2FA) <i class="fa-solid fa-circle-info" title="Belum ditegakkan saat login - lihat catatan di bawah"></i></label>
<label class="toggle-switch">
<input type="checkbox" name="two_fa_enabled" <?= $admin['two_fa_enabled'] ? 'checked' : ''; ?>>
<span class="toggle-switch-slider"></span>
</label>
</div>

<div class="settings-note"><i class="fa-solid fa-circle-info"></i> Toggle 2FA saat ini hanya tersimpan sebagai preferensi - proses login admin belum mewajibkan kode verifikasi tambahan.</div>

<div style="margin-top:16px;">
<button type="submit" name="simpan_password" class="btn"><i class="fa-solid fa-floppy-disk"></i> Simpan Password</button>
</div>

</form>

</div>

</div>

<form method="POST">

<div class="settings-grid-3">

<!-- Pengaturan Sertifikat -->
<div class="settings-card" style="--card-accent:#6A1B9A;--card-accent-soft:#F3E5F5;">

<div class="settings-card-head">
<span class="settings-card-icon violet"><i class="fa-solid fa-certificate"></i></span>
<h3>Pengaturan Sertifikat</h3>
</div>

<label>Prefix Nomor Sertifikat</label>
<input type="text" name="sertifikat_prefix" value="<?= e($settings['sertifikat_prefix']); ?>">

<label style="margin-top:12px;">Minimal Nilai Kelulusan</label>
<input type="number" name="sertifikat_nilai_lulus" min="0" max="100" value="<?= e($settings['sertifikat_nilai_lulus']); ?>">

<div class="settings-toggle-row">
<label>Aktifkan QR Code pada Sertifikat <i class="fa-solid fa-circle-info" title="Menampilkan/menyembunyikan QR code di halaman sertifikat pemain"></i></label>
<label class="toggle-switch">
<input type="checkbox" name="sertifikat_qr_aktif" <?= $settings['sertifikat_qr_aktif'] === '1' ? 'checked' : ''; ?>>
<span class="toggle-switch-slider"></span>
</label>
</div>

<div class="settings-toggle-row">
<label>Aktifkan Verifikasi Online Sertifikat <i class="fa-solid fa-circle-info" title="Mengaktifkan/menonaktifkan halaman verifikasi publik"></i></label>
<label class="toggle-switch">
<input type="checkbox" name="sertifikat_verifikasi_online" <?= $settings['sertifikat_verifikasi_online'] === '1' ? 'checked' : ''; ?>>
<span class="toggle-switch-slider"></span>
</label>
</div>

</div>

<!-- Pengaturan Gamifikasi -->
<div class="settings-card" style="--card-accent:#EF6C00;--card-accent-soft:#FFF3E0;">

<div class="settings-card-head">
<span class="settings-card-icon orange"><i class="fa-solid fa-gamepad"></i></span>
<h3>Pengaturan Gamifikasi</h3>
</div>

<label>XP per Quiz</label>
<input type="number" name="gamifikasi_xp_quiz" min="0" value="<?= e($settings['gamifikasi_xp_quiz']); ?>">

<label style="margin-top:12px;">XP per Materi</label>
<input type="number" name="gamifikasi_xp_materi" min="0" value="<?= e($settings['gamifikasi_xp_materi']); ?>">

<label style="margin-top:12px;">XP per Achievement</label>
<input type="number" name="gamifikasi_xp_achievement" min="0" value="<?= e($settings['gamifikasi_xp_achievement']); ?>">

<label style="margin-top:12px;">Level Maksimum</label>
<input type="number" name="gamifikasi_level_max" min="1" value="<?= e($settings['gamifikasi_level_max']); ?>">

<div class="settings-note"><i class="fa-solid fa-circle-info"></i> Nilai di atas tersimpan sebagai konfigurasi, tapi belum menggantikan rumus XP/level yang berjalan saat ini (quiz memberi otomatis +10 XP per jawaban benar, level = XP/100 tanpa batas atas).</div>

</div>

<!-- Pengaturan Notifikasi -->
<div class="settings-card" style="--card-accent:#00838F;--card-accent-soft:#E0F7FA;">

<div class="settings-card-head">
<span class="settings-card-icon aqua"><i class="fa-solid fa-envelope"></i></span>
<h3>Pengaturan Notifikasi</h3>
</div>

<div class="settings-toggle-row">
<label>Kirim Email Sertifikat Otomatis <i class="fa-solid fa-circle-info" title="Berlaku untuk tombol Kirim Sertifikat di Manajemen Sertifikat"></i></label>
<label class="toggle-switch">
<input type="checkbox" name="notif_email_sertifikat" <?= $settings['notif_email_sertifikat'] === '1' ? 'checked' : ''; ?>>
<span class="toggle-switch-slider"></span>
</label>
</div>

<div class="settings-toggle-row">
<label>Kirim Notifikasi Achievement <i class="fa-solid fa-circle-info" title="Belum ada sistem pengiriman email achievement - lihat catatan di bawah"></i></label>
<label class="toggle-switch">
<input type="checkbox" name="notif_email_achievement" <?= $settings['notif_email_achievement'] === '1' ? 'checked' : ''; ?>>
<span class="toggle-switch-slider"></span>
</label>
</div>

<div class="settings-toggle-row">
<label>Kirim Email Reset Password <i class="fa-solid fa-circle-info" title="Berlaku untuk OTP lupa password pengguna"></i></label>
<label class="toggle-switch">
<input type="checkbox" name="notif_email_reset_password" <?= $settings['notif_email_reset_password'] === '1' ? 'checked' : ''; ?>>
<span class="toggle-switch-slider"></span>
</label>
</div>

<label style="margin-top:8px;">Email Pengirim</label>
<input type="email" name="notif_email_pengirim" placeholder="noreply@statquest.bps.go.id" value="<?= e($settings['notif_email_pengirim']); ?>">

<div class="settings-note"><i class="fa-solid fa-circle-info"></i> Notifikasi Achievement tersimpan sebagai preferensi saja - aplikasi belum punya pengiriman email untuk achievement. Email Pengirim mengganti alamat "From" pada email OTP & sertifikat (kosongkan untuk memakai default di smtp_config.php).</div>

</div>

</div>

<div class="settings-grid-2">

<!-- Pengaturan Tampilan -->
<div class="settings-card" style="--card-accent:#C62828;--card-accent-soft:#FFEBEE;">

<div class="settings-card-head">
<span class="settings-card-icon red"><i class="fa-solid fa-palette"></i></span>
<h3>Pengaturan Tampilan</h3>
</div>

<label>Tema Aplikasi (perangkat ini)</label>
<div class="settings-theme-options" id="themeOptions">
<label class="settings-theme-option" id="themeOptionLight">
<input type="radio" name="tema_lokal" value="light">
<div class="settings-theme-swatch light"></div>
Light Mode
</label>
<label class="settings-theme-option" id="themeOptionDark">
<input type="radio" name="tema_lokal" value="dark">
<div class="settings-theme-swatch dark"></div>
Dark Mode
</label>
</div>

<div class="settings-color-row">
<label>Warna Utama</label>
<input type="color" name="tampilan_warna_utama" id="warnaUtamaPicker" value="<?= e($settings['tampilan_warna_utama']); ?>">
<input type="text" id="warnaUtamaText" value="<?= e($settings['tampilan_warna_utama']); ?>">
</div>

<div class="settings-color-row" style="margin-top:10px;">
<label>Warna Sekunder</label>
<input type="color" name="tampilan_warna_sekunder" id="warnaSekunderPicker" value="<?= e($settings['tampilan_warna_sekunder']); ?>">
<input type="text" id="warnaSekunderText" value="<?= e($settings['tampilan_warna_sekunder']); ?>">
</div>

<div class="settings-note"><i class="fa-solid fa-circle-info"></i> Warna Utama & Warna Sekunder langsung diterapkan ke seluruh tampilan panel admin setelah disimpan. Tema Light/Dark mengikuti preferensi per-perangkat, sama seperti tombol Mode Gelap di sidebar.</div>

</div>

<!-- Pengaturan Lainnya -->
<div class="settings-card" style="--card-accent:#F9A825;--card-accent-soft:#FFF8E1;">

<div class="settings-card-head">
<span class="settings-card-icon yellow"><i class="fa-solid fa-list-check"></i></span>
<h3>Pengaturan Lainnya</h3>
</div>

<div class="settings-toggle-row">
<label>Aktifkan Maintenance Mode <i class="fa-solid fa-circle-info" title="Memblokir akses login & dashboard pengguna sementara"></i></label>
<label class="toggle-switch">
<input type="checkbox" name="maintenance_mode" <?= $settings['maintenance_mode'] === '1' ? 'checked' : ''; ?>>
<span class="toggle-switch-slider"></span>
</label>
</div>

<div class="settings-toggle-row">
<label>Izinkan Registrasi Pengguna <i class="fa-solid fa-circle-info" title="Menutup/membuka form pendaftaran akun baru"></i></label>
<label class="toggle-switch">
<input type="checkbox" name="registrasi_terbuka" <?= $settings['registrasi_terbuka'] === '1' ? 'checked' : ''; ?>>
<span class="toggle-switch-slider"></span>
</label>
</div>

<label style="margin-top:8px;">Bahasa Default</label>
<select name="bahasa_default">
<option value="id" <?= $settings['bahasa_default'] === 'id' ? 'selected' : ''; ?>>Bahasa Indonesia</option>
<option value="en" <?= $settings['bahasa_default'] === 'en' ? 'selected' : ''; ?>>English</option>
</select>

<label style="margin-top:12px;">Zona Waktu</label>
<select name="zona_waktu">
<option value="Asia/Jakarta" <?= $settings['zona_waktu'] === 'Asia/Jakarta' ? 'selected' : ''; ?>>Asia/Jakarta (WIB)</option>
<option value="Asia/Makassar" <?= $settings['zona_waktu'] === 'Asia/Makassar' ? 'selected' : ''; ?>>Asia/Makassar (WITA)</option>
<option value="Asia/Jayapura" <?= $settings['zona_waktu'] === 'Asia/Jayapura' ? 'selected' : ''; ?>>Asia/Jayapura (WIT)</option>
</select>

<div class="settings-note"><i class="fa-solid fa-circle-info"></i> Aplikasi saat ini hanya tersedia dalam Bahasa Indonesia dan mengikuti waktu server. Bahasa Default & Zona Waktu tersimpan untuk pengembangan berikutnya.</div>

</div>

</div>

<div class="settings-save-bar">
<button type="submit" name="simpan_pengaturan" class="btn"><i class="fa-solid fa-floppy-disk"></i> Simpan Pengaturan</button>
</div>

</form>

<?php require __DIR__ . "/partials/footer.php"; ?>

</main>

</div>

<!-- Modal: Ubah Informasi Aplikasi -->
<div class="user-view-overlay" id="infoEditOverlay">
<div class="user-view-modal">

<button type="button" class="user-view-close" id="infoEditClose"><i class="fa-solid fa-xmark"></i></button>

<span class="cert-modal-icon"><i class="fa-solid fa-circle-info"></i></span>

<h3>Ubah Informasi Aplikasi</h3>
<p class="user-view-username">Informasi Aplikasi</p>

<form method="POST" class="admin-form">

<label>Nama Aplikasi</label>
<input type="text" name="app_nama" value="<?= e($settings['app_nama']); ?>" required>

<label>Versi</label>
<input type="text" name="app_versi" value="<?= e($settings['app_versi']); ?>" required>

<label>Organisasi</label>
<input type="text" name="app_organisasi" value="<?= e($settings['app_organisasi']); ?>" required>

<label>Tahun</label>
<input type="text" name="app_tahun" value="<?= e($settings['app_tahun']); ?>" required>

<button type="submit" name="simpan_informasi" class="btn" style="width:100%;margin-top:14px;">Simpan Perubahan</button>

</form>

</div>
</div>

<!-- Modal: Edit Profil Administrator -->
<div class="user-view-overlay" id="profilEditOverlay">
<div class="user-view-modal">

<button type="button" class="user-view-close" id="profilEditClose"><i class="fa-solid fa-xmark"></i></button>

<span class="cert-modal-icon"><i class="fa-solid fa-user-pen"></i></span>

<h3>Edit Profil Administrator</h3>
<p class="user-view-username">Profil Administrator</p>

<form method="POST" class="admin-form" enctype="multipart/form-data">

<label>Nama Lengkap</label>
<input type="text" name="nama" value="<?= e($admin['nama']); ?>" required>

<label>Username</label>
<input type="text" name="username" value="<?= e($admin['username']); ?>" required>

<label>Email</label>
<input type="email" name="email" value="<?= e($admin['email']); ?>">

<label>No. Telepon</label>
<input type="text" name="no_telp" value="<?= e($admin['no_telp']); ?>">

<label>Foto Profil</label>
<input type="file" name="foto" accept="image/*">

<button type="submit" name="simpan_profil" class="btn" style="width:100%;margin-top:14px;">Simpan Perubahan</button>

</form>

</div>
</div>

<script>
(function(){
    var infoOverlay = document.getElementById("infoEditOverlay");
    document.getElementById("btnUbahInformasi").addEventListener("click", function(){
        infoOverlay.classList.add("show");
    });
    document.getElementById("infoEditClose").addEventListener("click", function(){
        infoOverlay.classList.remove("show");
    });
    infoOverlay.addEventListener("click", function(e){
        if (e.target === infoOverlay) infoOverlay.classList.remove("show");
    });

    var profilOverlay = document.getElementById("profilEditOverlay");
    document.getElementById("btnEditProfil").addEventListener("click", function(){
        profilOverlay.classList.add("show");
    });
    document.getElementById("profilEditClose").addEventListener("click", function(){
        profilOverlay.classList.remove("show");
    });
    profilOverlay.addEventListener("click", function(e){
        if (e.target === profilOverlay) profilOverlay.classList.remove("show");
    });

    document.querySelectorAll(".toggle-password").forEach(function(btn){
        btn.addEventListener("click", function(){
            var input = document.getElementById(btn.dataset.target);
            var isHidden = input.type === "password";
            input.type = isHidden ? "text" : "password";
            btn.innerHTML = isHidden ? '<i class="fa-solid fa-eye-slash"></i>' : '<i class="fa-solid fa-eye"></i>';
        });
    });

    var warnaUtamaPicker = document.getElementById("warnaUtamaPicker");
    var warnaUtamaText = document.getElementById("warnaUtamaText");
    warnaUtamaPicker.addEventListener("input", function(){ warnaUtamaText.value = warnaUtamaPicker.value; });
    warnaUtamaText.addEventListener("input", function(){
        if (/^#[0-9A-Fa-f]{6}$/.test(warnaUtamaText.value)) warnaUtamaPicker.value = warnaUtamaText.value;
    });

    var warnaSekunderPicker = document.getElementById("warnaSekunderPicker");
    var warnaSekunderText = document.getElementById("warnaSekunderText");
    warnaSekunderPicker.addEventListener("input", function(){ warnaSekunderText.value = warnaSekunderPicker.value; });
    warnaSekunderText.addEventListener("input", function(){
        if (/^#[0-9A-Fa-f]{6}$/.test(warnaSekunderText.value)) warnaSekunderPicker.value = warnaSekunderText.value;
    });

    var THEME_KEY = "quizbps-admin-theme";
    var optLight = document.getElementById("themeOptionLight");
    var optDark = document.getElementById("themeOptionDark");

    function syncThemeOptions(){
        var current = localStorage.getItem(THEME_KEY) || "light";
        optLight.classList.toggle("active", current === "light");
        optDark.classList.toggle("active", current === "dark");
        optLight.querySelector("input").checked = current === "light";
        optDark.querySelector("input").checked = current === "dark";
    }

    function setTheme(theme){
        localStorage.setItem(THEME_KEY, theme);
        document.documentElement.setAttribute("data-theme", theme);
        var toggle = document.getElementById("adminThemeToggle");
        if (toggle) {
            toggle.querySelector("i").className = theme === "dark" ? "fa-solid fa-sun" : "fa-solid fa-moon";
            toggle.querySelector("span").textContent = theme === "dark" ? "Mode Terang" : "Mode Gelap";
        }
        syncThemeOptions();
    }

    optLight.addEventListener("click", function(){ setTheme("light"); });
    optDark.addEventListener("click", function(){ setTheme("dark"); });

    syncThemeOptions();
})();
</script>

</body>

</html>

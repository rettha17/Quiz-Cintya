<?php
/**
 * Shared helper functions used across dashboard/kategori/progress/hasil/achievement pages.
 */

if (!function_exists('e')) {
    function e($str) {
        return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
    }
}

function levelList() {
    return ['Mudah', 'Sedang', 'Sulit'];
}

/**
 * Best score per kategori x level for a user, plus category info.
 * Returns: [kategori_id => ['nama'=>, 'icon'=>, 'deskripsi'=>, 'levels'=>['Mudah'=>int|null, ...]]]
 */
function getProgress($conn, $user_id) {
    $progress = [];

    $kategori = mysqli_query($conn, "SELECT * FROM kategori ORDER BY id");

    while ($k = mysqli_fetch_assoc($kategori)) {
        $levels = [];

        foreach (levelList() as $lvl) {
            $stmt = mysqli_prepare($conn, "
                SELECT MAX(score) AS best
                FROM hasil_quiz
                WHERE user_id=? AND kategori_id=? AND level=?
            ");
            mysqli_stmt_bind_param($stmt, "iis", $user_id, $k['id'], $lvl);
            mysqli_stmt_execute($stmt);
            $best = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['best'];
            $levels[$lvl] = $best === null ? null : (int) $best;
        }

        $progress[$k['id']] = [
            'nama' => $k['nama'],
            'icon' => $k['icon'],
            'deskripsi' => $k['deskripsi'],
            'levels' => $levels,
        ];
    }

    return $progress;
}

/**
 * Soal count per kategori x level, plus topik (materi) count per kategori
 * and grand totals across all categories. Used on the Quiz Challenge listing page.
 * Returns: [
 *   'kategori' => [kategori_id => ['soal_per_level'=>['Mudah'=>int, ...], 'total_soal'=>int, 'total_topik'=>int]],
 *   'total_kategori' => int, 'total_level' => int, 'total_soal' => int
 * ]
 */
function getSoalStats($conn) {
    $stats = [];
    $totalSoal = 0;

    $kategori = mysqli_query($conn, "SELECT id FROM kategori ORDER BY id");
    while ($k = mysqli_fetch_assoc($kategori)) {
        $katId = $k['id'];
        $perLevel = [];

        foreach (levelList() as $lvl) {
            $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM soal WHERE kategori_id=? AND level=?");
            mysqli_stmt_bind_param($stmt, "is", $katId, $lvl);
            mysqli_stmt_execute($stmt);
            $c = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
            $perLevel[$lvl] = $c;
        }

        $stmtM = mysqli_prepare($conn, "SELECT COUNT(*) c FROM materi WHERE kategori_id=?");
        mysqli_stmt_bind_param($stmtM, "i", $katId);
        mysqli_stmt_execute($stmtM);
        $totalTopik = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmtM))['c'];

        $totalKatSoal = array_sum($perLevel);
        $totalSoal += $totalKatSoal;

        $stats[$katId] = [
            'soal_per_level' => $perLevel,
            'total_soal' => $totalKatSoal,
            'total_topik' => $totalTopik,
        ];
    }

    $totalKategori = count($stats);

    return [
        'kategori' => $stats,
        'total_kategori' => $totalKategori,
        'total_level' => $totalKategori * count(levelList()),
        'total_soal' => $totalSoal,
    ];
}

/**
 * Overall completion percentage: levels with an attempt / total levels available.
 */
function getPersenSelesai($progress) {
    $total = 0;
    $selesai = 0;

    foreach ($progress as $kat) {
        foreach ($kat['levels'] as $best) {
            $total++;
            if ($best !== null) $selesai++;
        }
    }

    return $total > 0 ? round(($selesai / $total) * 100) : 0;
}

/**
 * Materials to (re)study: never attempted, or best score below threshold.
 * Returns list of ['kategori_id','nama','level','best_score'(nullable)], weakest first.
 */
function getRekomendasi($conn, $user_id, $threshold = 70, $limit = 3) {
    $progress = getProgress($conn, $user_id);
    $rekomendasi = [];

    foreach ($progress as $kategori_id => $kat) {
        foreach ($kat['levels'] as $level => $best) {
            if ($best === null || $best < $threshold) {
                $rekomendasi[] = [
                    'kategori_id' => $kategori_id,
                    'nama' => $kat['nama'],
                    'level' => $level,
                    'best_score' => $best,
                ];
            }
        }
    }

    usort($rekomendasi, function ($a, $b) {
        return ($a['best_score'] ?? -1) <=> ($b['best_score'] ?? -1);
    });

    return array_slice($rekomendasi, 0, $limit);
}

/**
 * Katalog lengkap badge berbasis XP (dipakai getBadges() & tampilan achievement terkunci/terbuka).
 */
function getBadgeCatalog() {
    return [
        ['min_xp' => 100, 'icon' => '🥉', 'nama' => 'Pemula', 'deskripsi' => 'Mendapatkan 100 XP', 'warna' => 'linear-gradient(150deg,#E8B27D,#B4692E)'],
        ['min_xp' => 300, 'icon' => '🥈', 'nama' => 'Pejuang Statistik', 'deskripsi' => 'Mendapatkan 300 XP', 'warna' => 'linear-gradient(150deg,#E7ECF2,#9AA7B4)'],
        ['min_xp' => 500, 'icon' => '🥇', 'nama' => 'Ahli Statistik', 'deskripsi' => 'Mendapatkan 500 XP', 'warna' => 'linear-gradient(150deg,#FFE9B8,#F5A623)'],
        ['min_xp' => 1000, 'icon' => '🏆', 'nama' => 'Master Statistik', 'deskripsi' => 'Mendapatkan 1000 XP', 'warna' => 'linear-gradient(150deg,#8FD6FF,#1565C0)'],
    ];
}

/**
 * Badges earned so far based on total XP.
 */
function getBadges($xp) {
    $hasil = [];
    foreach (getBadgeCatalog() as $b) {
        if ($xp >= $b['min_xp']) $hasil[] = $b;
    }

    return $hasil;
}

function levelValid($level) {
    return in_array($level, levelList(), true) ? $level : 'Mudah';
}

/**
 * Durasi waktu menjawab (detik) per level kesulitan.
 */
function levelDurasiWaktu($level) {
    $durasi = ['Mudah' => 15, 'Sedang' => 20, 'Sulit' => 30];
    return $durasi[levelValid($level)];
}

/**
 * Posisi lanjutan quiz yang sedang berjalan (belum selesai & nyawa masih ada)
 * untuk kategori+level tertentu. Null jika tidak ada progress yang bisa dilanjutkan.
 */
function quizResumeNomor($kategoriId, $level) {
    if (empty($_SESSION['soal'])
        || ($_SESSION['soal_kategori'] ?? null) != $kategoriId
        || ($_SESSION['soal_level'] ?? null) != $level
        || empty($_SESSION['posisi_terakhir'])
        || ($_SESSION['nyawa'] ?? 0) <= 0) {
        return null;
    }

    $posisi = (int) $_SESSION['posisi_terakhir'];
    if ($posisi <= 0 || $posisi >= count($_SESSION['soal'])) {
        return null;
    }

    return $posisi;
}

/**
 * Catat satu peristiwa ke activity_log (dipakai untuk notifikasi & aktivitas terbaru admin).
 */
function logAktivitas($conn, $tipe, $judul, $icon = 'fa-circle-info', $warna = 'blue', $userId = null) {
    $stmt = mysqli_prepare($conn, "
        INSERT INTO activity_log (tipe, judul, icon, warna, user_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "ssssi", $tipe, $judul, $icon, $warna, $userId);
    mysqli_stmt_execute($stmt);
}

/**
 * Bandingkan badge sebelum & sesudah perubahan XP, catat tiap badge baru yang didapat.
 */
function cekBadgeBaru($conn, $xpLama, $xpBaru, $userId, $nama) {
    $lama = array_column(getBadges($xpLama), 'nama');
    foreach (getBadges($xpBaru) as $b) {
        if (!in_array($b['nama'], $lama, true)) {
            logAktivitas(
                $conn,
                'badge_earned',
                e($nama) . ' memperoleh Achievement "' . e($b['nama']) . '".',
                'fa-award',
                'purple',
                $userId
            );
        }
    }
}

/**
 * Format timestamp MySQL jadi teks relatif berbahasa Indonesia ("5 menit lalu").
 */
function waktuRelatif($timestamp) {
    $detik = time() - strtotime($timestamp);

    if ($detik < 60) return 'Baru saja';
    if ($detik < 3600) return floor($detik / 60) . ' menit lalu';
    if ($detik < 86400) return floor($detik / 3600) . ' jam lalu';
    if ($detik < 2592000) return floor($detik / 86400) . ' hari lalu';

    return date('d M Y', strtotime($timestamp));
}

/**
 * N aktivitas terbaru untuk dropdown notifikasi & panel "Aktivitas Terbaru" admin.
 */
function getRecentActivity($conn, $limit = 8) {
    $limit = (int) $limit;
    $result = mysqli_query($conn, "
        SELECT tipe, judul, icon, warna, dibuat_pada
        FROM activity_log
        ORDER BY dibuat_pada DESC
        LIMIT $limit
    ");

    $hasil = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['waktu_relatif'] = waktuRelatif($row['dibuat_pada']);
        $hasil[] = $row;
    }

    return $hasil;
}

/**
 * Ringkasan performa 7 hari terakhir untuk panel "Performa Minggu Ini" admin.
 */
function getWeeklyPerformance($conn) {
    $soalBaru = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) c FROM activity_log
        WHERE tipe = 'soal_baru' AND dibuat_pada >= NOW() - INTERVAL 7 DAY
    "))['c'];

    $penggunaBaru = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT COUNT(*) c FROM users WHERE created_at >= NOW() - INTERVAL 7 DAY
    "))['c'];

    $akurasi = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT AVG(score) a FROM hasil_quiz WHERE tanggal >= NOW() - INTERVAL 7 DAY
    "))['a'];

    $streak = getLongestStreak($conn);

    return [
        'quiz_dibuat' => (int) $soalBaru,
        'pengguna_baru' => (int) $penggunaBaru,
        'rata_akurasi' => $akurasi === null ? 0 : (int) round($akurasi),
        'streak_terpanjang' => $streak,
    ];
}

/**
 * Streak harian beruntun terpanjang (hari berturut-turut ada aktivitas quiz/materi)
 * di antara semua pengguna, dihitung dari tanggal aktivitas nyata (bukan kolom tersimpan).
 */
function getLongestStreak($conn) {
    $tanggalPerUser = [];

    $r1 = mysqli_query($conn, "SELECT DISTINCT user_id, DATE(tanggal) d FROM hasil_quiz");
    while ($row = mysqli_fetch_assoc($r1)) {
        $tanggalPerUser[$row['user_id']][$row['d']] = true;
    }

    $r2 = mysqli_query($conn, "SELECT DISTINCT user_id, DATE(selesai_pada) d FROM materi_progress WHERE selesai_pada IS NOT NULL");
    while ($row = mysqli_fetch_assoc($r2)) {
        $tanggalPerUser[$row['user_id']][$row['d']] = true;
    }

    $terpanjang = 0;

    foreach ($tanggalPerUser as $tanggalList) {
        $tanggal = array_keys($tanggalList);
        sort($tanggal);

        $lari = 1;
        $maxLari = count($tanggal) > 0 ? 1 : 0;

        for ($i = 1; $i < count($tanggal); $i++) {
            $selisih = (strtotime($tanggal[$i]) - strtotime($tanggal[$i - 1])) / 86400;
            $lari = $selisih == 1 ? $lari + 1 : 1;
            $maxLari = max($maxLari, $lari);
        }

        $terpanjang = max($terpanjang, $maxLari);
    }

    return $terpanjang;
}

/**
 * Streak harian beruntun untuk satu pengguna, dihitung dari tanggal aktivitas nyata
 * (hasil_quiz.tanggal + materi_progress.selesai_pada). Berhenti (0) jika hari terakhir
 * aktif bukan hari ini atau kemarin.
 */
function getUserStreak($conn, $user_id) {
    $tanggalSet = [];

    $stmt = mysqli_prepare($conn, "SELECT DISTINCT DATE(tanggal) d FROM hasil_quiz WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $tanggalSet[$row['d']] = true;
    }

    $stmt = mysqli_prepare($conn, "SELECT DISTINCT DATE(selesai_pada) d FROM materi_progress WHERE user_id=? AND selesai_pada IS NOT NULL");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) {
        $tanggalSet[$row['d']] = true;
    }

    if (empty($tanggalSet)) return 0;

    $hariTerakhir = max(array_keys($tanggalSet));
    $selisihHariIni = (strtotime(date('Y-m-d')) - strtotime($hariTerakhir)) / 86400;
    if ($selisihHariIni > 1) return 0;

    $streak = 1;
    $cursor = strtotime($hariTerakhir);
    while (isset($tanggalSet[date('Y-m-d', $cursor - 86400)])) {
        $streak++;
        $cursor -= 86400;
    }

    return $streak;
}

/**
 * Varian tema quiz khusus per kategori+level:
 * Statistika Dasar - Sedang -> tema kebun bunga
 * Statistika Dasar - Sulit -> tema bajak laut
 * Pengenalan BPS - Mudah -> tema lebah mengumpulkan nektar
 * Pengenalan BPS - Sedang -> tema panda memanen bambu
 * Pengenalan BPS - Sulit -> tema katak menangkap serangga
 * Indikator Statistik - Mudah -> tema penguin menangkap ikan
 * Indikator Statistik - Sedang -> tema astronot mengumpulkan bintang
 * Indikator Statistik - Sulit -> tema ksatria melawan monster
 * Kombinasi lain tetap pakai tema pemadam kebakaran (default).
 * Return: 'kebun' | 'bajak-laut' | 'lebah' | 'panda' | 'katak' | 'penguin' | 'astronot' | 'ksatria' | '' (default)
 */
function temaQuizAktif($kategoriNama, $level) {
    $nama = mb_strtolower(trim((string) $kategoriNama));

    if ($nama === 'statistika dasar') {
        if ($level === 'Sedang') {
            return 'kebun';
        }
        if ($level === 'Sulit') {
            return 'bajak-laut';
        }
    }

    if ($nama === 'pengenalan bps') {
        if ($level === 'Mudah') {
            return 'lebah';
        }
        if ($level === 'Sedang') {
            return 'panda';
        }
        if ($level === 'Sulit') {
            return 'katak';
        }
    }

    if ($nama === 'indikator statistik') {
        if ($level === 'Mudah') {
            return 'penguin';
        }
        if ($level === 'Sedang') {
            return 'astronot';
        }
        if ($level === 'Sulit') {
            return 'ksatria';
        }
    }

    return '';
}

/**
 * Materi grouped by kategori, each topik annotated with the logged-in user's
 * reading status ('belum' | 'proses' | 'selesai'), plus per-kategori totals
 * (topik count, durasi, xp tersedia, xp earned, progress %).
 * Returns: [kategori_id => ['materi' => [...], 'total_topik'=>, 'total_durasi'=>,
 *           'total_xp'=>, 'xp_earned'=>, 'progress_percent'=>]]
 */
function getMateriDenganProgress($conn, $user_id) {
    $hasil = [];

    $rows = mysqli_query($conn, "
        SELECT m.*, mp.status AS status_baca
        FROM materi m
        LEFT JOIN materi_progress mp ON mp.materi_id = m.id AND mp.user_id = $user_id
        ORDER BY m.kategori_id, m.urutan, m.id
    ");

    while ($m = mysqli_fetch_assoc($rows)) {
        $katId = $m['kategori_id'];

        if (!isset($hasil[$katId])) {
            $hasil[$katId] = [
                'materi' => [],
                'total_topik' => 0,
                'total_durasi' => 0,
                'total_xp' => 0,
                'xp_earned' => 0,
            ];
        }

        $status = $m['status_baca'] ?? 'belum';

        $hasil[$katId]['materi'][] = [
            'id' => $m['id'],
            'judul' => $m['judul'],
            'isi' => $m['isi'],
            'durasi_menit' => (int) $m['durasi_menit'],
            'xp' => (int) $m['xp'],
            'status' => $status,
        ];

        $hasil[$katId]['total_topik']++;
        $hasil[$katId]['total_durasi'] += (int) $m['durasi_menit'];
        $hasil[$katId]['total_xp'] += (int) $m['xp'];

        if ($status === 'selesai') {
            $hasil[$katId]['xp_earned'] += (int) $m['xp'];
        }
    }

    foreach ($hasil as &$kat) {
        $selesai = 0;
        foreach ($kat['materi'] as $m) {
            if ($m['status'] === 'selesai') $selesai++;
        }
        $kat['progress_percent'] = $kat['total_topik'] > 0
            ? round(($selesai / $kat['total_topik']) * 100)
            : 0;
    }
    unset($kat);

    return $hasil;
}

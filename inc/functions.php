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
 * and grand totals across all categories. Used on the StatQuest BPS listing page.
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
 * Kunci checkpoint quiz per kombinasi kategori+level, supaya tiap kategori & level
 * punya progress lanjutannya sendiri-sendiri (tidak saling menimpa satu sama lain).
 */
function quizProgressKey($kategoriId, $level) {
    return ((int) $kategoriId) . '_' . levelValid($level);
}

/**
 * Posisi lanjutan quiz yang sedang berjalan (belum selesai & nyawa masih ada)
 * untuk kategori+level tertentu. Null jika tidak ada progress yang bisa dilanjutkan.
 */
function quizResumeNomor($kategoriId, $level) {
    $key = quizProgressKey($kategoriId, $level);
    $cp = $_SESSION['quiz_checkpoints'][$key] ?? null;

    if (empty($cp['soal'])
        || empty($cp['posisi_terakhir'])
        || ($cp['nyawa'] ?? 0) <= 0) {
        return null;
    }

    $posisi = (int) $cp['posisi_terakhir'];
    if ($posisi <= 0 || $posisi >= count($cp['soal'])) {
        return null;
    }

    return $posisi;
}

/**
 * Semua checkpoint quiz (kategori+level) milik user yang masih bisa dilanjutkan,
 * dipakai di kategori.php supaya tombol "Lanjutkan" bisa muncul di lebih dari satu
 * kategori/level sekaligus, bukan cuma yang terakhir dimainkan.
 */
function quizActiveCheckpoints() {
    $hasil = [];

    foreach ($_SESSION['quiz_checkpoints'] ?? [] as $key => $cp) {
        $parts = explode('_', $key, 2);
        if (count($parts) !== 2) {
            continue;
        }

        [$kategoriId, $level] = $parts;
        $nomor = quizResumeNomor((int) $kategoriId, $level);

        if ($nomor !== null) {
            $hasil[] = ['kategori' => (int) $kategoriId, 'level' => $level, 'nomor' => $nomor];
        }
    }

    return $hasil;
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

/**
 * Format detik jadi teks singkat ("18 detik", "1m 5s"). Null/kosong -> "-".
 */
function formatDurasiSingkat($detik) {
    if ($detik === null || $detik === '') {
        return '-';
    }
    $detik = (int) round((float) $detik);
    if ($detik < 60) {
        return $detik . ' detik';
    }
    $menit = intdiv($detik, 60);
    $sisaDetik = $detik % 60;
    return $sisaDetik > 0 ? "{$menit}m {$sisaDetik}s" : "{$menit} menit";
}

/* ===========================
   LEADERBOARD
=========================== */

function leaderboardPeriodeValid($periode) {
    return in_array($periode, ['hari_ini', 'minggu_ini', 'bulan_ini', 'sepanjang'], true) ? $periode : 'sepanjang';
}

function leaderboardPeriodeCondition($periode) {
    switch ($periode) {
        case 'hari_ini':
            return "AND DATE(h.tanggal) = CURDATE()";
        case 'minggu_ini':
            return "AND YEARWEEK(h.tanggal, 1) = YEARWEEK(CURDATE(), 1)";
        case 'bulan_ini':
            return "AND YEAR(h.tanggal) = YEAR(CURDATE()) AND MONTH(h.tanggal) = MONTH(CURDATE())";
        default:
            return "";
    }
}

/**
 * Ranking pemain (agregat lintas hasil_quiz) berdasarkan filter kategori & periode.
 * Diurutkan dari total XP tertinggi. $kategoriId = 0 berarti semua kategori.
 */
function getLeaderboardRanking($conn, $kategoriId, $periode, $limit = 10) {
    $periodeSql = leaderboardPeriodeCondition($periode);
    $kategoriSql = $kategoriId > 0 ? "AND h.kategori_id = ?" : "";

    $sql = "
        SELECT u.id, u.nama, u.foto, u.level,
            COALESCE(SUM(h.xp),0) AS total_xp,
            COALESCE(SUM(h.jumlah_benar),0) AS total_benar,
            COALESCE(SUM(h.jumlah_salah),0) AS total_salah,
            COUNT(h.id) AS jumlah_quiz,
            AVG(h.durasi_detik) AS rata_durasi
        FROM users u
        JOIN hasil_quiz h ON h.user_id = u.id
        WHERE 1=1 $kategoriSql $periodeSql
        GROUP BY u.id, u.nama, u.foto, u.level
        ORDER BY total_xp DESC, total_benar DESC
        LIMIT ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if ($kategoriId > 0) {
        mysqli_stmt_bind_param($stmt, "ii", $kategoriId, $limit);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $limit);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $benar = (int) $row['total_benar'];
        $salah = (int) $row['total_salah'];
        $row['akurasi'] = ($benar + $salah) > 0 ? round($benar / ($benar + $salah) * 100, 1) : 0;
        $row['streak'] = getUserStreak($conn, $row['id']);
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Statistik agregat satu pengguna (filter kategori & periode yang sama dengan tabel utama).
 */
function getUserLeaderboardStats($conn, $userId, $kategoriId, $periode) {
    $periodeSql = leaderboardPeriodeCondition($periode);
    $kategoriSql = $kategoriId > 0 ? "AND h.kategori_id = ?" : "";

    $sql = "
        SELECT
            COALESCE(SUM(h.xp),0) AS total_xp,
            COALESCE(SUM(h.jumlah_benar),0) AS total_benar,
            COALESCE(SUM(h.jumlah_salah),0) AS total_salah,
            COUNT(h.id) AS jumlah_quiz,
            AVG(h.durasi_detik) AS rata_durasi
        FROM hasil_quiz h
        WHERE h.user_id = ? $kategoriSql $periodeSql
    ";

    $stmt = mysqli_prepare($conn, $sql);
    if ($kategoriId > 0) {
        mysqli_stmt_bind_param($stmt, "ii", $userId, $kategoriId);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $userId);
    }
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    $benar = (int) $row['total_benar'];
    $salah = (int) $row['total_salah'];
    $row['akurasi'] = ($benar + $salah) > 0 ? round($benar / ($benar + $salah) * 100, 1) : 0;
    $row['streak_aktif'] = getUserStreak($conn, $userId);
    $row['streak_terpanjang'] = getUserLongestStreak($conn, $userId);

    return $row;
}

/**
 * Peringkat satu pengguna di antara semua pemain yang match filter, tanpa perlu
 * menghitung streak/ranking penuh untuk semua pemain (hanya hitung XP & posisi).
 */
function getUserRank($conn, $userId, $kategoriId, $periode) {
    $periodeSql = leaderboardPeriodeCondition($periode);
    $kategoriSql = $kategoriId > 0 ? "AND h.kategori_id = ?" : "";

    $sqlTotal = "SELECT COUNT(DISTINCT h.user_id) c FROM hasil_quiz h WHERE 1=1 $kategoriSql $periodeSql";
    $stmt = mysqli_prepare($conn, $sqlTotal);
    if ($kategoriId > 0) {
        mysqli_stmt_bind_param($stmt, "i", $kategoriId);
    }
    mysqli_stmt_execute($stmt);
    $total = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

    $sqlMine = "SELECT COALESCE(SUM(h.xp),0) x, COUNT(*) c FROM hasil_quiz h WHERE h.user_id=? $kategoriSql $periodeSql";
    $stmt = mysqli_prepare($conn, $sqlMine);
    if ($kategoriId > 0) {
        mysqli_stmt_bind_param($stmt, "ii", $userId, $kategoriId);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $userId);
    }
    mysqli_stmt_execute($stmt);
    $mine = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $myXp = (int) $mine['x'];

    if ((int) $mine['c'] === 0) {
        return ['rank' => 0, 'total' => $total];
    }

    $sqlHigher = "
        SELECT COUNT(*) c FROM (
            SELECT h.user_id, SUM(h.xp) total_xp
            FROM hasil_quiz h
            WHERE 1=1 $kategoriSql $periodeSql
            GROUP BY h.user_id
            HAVING total_xp > ?
        ) t
    ";
    $stmt = mysqli_prepare($conn, $sqlHigher);
    if ($kategoriId > 0) {
        mysqli_stmt_bind_param($stmt, "ii", $kategoriId, $myXp);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $myXp);
    }
    mysqli_stmt_execute($stmt);
    $higherCount = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];

    return ['rank' => $higherCount + 1, 'total' => $total];
}

/**
 * 3 kategori dengan XP terbanyak untuk satu pengguna (sepanjang waktu),
 * beserta rata-rata skor (dipakai sebagai persentase penguasaan).
 */
function getUserTopKategori($conn, $userId, $limit = 3) {
    $stmt = mysqli_prepare($conn, "
        SELECT k.nama, k.icon, SUM(h.xp) AS xp_kategori, AVG(h.score) AS rata_skor
        FROM hasil_quiz h
        JOIN kategori k ON k.id = h.kategori_id
        WHERE h.user_id = ?
        GROUP BY k.id, k.nama, k.icon
        ORDER BY xp_kategori DESC
        LIMIT ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);

    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $row['rata_skor'] = round((float) $row['rata_skor']);
        $rows[] = $row;
    }
    return $rows;
}

/**
 * Streak harian beruntun TERPANJANG SEPANJANG SEJARAH untuk satu pengguna
 * (beda dengan getUserStreak() yang hanya streak yang sedang aktif sekarang).
 */
function getUserLongestStreak($conn, $user_id) {
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

    if (empty($tanggalSet)) {
        return 0;
    }

    $tanggal = array_keys($tanggalSet);
    sort($tanggal);

    $lari = 1;
    $maxLari = 1;

    for ($i = 1; $i < count($tanggal); $i++) {
        $selisih = (strtotime($tanggal[$i]) - strtotime($tanggal[$i - 1])) / 86400;
        $lari = $selisih == 1 ? $lari + 1 : 1;
        $maxLari = max($maxLari, $lari);
    }

    return $maxLari;
}

/* ==========================================
   SERTIFIKAT (nomor unik, token verifikasi, QR)
========================================== */

/**
 * Nomor sertifikat berikutnya untuk tahun berjalan, format BPS/SQBPS/YYYY/000001.
 * Urutan per tahun, tidak pernah dipakai ulang (angka diambil dari MAX yang sudah ada).
 */
function generateCertificateNumber($conn, $tahun) {
    $prefixSetting = function_exists('getSetting') ? getSetting($conn, 'sertifikat_prefix', 'BPS/SQBPS') : 'BPS/SQBPS';
    $prefix = "{$prefixSetting}/{$tahun}/";
    $like = mysqli_real_escape_string($conn, $prefix) . '%';

    $r = mysqli_query($conn, "
        SELECT certificate_number
        FROM sertifikat
        WHERE certificate_number LIKE '{$like}'
        ORDER BY certificate_number DESC
        LIMIT 1
    ");

    $urutan = 1;
    if ($row = mysqli_fetch_assoc($r)) {
        $lastUrutan = (int) substr($row['certificate_number'], -6);
        $urutan = $lastUrutan + 1;
    }

    return $prefix . str_pad((string) $urutan, 6, '0', STR_PAD_LEFT);
}

/**
 * Ambil sertifikat user jika sudah pernah diterbitkan, atau terbitkan baru
 * (nomor + token verifikasi unik, sekali per user, tidak berubah lagi setelahnya).
 */
function getOrCreateSertifikat($conn, $user_id, $score, $mastery) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM sertifikat WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if ($existing) {
        return $existing;
    }

    $tahun = date("Y");
    $issueDate = date("Y-m-d");

    for ($percobaan = 0; $percobaan < 3; $percobaan++) {
        $nomor = generateCertificateNumber($conn, $tahun);
        $token = bin2hex(random_bytes(16));

        $stmt = mysqli_prepare($conn, "
            INSERT INTO sertifikat (user_id, certificate_number, verification_token, score, mastery, issue_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "issiis", $user_id, $nomor, $token, $score, $mastery, $issueDate);

        if (mysqli_stmt_execute($stmt)) {
            break;
        }

        // Nomor/token bentrok (kondisi jarang, race condition) -> coba lagi dengan nilai baru.
        if (mysqli_errno($conn) !== 1062) {
            break;
        }
    }

    $stmt = mysqli_prepare($conn, "SELECT * FROM sertifikat WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

/**
 * Ambil data sertifikat + pemiliknya lewat token verifikasi (untuk halaman publik /v/{token}).
 */
function getSertifikatByToken($conn, $token) {
    $stmt = mysqli_prepare($conn, "
        SELECT s.*, u.nama, u.level, u.foto
        FROM sertifikat s
        JOIN users u ON u.id = s.user_id
        WHERE s.verification_token = ?
    ");
    mysqli_stmt_bind_param($stmt, "s", $token);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

/**
 * URL publik verifikasi sertifikat (dipanggil dari halaman di dalam folder pengguna/).
 * Mengikuti host & sub-path aplikasi saat ini, tidak hardcode domain.
 */
function getVerificationUrl($token) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $root = rtrim(str_replace('\\', '/', dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . $root . '/v/' . $token;
}

/**
 * QR code sebagai inline SVG, dibuat sepenuhnya di server (tidak butuh koneksi
 * internet/CDN saat halaman dibuka) - lebih andal untuk sertifikat resmi.
 */
function renderQrSvg($text, $moduleSize = 4, $fg = '#0D47A1', $quietModules = 4) {
    require_once __DIR__ . "/lib/qrcode.php";

    $qr = QRCode::getMinimumQRCode($text, QR_ERROR_CORRECT_LEVEL_M);
    $count = $qr->getModuleCount();
    $size = ($count + $quietModules * 2) * $moduleSize;
    $offset = $quietModules * $moduleSize;

    $svg = '<svg viewBox="0 0 ' . $size . ' ' . $size . '" width="100%" height="100%" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="QR verifikasi sertifikat">';
    $svg .= '<rect x="0" y="0" width="' . $size . '" height="' . $size . '" fill="#FFFFFF"/>';

    for ($r = 0; $r < $count; $r++) {
        for ($c = 0; $c < $count; $c++) {
            if ($qr->isDark($r, $c)) {
                $svg .= '<rect x="' . ($offset + $c * $moduleSize) . '" y="' . ($offset + $r * $moduleSize) . '" width="' . $moduleSize . '" height="' . $moduleSize . '" fill="' . $fg . '" shape-rendering="crispEdges"/>';
            }
        }
    }

    $svg .= '</svg>';
    return $svg;
}

/* ==========================================
   LUPA PASSWORD (reset via OTP email)
========================================== */

/**
 * Buat kode OTP 6 digit baru untuk user (OTP lama yang belum dipakai
 * otomatis dihapus). Berlaku 5 menit.
 */
function createPasswordResetOtp($conn, $userId, $email) {
    $stmt = mysqli_prepare($conn, "DELETE FROM password_reset_otp WHERE user_id=? AND is_used=0");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);

    $otp = str_pad((string) random_int(0, 999999), 6, "0", STR_PAD_LEFT);
    $expiredAt = date("Y-m-d H:i:s", time() + 300);

    $stmt = mysqli_prepare($conn, "INSERT INTO password_reset_otp (user_id, email, otp, expired_at) VALUES (?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "isss", $userId, $email, $otp, $expiredAt);
    mysqli_stmt_execute($stmt);

    return $otp;
}

/**
 * Ambil baris OTP jika kode masih valid (cocok, belum dipakai, belum kedaluwarsa).
 */
function getValidPasswordResetOtp($conn, $userId, $otp) {
    $stmt = mysqli_prepare($conn, "
        SELECT * FROM password_reset_otp
        WHERE user_id=? AND otp=? AND is_used=0 AND expired_at > NOW()
        ORDER BY id DESC LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, "is", $userId, $otp);
    mysqli_stmt_execute($stmt);
    return mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

function markPasswordResetOtpUsed($conn, $id) {
    $stmt = mysqli_prepare($conn, "UPDATE password_reset_otp SET is_used=1 WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
}

function deletePasswordResetOtps($conn, $userId) {
    $stmt = mysqli_prepare($conn, "DELETE FROM password_reset_otp WHERE user_id=?");
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
}

/**
 * Kirim email kode OTP lewat SMTP (kredensial di smtp_config.php).
 * Return true kalau terkirim, false kalau SMTP belum dikonfigurasi atau gagal kirim.
 */
function sendOtpEmail($toEmail, $toName, $otp) {
    global $conn;

    if (isset($conn) && getSetting($conn, 'notif_email_reset_password', '1') !== '1') {
        return false;
    }

    require_once __DIR__ . "/../smtp_config.php";

    if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
        return false;
    }

    require_once __DIR__ . "/lib/PHPMailer/Exception.php";
    require_once __DIR__ . "/lib/PHPMailer/PHPMailer.php";
    require_once __DIR__ . "/lib/PHPMailer/SMTP.php";

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        $fromEmailOtp = (isset($conn) ? getSetting($conn, 'notif_email_pengirim', '') : '') ?: SMTP_FROM_EMAIL;
        $mail->setFrom($fromEmailOtp, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Kode OTP Reset Password - StatQuest BPS';
        $mail->Body = '
            <div style="font-family:Poppins,Arial,sans-serif;max-width:480px;margin:auto;background:#F4F8FF;padding:32px 24px;border-radius:16px;">
            <div style="background:linear-gradient(135deg,#005BAC,#1D7CFF);border-radius:14px;padding:22px 24px;text-align:center;margin-bottom:20px;">
            <span style="color:#fff;font-weight:800;font-size:18px;letter-spacing:.5px;">StatQuest BPS</span>
            </div>
            <h2 style="color:#0D2E5C;margin-bottom:6px;">Kode OTP Reset Password</h2>
            <p style="color:#455A64;">Halo ' . htmlspecialchars($toName) . ',</p>
            <p style="color:#455A64;">Gunakan kode berikut untuk mereset password akunmu:</p>
            <div style="background:#fff;border:2px dashed #1565C0;border-radius:12px;padding:18px;text-align:center;margin:20px 0;">
            <span style="font-size:34px;font-weight:800;letter-spacing:10px;color:#005BAC;">' . htmlspecialchars($otp) . '</span>
            </div>
            <p style="color:#78909C;font-size:13px;">Kode ini berlaku selama <b>5 menit</b>. Jangan bagikan kode ini kepada siapa pun.</p>
            <p style="color:#90A4AE;font-size:12px;margin-top:20px;">Jika kamu tidak meminta reset password, abaikan email ini - password kamu tidak akan berubah.</p>
            </div>
        ';
        $mail->AltBody = "Kode OTP reset password StatQuest BPS kamu: {$otp} (berlaku 5 menit). Abaikan email ini jika kamu tidak meminta reset password.";

        $mail->send();
        return true;

    } catch (\Exception $e) {
        error_log("Gagal kirim email OTP: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Kirim ulang sertifikat ke email peserta (link untuk melihat & memverifikasi),
 * lewat SMTP yang sama dengan sendOtpEmail(). Return true kalau terkirim.
 */
function sendSertifikatEmail($toEmail, $toName, $certNumber, $verifyUrl) {
    global $conn;

    if (isset($conn) && getSetting($conn, 'notif_email_sertifikat', '1') !== '1') {
        return false;
    }

    require_once __DIR__ . "/../smtp_config.php";

    if (SMTP_HOST === '' || SMTP_USERNAME === '' || SMTP_PASSWORD === '') {
        return false;
    }

    require_once __DIR__ . "/lib/PHPMailer/Exception.php";
    require_once __DIR__ . "/lib/PHPMailer/PHPMailer.php";
    require_once __DIR__ . "/lib/PHPMailer/SMTP.php";

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE === 'ssl'
            ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS
            : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';

        $fromEmailCert = (isset($conn) ? getSetting($conn, 'notif_email_pengirim', '') : '') ?: SMTP_FROM_EMAIL;
        $mail->setFrom($fromEmailCert, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'Sertifikat StatQuest BPS - ' . $certNumber;
        $mail->Body = '
            <div style="font-family:Poppins,Arial,sans-serif;max-width:480px;margin:auto;background:#F4F8FF;padding:32px 24px;border-radius:16px;">
            <div style="background:linear-gradient(135deg,#005BAC,#1D7CFF);border-radius:14px;padding:22px 24px;text-align:center;margin-bottom:20px;">
            <span style="color:#fff;font-weight:800;font-size:18px;letter-spacing:.5px;">StatQuest BPS</span>
            </div>
            <h2 style="color:#0D2E5C;margin-bottom:6px;">Sertifikat Kamu Sudah Terbit</h2>
            <p style="color:#455A64;">Halo ' . htmlspecialchars($toName) . ',</p>
            <p style="color:#455A64;">Sertifikat penyelesaian StatQuest BPS kamu dengan nomor:</p>
            <div style="background:#fff;border:2px dashed #1565C0;border-radius:12px;padding:14px;text-align:center;margin:16px 0;">
            <span style="font-size:16px;font-weight:800;letter-spacing:1px;color:#005BAC;">' . htmlspecialchars($certNumber) . '</span>
            </div>
            <p style="color:#455A64;">Buka tautan berikut untuk melihat &amp; memverifikasi sertifikatmu:</p>
            <p style="text-align:center;margin:20px 0;">
            <a href="' . htmlspecialchars($verifyUrl) . '" style="display:inline-block;background:#1565C0;color:#fff;text-decoration:none;padding:12px 22px;border-radius:10px;font-weight:700;">Lihat Sertifikat</a>
            </p>
            <p style="color:#90A4AE;font-size:12px;margin-top:20px;">Simpan tautan ini baik-baik sebagai bukti keaslian sertifikatmu.</p>
            </div>
        ';
        $mail->AltBody = "Sertifikat StatQuest BPS kamu ({$certNumber}) sudah terbit. Lihat & verifikasi di: {$verifyUrl}";

        $mail->send();
        return true;

    } catch (\Exception $e) {
        error_log("Gagal kirim email sertifikat: " . $mail->ErrorInfo);
        return false;
    }
}

if (!function_exists('labelPenguasaanMateri')) {
    /**
     * Label penguasaan materi berdasarkan persentase materi yang sudah selesai.
     */
    function labelPenguasaanMateri($persen) {
        if ($persen >= 90) return 'Sangat Baik';
        if ($persen >= 75) return 'Baik';
        if ($persen >= 50) return 'Cukup';
        return 'Perlu Ditingkatkan';
    }
}

if (!function_exists('materiSelesaiCount')) {
    /**
     * Jumlah materi berstatus selesai milik satu pemain.
     */
    function materiSelesaiCount($conn, $userId) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM materi_progress WHERE user_id=? AND status='selesai'");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        return (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    }
}

if (!function_exists('sertifikatCount')) {
    /**
     * Jumlah sertifikat yang sudah diterbitkan untuk satu pemain.
     */
    function sertifikatCount($conn, $userId) {
        $stmt = mysqli_prepare($conn, "SELECT COUNT(*) c FROM sertifikat WHERE user_id=?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        return (int) mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['c'];
    }
}

if (!function_exists('riwayatKuisTerakhir')) {
    /**
     * Beberapa riwayat hasil kuis terakhir milik satu pemain (untuk modal Lihat Detail).
     */
    function riwayatKuisTerakhir($conn, $userId, $limit = 5) {
        $stmt = mysqli_prepare($conn, "
            SELECT k.nama AS kategori, h.level, h.score, h.tanggal
            FROM hasil_quiz h
            JOIN kategori k ON k.id = h.kategori_id
            WHERE h.user_id = ?
            ORDER BY h.tanggal DESC
            LIMIT ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $userId, $limit);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        $rows = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $rows[] = [
                'kategori' => $row['kategori'],
                'level' => $row['level'],
                'score' => (int) $row['score'],
                'tanggal' => date('d M Y', strtotime($row['tanggal'])),
            ];
        }
        return $rows;
    }
}

if (!function_exists('levelBadge')) {
    /**
     * Warna & label bintang level pemain, dikelompokkan per rentang level.
     */
    function levelBadge($level) {
        $level = (int) $level;
        if ($level >= 10) return ['color' => '#EDA100', 'bg' => '#FFF6E0'];
        if ($level >= 7) return ['color' => '#7C4DFF', 'bg' => '#F1EBFF'];
        if ($level >= 4) return ['color' => '#1565C0', 'bg' => '#E3F2FD'];
        return ['color' => '#2E7D32', 'bg' => '#E8F5E9'];
    }
}

if (!function_exists('renderSparkline')) {
    /**
     * Sparkline SVG mini-chart untuk kartu statistik.
     * $data: array of ['label' => ..., 'total' => int]
     */
    function renderSparkline($data, $color, $width = 160, $height = 52) {
        static $spCounter = 0;
        $n = count($data);
        if ($n < 2) return '';

        $values = array_column($data, 'total');
        $max = max($values);
        $min = min($values);
        $range = max(1, $max - $min);
        $padY = 8;
        $stepX = $width / ($n - 1);
        $pts = [];

        foreach ($data as $i => $d) {
            $x = round($i * $stepX, 2);
            $y = round($padY + ($height - $padY * 2) * (1 - ($d['total'] - $min) / $range), 2);
            $pts[] = [$x, $y];
        }

        $path = "M {$pts[0][0]},{$pts[0][1]} ";
        for ($i = 1; $i < $n - 1; $i++) {
            $mx = round(($pts[$i][0] + $pts[$i + 1][0]) / 2, 2);
            $my = round(($pts[$i][1] + $pts[$i + 1][1]) / 2, 2);
            $path .= "Q {$pts[$i][0]},{$pts[$i][1]} {$mx},{$my} ";
        }
        $secondLast = $pts[$n - 2];
        $last = $pts[$n - 1];
        $path .= "Q {$secondLast[0]},{$secondLast[1]} {$last[0]},{$last[1]}";

        $areaPath = $path . " L {$width},{$height} L 0,{$height} Z";
        $gid = 'sparkGrad' . (++$spCounter);

        return '<svg viewBox="0 0 ' . $width . ' ' . $height . '" class="sparkline" preserveAspectRatio="none" aria-hidden="true">'
            . '<defs><linearGradient id="' . $gid . '" x1="0" y1="0" x2="0" y2="1">'
            . '<stop offset="0%" stop-color="' . $color . '" stop-opacity="0.35"/>'
            . '<stop offset="100%" stop-color="' . $color . '" stop-opacity="0"/>'
            . '</linearGradient></defs>'
            . '<path d="' . e($areaPath) . '" fill="url(#' . $gid . ')" stroke="none"/>'
            . '<path d="' . e($path) . '" fill="none" stroke="' . $color . '" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>'
            . '</svg>';
    }
}

if (!function_exists('getDefaultSettings')) {
    /**
     * Nilai default tiap pengaturan sistem, dipakai kalau baris belum pernah disimpan di tabel `pengaturan`.
     */
    function getDefaultSettings() {
        return [
            'app_nama' => 'StatQuest BPS',
            'app_versi' => '1.0.0',
            'app_organisasi' => 'Badan Pusat Statistik',
            'app_tahun' => date('Y'),
            'sertifikat_prefix' => 'BPS/SQBPS',
            'sertifikat_nilai_lulus' => '70',
            'sertifikat_qr_aktif' => '1',
            'sertifikat_verifikasi_online' => '1',
            'gamifikasi_xp_quiz' => '50',
            'gamifikasi_xp_materi' => '20',
            'gamifikasi_xp_achievement' => '100',
            'gamifikasi_level_max' => '20',
            'notif_email_sertifikat' => '1',
            'notif_email_achievement' => '1',
            'notif_email_reset_password' => '1',
            'notif_email_pengirim' => '',
            'tampilan_warna_utama' => '#1565C0',
            'tampilan_warna_sekunder' => '#F9A825',
            'maintenance_mode' => '0',
            'registrasi_terbuka' => '1',
            'bahasa_default' => 'id',
            'zona_waktu' => 'Asia/Jakarta',
        ];
    }
}

if (!function_exists('getAllSettings')) {
    /**
     * Semua pengaturan sistem, digabung dengan nilai default untuk key yang belum pernah disimpan.
     */
    function getAllSettings($conn) {
        $settings = getDefaultSettings();
        $res = mysqli_query($conn, "SELECT setting_key, setting_value FROM pengaturan");
        while ($row = mysqli_fetch_assoc($res)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        return $settings;
    }
}

if (!function_exists('getSetting')) {
    /**
     * Satu nilai pengaturan sistem (fallback ke default kalau belum pernah disimpan).
     */
    function getSetting($conn, $key, $default = null) {
        $stmt = mysqli_prepare($conn, "SELECT setting_value FROM pengaturan WHERE setting_key=?");
        mysqli_stmt_bind_param($stmt, "s", $key);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        if ($row !== null) {
            return $row['setting_value'];
        }
        $defaults = getDefaultSettings();
        return $default !== null ? $default : ($defaults[$key] ?? '');
    }
}

if (!function_exists('setSetting')) {
    /**
     * Simpan satu pengaturan sistem (insert kalau belum ada, update kalau sudah).
     */
    function setSetting($conn, $key, $value) {
        $stmt = mysqli_prepare($conn, "INSERT INTO pengaturan (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        mysqli_stmt_bind_param($stmt, "ss", $key, $value);
        return mysqli_stmt_execute($stmt);
    }
}

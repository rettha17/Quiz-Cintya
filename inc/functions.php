<?php
/**
 * Shared helper functions used across dashboard/kategori/progress/hasil/achievement pages.
 */

function e($str) {
    return htmlspecialchars((string) $str, ENT_QUOTES, 'UTF-8');
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
 * Badges earned so far based on total XP.
 */
function getBadges($xp) {
    $daftar = [
        ['min_xp' => 100, 'icon' => '🥉', 'nama' => 'Pemula', 'deskripsi' => 'Mendapatkan 100 XP'],
        ['min_xp' => 300, 'icon' => '🥈', 'nama' => 'Pejuang Statistik', 'deskripsi' => 'Mendapatkan 300 XP'],
        ['min_xp' => 500, 'icon' => '🥇', 'nama' => 'Ahli Statistik', 'deskripsi' => 'Mendapatkan 500 XP'],
        ['min_xp' => 1000, 'icon' => '🏆', 'nama' => 'Master Statistik', 'deskripsi' => 'Mendapatkan 1000 XP'],
    ];

    $hasil = [];
    foreach ($daftar as $b) {
        if ($xp >= $b['min_xp']) $hasil[] = $b;
    }

    return $hasil;
}

function levelValid($level) {
    return in_array($level, levelList(), true) ? $level : 'Mudah';
}

/**
 * Varian tema quiz khusus per kategori+level:
 * Statistika Dasar - Sedang -> tema kebun bunga
 * Statistika Dasar - Sulit -> tema bajak laut
 * Pengenalan BPS - Mudah -> tema lebah mengumpulkan nektar
 * Pengenalan BPS - Sedang -> tema panda memanen bambu
 * Pengenalan BPS - Sulit -> tema katak menangkap serangga
 * Kombinasi lain tetap pakai tema pemadam kebakaran (default).
 * Return: 'kebun' | 'bajak-laut' | 'lebah' | 'panda' | 'katak' | '' (default)
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

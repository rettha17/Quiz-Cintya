<?php
/**
 * Partial: baris <tr> tabel leaderboard.
 * Butuh $ranking (array dari getLeaderboardRanking) dan $user_id di scope pemanggil.
 */

if (empty($ranking)) {
    ?>
    <tr>
    <td colspan="9" class="lb-empty-row">Belum ada data quiz untuk filter ini.</td>
    </tr>
    <?php
    return;
}

$rank = 1;

foreach ($ranking as $row) {

    $isMe = ((int) $row['id'] === (int) $user_id);
    $rankClass = $rank <= 3 ? "lb-rank-badge rank-{$rank}" : "lb-rank-badge";
    ?>

    <tr class="<?= $isMe ? 'lb-row-me' : ''; ?>">

    <td><span class="<?= $rankClass; ?>"><?= $rank; ?></span></td>

    <td>
    <div class="lb-player">
    <img src="assets/img/<?= !empty($row['foto']) ? e($row['foto']) : 'default.png'; ?>" class="lb-player-avatar">
    <span class="lb-player-name"><?= e($row['nama']); ?><?= $isMe ? ' <b>(Anda)</b>' : ''; ?></span>
    </div>
    </td>

    <td><span class="lb-level-pill">Level <?= (int) $row['level']; ?></span></td>

    <td><strong><?= number_format((int) $row['total_xp'], 0, ',', '.'); ?> XP</strong></td>

    <td class="lb-col-benar"><?= (int) $row['total_benar']; ?></td>

    <td class="lb-col-salah"><?= (int) $row['total_salah']; ?></td>

    <td><?= $row['akurasi']; ?>%</td>

    <td><?= formatDurasiSingkat($row['rata_durasi']); ?></td>

    <td><span class="lb-streak"><i class="fa-solid fa-fire"></i> <?= (int) $row['streak']; ?></span></td>

    </tr>

    <?php
    $rank++;
}

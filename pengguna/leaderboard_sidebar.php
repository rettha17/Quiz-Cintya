<?php
/**
 * Partial: bagian sidebar yang ikut refresh real-time (kartu peringkat,
 * statistik saya, top 3 kategori). Butuh $myRank, $myStats, $topKategori.
 */
?>

<div class="lb-rank-card">
<span class="lb-rank-card-label">Peringkat Saya</span>
<div class="lb-rank-card-number">#<?= $myRank['rank'] > 0 ? $myRank['rank'] : '-'; ?></div>
<span class="lb-rank-card-sub">dari <?= (int) $myRank['total']; ?> pemain</span>
<div class="lb-rank-card-badge"><i class="fa-solid fa-shield-halved"></i><i class="fa-solid fa-star lb-badge-star"></i></div>
</div>

<div class="lb-stats-card">

<h3>Statistik Saya</h3>

<div class="lb-stats-grid">

<div class="lb-stat-tile tile-purple">
<i class="fa-solid fa-bolt"></i>
<b><?= number_format((int) $myStats['total_xp'], 0, ',', '.'); ?></b>
<span>Total XP</span>
</div>

<div class="lb-stat-tile tile-green">
<i class="fa-solid fa-circle-check"></i>
<b><?= (int) $myStats['total_benar']; ?></b>
<span>Quiz Benar</span>
</div>

<div class="lb-stat-tile tile-red">
<i class="fa-solid fa-circle-xmark"></i>
<b><?= (int) $myStats['total_salah']; ?></b>
<span>Quiz Salah</span>
</div>

<div class="lb-stat-tile tile-blue">
<i class="fa-solid fa-bullseye"></i>
<b><?= $myStats['akurasi']; ?>%</b>
<span>Akurasi</span>
</div>

<div class="lb-stat-tile tile-cyan">
<i class="fa-regular fa-clock"></i>
<b><?= formatDurasiSingkat($myStats['rata_durasi']); ?></b>
<span>Rata-rata Durasi</span>
</div>

<div class="lb-stat-tile tile-orange">
<i class="fa-solid fa-fire"></i>
<b><?= (int) $myStats['streak_terpanjang']; ?> hari</b>
<span>Streak Terpanjang</span>
</div>

</div>

</div>

<div class="lb-topkat-card">

<h3>Top 3 Kategori Saya</h3>

<?php if (empty($topKategori)) { ?>

<p class="lb-empty-note">Belum ada data kategori. Yuk mulai quiz pertamamu!</p>

<?php } else { foreach ($topKategori as $tk) { ?>

<div class="lb-topkat-item">

<div class="lb-topkat-head">
<i class="fa-solid <?= e($tk['icon'] ?: 'fa-chart-simple'); ?>"></i>
<span><?= e($tk['nama']); ?></span>
</div>

<div class="lb-topkat-bar"><div class="lb-topkat-fill" style="width:<?= (int) $tk['rata_skor']; ?>%"></div></div>

<div class="lb-topkat-nums">
<b><?= (int) $tk['rata_skor']; ?>%</b>
<span><?= number_format((int) $tk['xp_kategori'], 0, ',', '.'); ?> XP</span>
</div>

</div>

<?php } } ?>

</div>

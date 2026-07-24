<?php
/**
 * Shared sidebar for every logged-in page. Expects $user (array) and
 * $activeMenu (string) to be set by the including page. $activeMenu is one of:
 * dashboard, materi, quiz, progress, leaderboard, achievement, sertifikat, profil, edit_profil.
 */
$activeMenu = $activeMenu ?? '';
?>
<button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Buka menu">
<i class="fa-solid fa-bars"></i>
</button>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar expanded" id="sidebar">

<div class="logo">

<div class="logo-icon">

<img src="assets/img/login-badge-icon.png" alt="" class="logo-icon-img">

</div>

<div>

<h2><span class="logo-qcs">StatQuest</span> <span class="logo-b">BPS</span></h2>

<p>Jelajah Statistik Indonesia</p>

</div>

</div>

<ul>

<li class="<?= $activeMenu === 'dashboard' ? 'active' : ''; ?>">
<a href="dashboard.php" title="Dashboard">
<i class="fa-solid fa-house"></i>
<span>Dashboard</span>
</a>
</li>

<li class="<?= $activeMenu === 'materi' ? 'active' : ''; ?>">
<a href="materi.php" title="Materi Pembelajaran">
<i class="fa-solid fa-book-open"></i>
<span>Materi Pembelajaran</span>
</a>
</li>

<li class="<?= $activeMenu === 'quiz' ? 'active' : ''; ?>">
<a href="kategori.php" title="StatQuest BPS">
<i class="fa-solid fa-gamepad"></i>
<span>StatQuest BPS</span>
</a>
</li>

<li class="<?= $activeMenu === 'progress' ? 'active' : ''; ?>">
<a href="progress.php" title="Progress">
<i class="fa-solid fa-chart-line"></i>
<span>Progress</span>
</a>
</li>

<li class="<?= $activeMenu === 'leaderboard' ? 'active' : ''; ?>">
<a href="leaderboard.php" title="Leaderboard">
<i class="fa-solid fa-ranking-star"></i>
<span>Leaderboard</span>
</a>
</li>

<li class="<?= $activeMenu === 'achievement' ? 'active' : ''; ?>">
<a href="achievement.php" title="Achievement">
<i class="fa-solid fa-award"></i>
<span>Achievement</span>
</a>
</li>

<li class="<?= $activeMenu === 'sertifikat' ? 'active' : ''; ?>">
<a href="sertifikat.php" title="Sertifikat">
<i class="fa-solid fa-certificate"></i>
<span>Sertifikat</span>
</a>
</li>

<hr>

<li class="<?= $activeMenu === 'profil' ? 'active' : ''; ?>">
<a href="profil.php" title="Profil Saya">
<i class="fa-solid fa-user"></i>
<span>Profil Saya</span>
</a>
</li>

<li class="<?= $activeMenu === 'edit_profil' ? 'active' : ''; ?>">
<a href="edit_profil.php" title="Edit Profil">
<i class="fa-solid fa-user-pen"></i>
<span>Edit Profil</span>
</a>
</li>

</ul>

<div class="sidebar-footer">

<a href="profil.php" class="rail-card" title="Level <?= e($user['level'] ?? '1'); ?>">
<span class="rail-card-icon"><i class="fa-solid fa-bolt"></i></span>
<span class="rail-card-text">
<b>Level <?= e($user['level'] ?? '1'); ?></b>
<span><?= e($user['xp'] ?? 0); ?> XP</span>
</span>
<span class="rail-card-arrow"><i class="fa-solid fa-arrow-right"></i></span>
</a>

<button type="button" class="theme-toggle" id="themeToggle" title="Ganti Tema">
<i class="fa-solid fa-moon"></i>
<span>Mode Gelap</span>
</button>

<a href="logout.php" class="rail-logout" title="Logout">
<i class="fa-solid fa-right-from-bracket"></i>
<span>Logout</span>
</a>

<div class="sidebar-mascot-block">
<img src="assets/img/sidebar-mascot-trophy.png" alt="Maskot StatQuest BPS: Belajar Statistik Jadi Lebih Menyenangkan!" class="sidebar-mascot-full-img">
<p class="sidebar-mascot-caption">Terus belajar dan raih semua pencapaianmu!</p>
</div>

</div>

</aside>

<script>
(function(){
    var sidebar = document.getElementById("sidebar");
    var toggle = document.getElementById("sidebarToggle");
    var backdrop = document.getElementById("sidebarBackdrop");
    var themeToggle = document.getElementById("themeToggle");
    var SIDEBAR_WIDTH = 270;
    var EDGE_ZONE = 24;
    var THEME_KEY = "quizbps-theme";

    function isMobile(){
        return window.matchMedia("(max-width: 768px)").matches;
    }

    /* ---- dark / light theme ---- */
    function applyTheme(theme){
        document.documentElement.setAttribute("data-theme", theme);

        if (themeToggle) {
            themeToggle.querySelector("i").className = theme === "dark" ? "fa-solid fa-sun" : "fa-solid fa-moon";
            themeToggle.querySelector("span").textContent = theme === "dark" ? "Mode Terang" : "Mode Gelap";
        }
    }

    applyTheme(localStorage.getItem(THEME_KEY) || "light");

    if (themeToggle) {
        themeToggle.addEventListener("click", function(){
            var current = document.documentElement.getAttribute("data-theme") === "dark" ? "dark" : "light";
            var next = current === "dark" ? "light" : "dark";
            localStorage.setItem(THEME_KEY, next);
            applyTheme(next);
        });
    }

    function openSidebar(){
        sidebar.classList.add("open");
        backdrop.classList.add("show");
        document.documentElement.classList.add("no-scroll");
        document.body.classList.add("no-scroll");
        toggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
    }

    function closeSidebar(){
        sidebar.classList.remove("open");
        backdrop.classList.remove("show");
        document.documentElement.classList.remove("no-scroll");
        document.body.classList.remove("no-scroll");
        toggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
    }

    toggle.addEventListener("click", function(){
        sidebar.classList.contains("open") ? closeSidebar() : openSidebar();
    });

    backdrop.addEventListener("click", closeSidebar);

    Array.prototype.forEach.call(sidebar.querySelectorAll("a"), function(link){
        link.addEventListener("click", closeSidebar);
    });

    /* ---- swipe / drag gesture (mobile) ---- */
    var touchStartX = null;
    var touchStartY = null;
    var dragging = false;
    var draggingFromClosed = false;

    document.addEventListener("touchstart", function(e){
        if (!isMobile()) return;

        var wasOpen = sidebar.classList.contains("open");
        var x = e.touches[0].clientX;

        if (!wasOpen && x > EDGE_ZONE) return;
        if (wasOpen && !sidebar.contains(e.target) && !backdrop.contains(e.target)) return;

        touchStartX = x;
        touchStartY = e.touches[0].clientY;
        dragging = true;
        draggingFromClosed = !wasOpen;

        sidebar.style.transition = "none";
        backdrop.style.transition = "none";
        backdrop.style.display = "block";
    }, { passive: true });

    document.addEventListener("touchmove", function(e){
        if (!dragging || touchStartX === null) return;

        var x = e.touches[0].clientX;
        var y = e.touches[0].clientY;
        var deltaX = x - touchStartX;
        var deltaY = y - touchStartY;

        if (Math.abs(deltaY) > Math.abs(deltaX)) {
            dragging = false;
            sidebar.style.transition = "";
            backdrop.style.transition = "";
            sidebar.style.transform = "";
            backdrop.style.opacity = "";
            backdrop.style.display = "";
            return;
        }

        var base = draggingFromClosed ? -SIDEBAR_WIDTH : 0;
        var next = base + deltaX;
        next = Math.max(-SIDEBAR_WIDTH, Math.min(0, next));

        sidebar.style.transform = "translateX(" + next + "px)";
        backdrop.style.opacity = (SIDEBAR_WIDTH + next) / SIDEBAR_WIDTH;
    }, { passive: true });

    document.addEventListener("touchend", function(e){
        if (!dragging) return;
        dragging = false;

        sidebar.style.transition = "";
        backdrop.style.transition = "";
        sidebar.style.transform = "";
        backdrop.style.opacity = "";
        backdrop.style.display = "";

        var deltaX = e.changedTouches[0].clientX - touchStartX;
        var threshold = SIDEBAR_WIDTH * 0.35;

        if (draggingFromClosed) {
            if (deltaX > threshold) openSidebar(); else closeSidebar();
        } else {
            if (deltaX < -threshold) closeSidebar(); else openSidebar();
        }

        touchStartX = null;
        touchStartY = null;
    });

    document.addEventListener("touchcancel", function(){
        if (!dragging) return;
        dragging = false;

        sidebar.style.transition = "";
        backdrop.style.transition = "";
        sidebar.style.transform = "";
        backdrop.style.opacity = "";
        backdrop.style.display = "";

        if (draggingFromClosed) closeSidebar(); else openSidebar();

        touchStartX = null;
        touchStartY = null;
    });
})();
</script>

<?php
/**
 * Shared admin sidebar. Expects $admin (array) and $activeMenu (string) to be set
 * by the including page. $activeMenu is one of: dashboard, kategori, soal, materi, game, users, ranking, progress_materi.
 */
$activeMenu = $activeMenu ?? '';
?>
<button type="button" class="admin-sidebar-toggle" id="adminSidebarToggle" aria-label="Buka menu">
<i class="fa-solid fa-bars"></i>
</button>

<div class="admin-sidebar-backdrop" id="adminSidebarBackdrop"></div>

<aside class="admin-sidebar" id="adminSidebar">

<button type="button" class="admin-rail-toggle" id="adminRailToggle" aria-label="Perluas menu">
<i class="fa-solid fa-chevron-right"></i>
</button>

<div class="admin-logo">

<div class="admin-logo-icon">
<i class="fa-solid fa-user-shield"></i>
</div>

<div>
<h2>Admin Panel</h2>
<p>Quiz Challenge BPS</p>
</div>

</div>

<ul>

<li class="<?= $activeMenu === 'dashboard' ? 'active' : ''; ?>">
    <a href="index.php" title="Dashboard">
        <i class="fa-solid fa-gauge"></i>
        <span>Dashboard</span>
    </a>
</li>

<li class="<?= $activeMenu === 'kategori' ? 'active' : ''; ?>">
    <a href="kategori.php" title="Kategori">
        <i class="fa-solid fa-layer-group"></i>
        <span>Kategori</span>
    </a>
</li>

<li class="<?= $activeMenu === 'soal' ? 'active' : ''; ?>">
    <a href="soal.php" title="Bank Soal">
        <i class="fa-solid fa-circle-question"></i>
        <span>Bank Soal</span>
    </a>
</li>

<li class="<?= $activeMenu === 'materi' ? 'active' : ''; ?>">
    <a href="materi.php" title="Materi">
        <i class="fa-solid fa-book-open"></i>
        <span>Materi</span>
    </a>
</li>

<li class="<?= $activeMenu === 'game' ? 'active' : ''; ?>">
    <a href="game.php" title="Game Editor">
        <i class="fa-solid fa-gamepad"></i>
        <span>Game Editor</span>
    </a>
</li>

<li class="<?= $activeMenu === 'users' ? 'active' : ''; ?>">
    <a href="users.php" title="Pemain">
        <i class="fa-solid fa-users"></i>
        <span>Pemain</span>
    </a>
</li>

<li class="<?= $activeMenu === 'ranking' ? 'active' : ''; ?>">
    <a href="ranking.php" title="Ranking">
        <i class="fa-solid fa-trophy"></i>
        <span>Ranking</span>
    </a>
</li>

<li class="<?= $activeMenu === 'progress_materi' ? 'active' : ''; ?>">
    <a href="progress_materi.php" title="Progress Materi">
        <i class="fa-solid fa-chart-line"></i>
        <span>Progress Materi</span>
    </a>
</li>

</ul>

<div class="admin-sidebar-footer">

<a href="../pengguna/dashboard.php" class="admin-rail-card" title="Ke Aplikasi">
<span class="admin-rail-card-icon"><i class="fa-solid fa-arrow-left"></i></span>
<span class="admin-rail-card-text">
<b>Ke Aplikasi</b>
<span>Kembali ke sisi pengguna</span>
</span>
</a>

<button type="button" class="admin-theme-toggle" id="adminThemeToggle" title="Ganti Tema">
<i class="fa-solid fa-moon"></i>
<span>Mode Gelap</span>
</button>

<a href="logout.php" class="admin-rail-logout" title="Logout">
<i class="fa-solid fa-right-from-bracket"></i>
<span>Logout</span>
</a>

</div>

</aside>

<script>
(function(){
    var sidebar = document.getElementById("adminSidebar");
    var toggle = document.getElementById("adminSidebarToggle");
    var backdrop = document.getElementById("adminSidebarBackdrop");
    var railToggle = document.getElementById("adminRailToggle");
    var themeToggle = document.getElementById("adminThemeToggle");
    var SIDEBAR_WIDTH = 270;
    var EDGE_ZONE = 24;
    var THEME_KEY = "quizbps-admin-theme";

    function isMobile(){
        return window.matchMedia("(max-width: 900px)").matches;
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

    /* ---- desktop rail expand/collapse ---- */
    railToggle.addEventListener("click", function(e){
        e.stopPropagation();
        sidebar.classList.toggle("expanded");
    });

    document.addEventListener("click", function(e){
        if (isMobile()) return;
        if (!sidebar.classList.contains("expanded")) return;
        if (sidebar.contains(e.target)) return;
        sidebar.classList.remove("expanded");
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

<div class="admin-profile" id="adminProfileWrap">

<div class="admin-profile-info">
<h4><?= e($admin['nama']); ?></h4>
<span>Administrator</span>
</div>

<div class="admin-profile-avatar">
<i class="fa-solid fa-user-shield"></i>
</div>

<i class="fa-solid fa-chevron-down admin-profile-caret"></i>

<div class="admin-profile-dropdown" id="adminProfileDropdown">
<a href="edit_profil.php"><i class="fa-solid fa-user-pen"></i> Edit Profil</a>
<a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

</div>

<script>
(function(){
    const wrap = document.getElementById("adminProfileWrap");
    const dropdown = document.getElementById("adminProfileDropdown");

    wrap.addEventListener("click", function(e){
        e.stopPropagation();
        dropdown.classList.toggle("show");
    });

    document.addEventListener("click", function(){
        dropdown.classList.remove("show");
    });
})();
</script>

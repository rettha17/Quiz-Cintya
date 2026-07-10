// ==========================
// Notifikasi & Pesan (belum ada sistem notifikasi/pesan)
// ==========================

const bell = document.querySelector(".fa-bell");

if (bell) {

    bell.onclick = function () {
        alert("Tidak ada notifikasi baru.");
    };

}

const envelope = document.querySelector(".fa-envelope");

if (envelope) {

    envelope.onclick = function () {
        alert("Tidak ada pesan baru.");
    };

}

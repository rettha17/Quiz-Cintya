let index = 0;
let xp = 0;

const player = document.getElementById("player");
const map = document.getElementById("map");
const quizBox = document.getElementById("quizBox");

const pertanyaan = document.getElementById("pertanyaan");

const btnA = document.getElementById("A");
const btnB = document.getElementById("B");
const btnC = document.getElementById("C");
const btnD = document.getElementById("D");

const xpText = document.getElementById("xp");

// posisi awal checkpoint
let checkpoint = 500;

buatCheckpoint();

function buatCheckpoint(){

    if(index >= soal.length){

        finishGame();

        return;

    }

    // hapus checkpoint lama
    document.querySelectorAll(".checkpoint").forEach(el=>el.remove());

    const box = document.createElement("div");

    box.className="checkpoint";

    box.innerHTML="📦";

    box.style.left=checkpoint+"px";

    map.appendChild(box);

    jalan();

}

function jalan(){

    player.style.left=checkpoint+"px";

    setTimeout(() => {

        tampilSoal();

    },2000);

}

function tampilSoal(){

    quizBox.style.display="block";

    pertanyaan.innerHTML=soal[index].pertanyaan;

    btnA.innerHTML="A. "+soal[index].opsi_a;
    btnB.innerHTML="B. "+soal[index].opsi_b;
    btnC.innerHTML="C. "+soal[index].opsi_c;
    btnD.innerHTML="D. "+soal[index].opsi_d;

}

btnA.onclick=()=>cek("A");
btnB.onclick=()=>cek("B");
btnC.onclick=()=>cek("C");
btnD.onclick=()=>cek("D");

function cek(jawaban){

    if(jawaban==soal[index].jawaban){

        xp += 10;

    }else{

        nyawa--;

    }

    xpText.innerHTML="XP : "+xp;

    heart.innerHTML="❤️".repeat(nyawa);

    pembahasan.style.display="block";

    isiPembahasan.innerHTML=soal[index].pembahasan;

}

lanjut.onclick=function(){

    pembahasan.style.display="none";

    quizBox.style.display="none";

    index++;

    checkpoint+=500;

    if(nyawa<=0){

        alert("Game Over");

        location.reload();

        return;

    }

    buatCheckpoint();

}

function finishGame(){

fetch("save_xp.php",{

method:"POST",

headers:{
'Content-Type':'application/x-www-form-urlencoded'
},

body:"xp="+xp

})
.then(()=>{

alert("🎉 Selamat!\n\nXP +"+xp);

window.location="dashboard.php";

});

}

let nyawa = 3;

const heart = document.getElementById("heart");

const pembahasan = document.getElementById("pembahasan");

const isiPembahasan = document.getElementById("isiPembahasan");

const lanjut = document.getElementById("lanjut");
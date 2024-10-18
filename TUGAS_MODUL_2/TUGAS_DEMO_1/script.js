// Mendapatkan elemen tampilan dan tombol kalkulator
const screen = document.getElementById('calculator-screen');
const buttons = document.querySelectorAll('.button');

// Variabel untuk menyimpan input dan formula
let formula = ''; // Menyimpan formula inputan

// Fungsi untuk memperbarui layar kalkulator
function updateScreen(value) {
    screen.value = value;
}

// Fungsi untuk menangani input tombol angka dan operator
function handleInput(value) {
    // Jika layar masih 0 dan tombol pertama adalah angka, kita hapus 0 awal
    if (formula === '0' && !isNaN(value)) {
        formula = value;
    } else {
        formula += value;
    }
    updateScreen(formula);
}

// Fungsi untuk menghitung hasil
function calculate() {
    try {
        // Mengganti operator khusus dengan versi JavaScript yang dikenali
        const result = eval(formula.replace(/×/g, '*').replace(/÷/g, '/'));
        formula = result.toString();
        updateScreen(formula);
    } catch (error) {
        formula = 'Error';
        updateScreen(formula);
    }
}

// Fungsi untuk menghapus semua input
function clearAll() {
    formula = '0';
    updateScreen(formula);
}

// Fungsi untuk mengubah nilai positif/negatif
function toggleSign() {
    if (formula.charAt(0) === '-') {
        formula = formula.substring(1);
    } else {
        formula = '-' + formula;
    }
    updateScreen(formula);
}

// Menambahkan event listener untuk setiap tombol
buttons.forEach(button => {
    button.addEventListener('click', function() {
        const value = this.textContent;

        // Memeriksa jenis tombol yang ditekan
        if (!isNaN(value) || value === '.' || value === '%') {
            handleInput(value);
        } else if (value === 'AC') {
            clearAll();
        } else if (value === '=') {
            calculate();
        } else if (value === '±') {
            toggleSign();
        } else {
            handleInput(` ${value} `); // Menambahkan operator dengan spasi di sekitar
        }
    });
});

// Mendapatkan elemen kalkulator untuk bisa digeser
const calculator = document.querySelector('.calculator');






// Variabel untuk menyimpan posisi awal dan status drag
let isDragging = false;
let offsetX, offsetY;

// Fungsi untuk memulai drag
calculator.addEventListener('mousedown', function(e) {
    isDragging = true;
    offsetX = e.clientX - calculator.offsetLeft;
    offsetY = e.clientY - calculator.offsetTop;
    calculator.style.cursor = 'grabbing'; // Ubah kursor saat di-drag
});

// Fungsi untuk menangani pergerakan kalkulator
document.addEventListener('mousemove', function(e) {
    if (isDragging) {
        let posX = e.clientX - offsetX;
        let posY = e.clientY - offsetY;

        // Cegah kalkulator keluar dari batas kiri dan kanan
        if (posX < 0) posX = 0;
        if (posX + calculator.offsetWidth > window.innerWidth) {
            posX = window.innerWidth - calculator.offsetWidth;
        }

        // Cegah kalkulator keluar dari batas atas dan bawah
        if (posY < 0) posY = 0;
        if (posY + calculator.offsetHeight > window.innerHeight) {
            posY = window.innerHeight - calculator.offsetHeight;
        }

        calculator.style.left = `${posX}px`;
        calculator.style.top = `${posY}px`;
    }
});

// Fungsi untuk menghentikan drag
document.addEventListener('mouseup', function() {
    isDragging = false;
    calculator.style.cursor = 'move'; // Kembalikan kursor setelah selesai di-drag
});

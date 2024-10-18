function validateForm() {
    var name = document.getElementById('name').value;
    var email = document.getElementById('email').value;
    var password = document.getElementById('password').value;

    // Regex untuk memeriksa apakah email berakhir dengan @gmail.com
    var emailPattern = /^[a-zA-Z0-9._%+-]+@gmail\.com$/;

    if (name == "" || email == "" || password == "") {
        alert("Semua data harus diisi.");
        return false;
    } else if (!emailPattern.test(email)) {
        alert("Email harus menggunakan domain @gmail.com.");
        return false;
    } else {
        alert("Pendaftaran berhasil!");
    }
}

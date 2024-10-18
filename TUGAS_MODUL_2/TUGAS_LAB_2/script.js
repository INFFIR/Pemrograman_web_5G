function calculateSum() {
    var num1 = parseFloat(document.getElementById('number1').value);
    var num2 = parseFloat(document.getElementById('number2').value);
    var sum = num1 + num2;
    document.getElementById('result').innerHTML = 'Hasil: ' + sum;
}

function resetForm() {
    document.getElementById('number1').value = '';
    document.getElementById('number2').value = '';
    document.getElementById('result').innerHTML = '';
}

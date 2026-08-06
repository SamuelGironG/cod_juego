function seleccionarParte(parte, boton) {

    const botones = document.querySelectorAll('.btn-parte');
    botones.forEach(btn => btn.classList.remove('seleccionado'));

    boton.classList.add('seleccionado');

    document.getElementById('inputParteCuerpo').value = parte;
}
function seleccionarParte(parte, boton) {
    const botones = document.querySelectorAll('.btn-parte');
    botones.forEach(btn => btn.classList.remove('seleccionado'));

    boton.classList.add('seleccionado');
    document.getElementById('inputParteCuerpo').value = parte;
}



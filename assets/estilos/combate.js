function seleccionarParte(parte, boton) {
    // 1. Quitar la clase 'seleccionado' a todos los botones de partes del cuerpo
    const botones = document.querySelectorAll('.btn-parte');
    botones.forEach(btn => btn.classList.remove('seleccionado'));

    // 2. Añadir la clase 'seleccionado' al botón que se presionó
    boton.classList.add('seleccionado');

    // 3. Asignar el valor al input oculto para que PHP lo reciba por POST
    document.getElementById('inputParteCuerpo').value = parte;
}
function seleccionarParte(parte, boton) {
    const botones = document.querySelectorAll('.btn-parte');
    botones.forEach(btn => btn.classList.remove('seleccionado'));

    boton.classList.add('seleccionado');
    document.getElementById('inputParteCuerpo').value = parte;
}



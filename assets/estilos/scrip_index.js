function desmutear() {
    var video = document.getElementById("video-fondo");
    var btn = document.getElementById("btn-audio");
    
    if (video.muted) {
        video.muted = false;
        btn.innerHTML = "Silenciar Audio";
    } else {
        video.muted = true;
        btn.innerHTML = "Activar Audio";
    }
}

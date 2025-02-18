function zoomImage(src) {
    var overlay = document.createElement("div");
    overlay.classList.add("zoom-overlay");

    var img = document.createElement("img");
    img.src = src;

    var closeBtn = document.createElement("button");
    closeBtn.classList.add("close-btn");
    closeBtn.textContent = "X";
    closeBtn.onclick = function() {
        overlay.style.display = "none";
    };

    overlay.appendChild(img);
    overlay.appendChild(closeBtn);

    document.body.appendChild(overlay);

    overlay.style.display = "flex";
}
"use strict";

document.addEventListener("DOMContentLoaded", () => {
    initLogoutModal();
});

function initLogoutModal() {
    const btnLogout       = document.getElementById("btnLogout");
    const logoutOverlay   = document.getElementById("logoutOverlay");
    const btnLogoutCancel = document.getElementById("btnLogoutCancel");

    if (btnLogout && logoutOverlay) {
        btnLogout.addEventListener("click", (e) => {
            e.preventDefault();
            logoutOverlay.style.display = "flex";
        });
    }

    if (btnLogoutCancel && logoutOverlay) {
        btnLogoutCancel.addEventListener("click", () => {
            logoutOverlay.style.display = "none";
        });
    }

    if (logoutOverlay) {
        logoutOverlay.addEventListener("click", (e) => {
            if (e.target === logoutOverlay) logoutOverlay.style.display = "none";
        });
    }
}

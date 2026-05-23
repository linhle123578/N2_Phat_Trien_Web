"use strict";

document.addEventListener("DOMContentLoaded", () => {
    // Avatar đã bị xóa — không gọi initAvatarPreview nữa
    initPasswordToggles();
    initAddressToggle();
    initPasswordValidation();
});

/* ── Password Toggles ────────────────────────────────────── */
function initPasswordToggles() {
    document.querySelectorAll(".btn-eye[data-target]").forEach((btn) => {
        btn.addEventListener("click", () => {
            const inp = document.getElementById(btn.dataset.target);
            if (!inp) return;
            const show = inp.type === "password";
            inp.type = show ? "text" : "password";
            const icon = btn.querySelector("i");
            if (icon) icon.className = show ? "bi bi-eye" : "bi bi-eye-slash";
        });
    });
}

/* ── Add Address Form Toggle ─────────────────────────────── */
function initAddressToggle() {
    const btnShow   = document.getElementById("btnShowAddAddr");
    const btnCancel = document.getElementById("btnCancelAddAddr");
    const form      = document.getElementById("addAddrForm");
    if (!btnShow || !form) return;

    btnShow.addEventListener("click", () => {
        form.style.display = "block";
        btnShow.style.display = "none";
        form.scrollIntoView({ behavior: "smooth", block: "nearest" });
    });

    if (btnCancel) {
        btnCancel.addEventListener("click", () => {
            form.style.display = "none";
            btnShow.style.display = "inline-flex";
        });
    }
}

/* ── Password Match Validation ───────────────────────────── */
function initPasswordValidation() {
    const newPw  = document.getElementById("fieldPwNew");
    const confPw = document.getElementById("fieldPwConfirm");
    if (!newPw || !confPw) return;

    function check() {
        if (!confPw.value) {
            confPw.classList.remove("is-valid", "is-invalid");
            return;
        }
        if (newPw.value === confPw.value) {
            confPw.classList.remove("is-invalid");
            confPw.classList.add("is-valid");
        } else {
            confPw.classList.remove("is-valid");
            confPw.classList.add("is-invalid");
        }
    }

    newPw.addEventListener("input", check);
    confPw.addEventListener("input", check);
}
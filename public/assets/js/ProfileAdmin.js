/**
 * ProfileAdmin.js
 * Farm2Home Admin – Tài khoản của tôi
 */

(function () {
    "use strict";

    /* ── Sidebar toggle (mobile) ──────────────────── */
    function initSidebar() {
        const sidebar  = document.getElementById("paSidebar");
        const btnToggle = document.getElementById("btnToggle");
        const overlay  = document.getElementById("paOverlay");

        if (!sidebar || !btnToggle) return;

        function openSidebar() {
            sidebar.classList.add("open");
            overlay && overlay.classList.add("show");
        }

        function closeSidebar() {
            sidebar.classList.remove("open");
            overlay && overlay.classList.remove("show");
        }

        btnToggle.addEventListener("click", () => {
            sidebar.classList.contains("open") ? closeSidebar() : openSidebar();
        });

        overlay && overlay.addEventListener("click", closeSidebar);
    }

    /* ── Avatar preview ───────────────────────────── */
    function initAvatarPreview() {
        const input   = document.getElementById("avatarInput");
        const preview = document.getElementById("avatarPreview");

        if (!input || !preview) return;

        input.addEventListener("change", function () {
            const file = this.files[0];
            if (!file) return;

            const validTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
            if (!validTypes.includes(file.type)) {
                showToast("Chỉ hỗ trợ ảnh JPG, PNG, GIF, WEBP.", "danger");
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                showToast("Ảnh quá lớn (tối đa 5MB).", "danger");
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                preview.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    }

    /* ── Password eye toggle ──────────────────────── */
    function initPasswordToggles() {
        document.querySelectorAll(".pa-eye-btn[data-target]").forEach((btn) => {
            btn.addEventListener("click", function () {
                const targetId = this.getAttribute("data-target");
                const input    = document.getElementById(targetId);
                const icon     = this.querySelector("i");

                if (!input || !icon) return;

                if (input.type === "password") {
                    input.type = "text";
                    icon.classList.replace("bi-eye-slash", "bi-eye");
                } else {
                    input.type = "password";
                    icon.classList.replace("bi-eye", "bi-eye-slash");
                }
            });
        });
    }

    /* ── Password match validation ────────────────── */
    function initPasswordValidation() {
        const newPw  = document.getElementById("new_password");
        const confPw = document.getElementById("confirm_password");

        if (!newPw || !confPw) return;

        function check() {
            if (confPw.value.length === 0) {
                confPw.classList.remove("is-valid", "is-invalid");
                return;
            }
            if (newPw.value === confPw.value) {
                confPw.classList.replace("is-invalid", "is-valid") ||
                    confPw.classList.add("is-valid");
            } else {
                confPw.classList.replace("is-valid", "is-invalid") ||
                    confPw.classList.add("is-invalid");
            }
        }

        newPw.addEventListener("input",  check);
        confPw.addEventListener("input", check);
    }

    /* ── Inline toast helper ──────────────────────── */
    function showToast(message, type) {
        type = type || "info";

        const container = document.querySelector(".pa-content");
        if (!container) return;

        const toast = document.createElement("div");
        toast.className = `alert alert-${type} alert-dismissible fade show rounded-3 mb-3`;
        toast.setAttribute("role", "alert");
        toast.innerHTML = `
            <i class="bi bi-${type === "danger" ? "exclamation-circle-fill" : "check-circle-fill"} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
        `;

        container.prepend(toast);

        // Auto-dismiss after 4 seconds
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(toast);
            bsAlert && bsAlert.close();
        }, 4000);
    }

    /* ── Active nav link highlight ────────────────── */
    function initActiveNav() {
        const current = window.location.pathname;
        document.querySelectorAll(".pa-link").forEach((link) => {
            if (link.getAttribute("href") !== "#" && current.includes(link.getAttribute("href"))) {
                document.querySelectorAll(".pa-link").forEach((l) => l.classList.remove("active"));
                link.classList.add("active");
            }
        });
    }

    /* ── Bootstrap form validation ────────────────── */
    function initFormValidation() {
        document.querySelectorAll("form").forEach((form) => {
            form.addEventListener("submit", function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add("was-validated");
            });
        });
    }

    /* ── Init ─────────────────────────────────────── */
    document.addEventListener("DOMContentLoaded", function () {
        initSidebar();
        initAvatarPreview();
        initPasswordToggles();
        initPasswordValidation();
        initActiveNav();
        initFormValidation();
    });
})();
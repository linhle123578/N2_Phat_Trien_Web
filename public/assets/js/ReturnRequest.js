/**
 * ReturnRequest.js
 * Xử lý UI động cho form yêu cầu đổi/trả hàng
 */
(function () {
  'use strict';

  // ── Reason chips ──────────────────────────────────────
  document.querySelectorAll('.rr-reason-chip').forEach(chip => {
    const radio = chip.querySelector('input[type=radio]');
    chip.addEventListener('click', () => {
      // Bỏ selected khỏi tất cả
      document.querySelectorAll('.rr-reason-chip').forEach(c => c.classList.remove('selected'));
      chip.classList.add('selected');
      radio.checked = true;
    });
    // Nếu đã checked (re-render sau lỗi)
    if (radio.checked) chip.classList.add('selected');
  });

  // ── Return type cards ─────────────────────────────────
  const typeCards   = document.querySelectorAll('.rr-type-card');
  const bankSection = document.getElementById('bankSection');

  function updateTypeSelection() {
    typeCards.forEach(card => {
      const radio = card.querySelector('input[type=radio]');
      card.classList.toggle('selected', radio.checked);
    });

    const selectedType = document.querySelector('.rr-type-card input[type=radio]:checked');
    if (bankSection) {
      if (selectedType && selectedType.value === 'Hoàn tiền') {
        bankSection.style.display = '';
        bankSection.style.animation = 'rr-slide-down .25s ease';
      } else {
        bankSection.style.display = 'none';
      }
    }
  }

  typeCards.forEach(card => {
    card.addEventListener('click', () => {
      const radio = card.querySelector('input[type=radio]');
      radio.checked = true;
      updateTypeSelection();
    });
  });

  // Init
  updateTypeSelection();

  // ── Textarea char count ───────────────────────────────
  const textarea  = document.getElementById('description');
  const charCount = document.getElementById('charCount');
  if (textarea && charCount) {
    function updateCount() {
      const len = textarea.value.length;
      charCount.textContent = len;
      charCount.style.color = len > 900 ? '#c0392b' : '';
    }
    textarea.addEventListener('input', updateCount);
    updateCount();
  }

  // ── Form submit loading state ─────────────────────────
  const form      = document.getElementById('returnForm');
  const btnSubmit = document.getElementById('btnSubmit');
  if (form && btnSubmit) {
    form.addEventListener('submit', () => {
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Đang gửi...';
    });
  }

  // ── Bank inputs: uppercase holder name ───────────────
  const holderInput = document.querySelector('input[name=bank_holder]');
  if (holderInput) {
    holderInput.addEventListener('input', function () {
      const pos = this.selectionStart;
      this.value = this.value.toUpperCase();
      this.setSelectionRange(pos, pos);
    });
  }

  // ── Bank account: numbers only ────────────────────────
  const accountInput = document.querySelector('input[name=bank_account]');
  if (accountInput) {
    accountInput.addEventListener('input', function () {
      this.value = this.value.replace(/[^0-9]/g, '');
    });
  }
})();
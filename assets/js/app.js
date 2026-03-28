

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.toast').forEach(function (el) {
    try { new bootstrap.Toast(el, { delay: 3500 }).show(); } catch (e) {}
  });

  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      const msg = el.getAttribute('data-confirm') || 'Are you sure?';
      if (!window.confirm(msg)) {
        event.preventDefault();
      }
    });
  });
});

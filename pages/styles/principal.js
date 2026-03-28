document.addEventListener('DOMContentLoaded', function () {
  document.body.classList.add('js-ready');

  var confirmForms = document.querySelectorAll('form[data-confirm]');
  confirmForms.forEach(function (form) {
    form.addEventListener('submit', function (event) {
      var message = form.getAttribute('data-confirm') || 'Confirmer cette action ?';
      if (!window.confirm(message)) {
        event.preventDefault();
      }
    });
  });

  var buttons = document.querySelectorAll('button');
  buttons.forEach(function (button) {
    button.addEventListener('mouseenter', function () {
      button.style.transform = 'translateY(-1px)';
    });
    button.addEventListener('mouseleave', function () {
      button.style.transform = '';
    });
  });

  var firstInput = document.querySelector('input[type="text"], input[type="email"], input[type="password"]');
  if (firstInput) {
    firstInput.focus();
  }
});

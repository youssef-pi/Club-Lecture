document.addEventListener('DOMContentLoaded', function () {
  document.body.classList.add('js-ready');

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

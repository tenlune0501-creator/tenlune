(function () {
  if (window.location.pathname.indexOf('/contact') === -1) return;
  var params = new URLSearchParams(window.location.search);
  var prefill = params.get('prefill');
  if (!prefill) return;

  var tries = 0;
  var timer = setInterval(function () {
    tries++;
    var ta = document.querySelector('textarea[name="quote-summary"]');
    if (ta) {
      if (!ta.value) ta.value = decodeURIComponent(prefill);
      clearInterval(timer);
    }
    if (tries > 30) clearInterval(timer);
  }, 150);
})();
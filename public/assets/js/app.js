document.addEventListener('submit', e => {
  const m = e.target.dataset && e.target.dataset.confirm;
  if (m && !confirm(m)) e.preventDefault();
});
(function () {
  const sel = document.getElementById('doc_type'), cp = document.getElementById('copies'), out = document.getElementById('fee');
  if (!sel || !cp || !out) return;
  const calc = () => {
    const f = parseFloat((sel.selectedOptions[0] || {dataset: {}}).dataset.fee || 0);
    const c = Math.max(1, parseInt(cp.value || 1, 10));
    out.textContent = '₱' + (f * c).toLocaleString('en-PH', {minimumFractionDigits: 2});
  };
  sel.addEventListener('input', calc); cp.addEventListener('input', calc); calc();
})();
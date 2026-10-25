<h1>Payment</h1>
<div class="card narrow">
  <p>PAY-<?= (int)$p['id'] ?> · <?= e($p['description']) ?></p>
  <p class="big"><?= money($p['amount']) ?></p>
  <?php if ($p['remarks']): ?><div class="alert error">Previous attempt rejected: <?= e($p['remarks']) ?></div><?php endif; ?>
  <form method="post" action="<?= url("/student/payments/{$p['id']}/pay") ?>"><?= csrf_field() ?>
    <label>Payment method</label>
    <select name="method" required><option value="e-wallet">E-wallet (GCash / Maya)</option><option value="bank">Bank transfer</option></select>
    <label>Transaction reference number</label>
    <input name="reference_no" required maxlength="30" placeholder="e.g. TXN48213" style="text-transform:uppercase">
    <p class="hint">Pay through your e-wallet or bank, then enter the reference number here. The Cashier verifies it and issues your official receipt. Prefer cash? Go to the Cashier window and quote <strong>PAY-<?= (int)$p['id'] ?></strong>. No card details are stored.</p>
    <div class="row"><button class="btn">Submit payment</button><a class="btn outline" href="<?= url('/student/payments') ?>">Cancel</a></div>
  </form>
</div>
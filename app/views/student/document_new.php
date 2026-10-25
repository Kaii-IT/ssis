<h1>Request a Document</h1><p class="sub">Submit a request, then pay to start processing.</p>
<div class="card narrow">
  <form method="post" action="<?= url('/student/documents') ?>"><?= csrf_field() ?>
    <label>Document type</label>
    <select name="document_type_id" id="doc_type" required>
      <?php foreach ($types as $t): ?>
        <option value="<?= (int)$t['id'] ?>" data-fee="<?= e($t['fee']) ?>" <?= (int)old('document_type_id') === (int)$t['id'] ? 'selected' : '' ?>>
          <?= e($t['name']) ?> — <?= money($t['fee']) ?><?= $t['requirement'] !== 'none' ? ' (requires ' . e($t['requirement']) . ')' : '' ?>
        </option>
      <?php endforeach; ?>
    </select>
    <label>Number of copies</label><input type="number" id="copies" name="copies" min="1" max="10" value="<?= e(old('copies', '1')) ?>" required>
    <label>Purpose</label><textarea name="purpose" rows="3" maxlength="255" placeholder="e.g. Scholarship, employment" required><?= e(old('purpose')) ?></textarea>
    <p><strong>Total fee: <span id="fee">₱0.00</span></strong></p>
    <button class="btn">Submit request</button>
  </form>
</div>
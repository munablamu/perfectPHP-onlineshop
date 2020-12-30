<div class="alerts">
  <?php foreach ( $flash as $type => $message ): ?>
  <div class="alert alert-<?= $this->escape($type) ?>" role="alert">
    <p><?= $this->escape($message) ?></p>
  </div>
  <?php endforeach; ?>
</div>

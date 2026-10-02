<?php
declare(strict_types=1);
require __DIR__ . '/private/bootstrap.php';

$reference = $_SESSION['confirmation_reference'] ?? null;
if (!is_string($reference) || !preg_match('/^DBAC-[A-F0-9]{16}$/', $reference)) {
    redirect_to($config, 'index.php');
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#A65A3A">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(path_url($config, 'assets/style.css')) ?>?v=20261002-redesign">
  <script src="<?= e(path_url($config, 'assets/form.js')) ?>" defer></script>
  <meta name="robots" content="noindex, nofollow">
  <title>Registration received | DezignBank</title>
</head>
<body class="confirmation-page">
  <div class="site-shell">
    <header class="site-header">
      <a class="brand" href="<?= e(path_url($config, 'index.php')) ?>" aria-label="DezignBank competition registration home">
        <img class="brand-mark" src="<?= e(path_url($config, 'assets/dezignbank-mark.svg')) ?>" alt="" width="31" height="31">
        <span class="brand-name">DezignBank</span>
      </a>
      <span class="header-label">Architecture Student Competition</span>
    </header>
    <main class="confirmation-main">
      <section class="confirmation-card" aria-labelledby="confirmation-title">
        <span class="confirmation-icon" aria-hidden="true">✓</span>
        <p class="eyebrow">DezignBank / Competition</p>
        <h1 id="confirmation-title">Registration received</h1>
        <p>Your details have been saved. Keep this reference for your records.</p>
        <div class="reference-box"><span>YOUR REGISTRATION REFERENCE</span><strong><?= e($reference) ?></strong></div>
        <p class="confirmation-footnote">This confirmation is available in this browser session. Print or save it now for your records.</p>
        <div class="confirmation-actions">
          <button type="button" class="submit-button print-button" data-print><span>Print confirmation</span><span class="button-arrow" aria-hidden="true">↗</span></button>
          <a href="<?= e(path_url($config, 'index.php')) ?>" class="text-link">Back to registration</a>
        </div>
      </section>
    </main>
    <footer class="site-footer"><span>© <?= date('Y') ?> DezignBank</span><span>Architecture Student Competition</span></footer>
  </div>
</body>
</html>

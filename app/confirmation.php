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
  <link rel="stylesheet" href="<?= e(path_url($config, 'assets/style.css')) ?>">
  <script src="<?= e(path_url($config, 'assets/form.js')) ?>" defer></script>
  <meta name="robots" content="noindex, nofollow">
  <title>Registration received | DezignBank</title>
</head>
<body class="confirmation-page">
  <div class="site-shell">
    <header class="site-header">
      <a class="brand" href="<?= e(path_url($config, 'index.php')) ?>" aria-label="DezignBank competition registration home">
        <span class="brand-placeholder" aria-hidden="true">LOGO</span>
        <span class="brand-name">DezignBank<span class="brand-dot">.</span></span>
      </a>
      <span class="header-label">Architecture student competition</span>
    </header>
    <main class="confirmation-main">
      <div class="confirmation-visual" aria-hidden="true"><img src="<?= e(path_url($config, 'assets/heritage-architecture.svg')) ?>" alt="" width="900" height="690"></div>
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

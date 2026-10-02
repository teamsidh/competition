<?php
declare(strict_types=1);
require __DIR__ . '/private/bootstrap.php';

$values = $_SESSION['form_values'] ?? [];
$errors = $_SESSION['form_errors'] ?? [];
$general = $_SESSION['form_general'] ?? null;
unset($_SESSION['form_values'], $_SESSION['form_errors'], $_SESSION['form_general']);
$isOpen = ($config['REGISTRATION_OPEN'] ?? false) === true;
$hasErrors = $general !== null || $errors !== [];

function field_error(array $errors, string $key): string
{
    return isset($errors[$key]) ? '<span class="field-error" id="' . e($key) . '-error">' . e($errors[$key]) . '</span>' : '';
}

function field_attributes(array $errors, string $key): string
{
    return isset($errors[$key]) ? ' aria-invalid="true" aria-describedby="' . e($key) . '-error"' : '';
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#A65A3A">
  <meta name="description" content="Register for the DezignBank Architecture Student Competition.">
  <link rel="canonical" href="<?= e(canonical_url($config, 'index.php')) ?>">
  <link rel="stylesheet" href="<?= e(path_url($config, 'assets/style.css')) ?>">
  <script src="<?= e(path_url($config, 'assets/form.js')) ?>" defer></script>
  <title>Architecture Student Competition — Registration | DezignBank</title>
</head>
<body>
  <a class="skip-link" href="#registration">Skip to registration</a>
  <div class="site-shell">
    <header class="site-header">
      <a class="brand" href="<?= e(path_url($config, 'index.php')) ?>" aria-label="DezignBank competition registration home">
        <span class="brand-placeholder" aria-hidden="true">DB</span>
        <span class="brand-name">DezignBank<span class="brand-dot">.</span></span>
      </a>
      <span class="header-label">Architecture student competition</span>
    </header>

    <main class="main-grid" id="registration">
      <section class="story-panel" aria-labelledby="story-title">
        <div class="story-topline"><span>DEZIGNBANK / COMPETITION</span><span class="topline-rule"></span><span>01</span></div>
        <div class="story-copy">
          <p class="eyebrow">A space for the next generation</p>
          <h1 id="story-title">Design begins with <em>looking back.</em></h1>
          <p>Architecture carries ideas across generations. Bring your point of view to the DezignBank Architecture Student Competition.</p>
        </div>
        <div class="heritage-art">
          <img src="<?= e(path_url($config, 'assets/heritage-architecture.svg')) ?>" alt="" width="900" height="690">
          <span class="art-caption">A study of historic arches and proportion</span>
        </div>
        <div class="story-bottom"><span>HERITAGE</span><span class="story-line"></span><span>IMAGINATION</span><span class="story-line"></span><span>FUTURE</span></div>
      </section>

      <section class="form-panel" aria-labelledby="form-title">
        <div class="form-topline"><span>STUDENT REGISTRATION</span><span class="form-index">DB / 01</span></div>
        <div class="form-intro">
          <p class="eyebrow">Your first step</p>
          <h2 id="form-title">Architecture Student Competition <span>— Registration</span></h2>
          <p>Tell us a little about yourself and your college. Fields marked <span class="required-mark">*</span> are required.</p>
        </div>

        <?php if (!$isOpen): ?>
          <div class="closed-notice" role="status">
            <h3>Registration is currently closed</h3>
            <p>Please check back for updates from DezignBank.</p>
          </div>
        <?php else: ?>
          <?php if ($hasErrors): ?>
            <div class="error-summary" id="error-summary" role="alert" tabindex="-1">
              <strong>We couldn’t submit your registration.</strong>
              <?php if ($general !== null): ?><p><?= e($general) ?></p><?php endif; ?>
              <?php if ($errors !== []): ?>
                <ul>
                  <?php foreach ($errors as $key => $message): ?>
                    <li><a href="#<?= e($key) ?>"><?= e($message) ?></a></li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <form method="post" action="<?= e(path_url($config, 'register.php')) ?>" id="registration-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
            <div class="honeypot" aria-hidden="true">
              <label for="website">Website</label>
              <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="form-section-title"><span>01</span><h3>Personal details</h3></div>
            <div class="field-grid">
              <div class="field field-full">
                <label for="full_name">Full name <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="full_name" name="full_name" type="text" value="<?= e($values['full_name'] ?? '') ?>" autocomplete="name" minlength="2" maxlength="120" required<?= field_attributes($errors, 'full_name') ?> placeholder="Your full name">
                <?= field_error($errors, 'full_name') ?>
              </div>
              <div class="field">
                <label for="email">Email address <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="email" name="email" type="email" value="<?= e($values['email'] ?? '') ?>" autocomplete="email" maxlength="254" required<?= field_attributes($errors, 'email') ?> placeholder="you@example.com">
                <?= field_error($errors, 'email') ?>
              </div>
              <div class="field">
                <label for="mobile">WhatsApp / mobile number <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="mobile" name="mobile" type="tel" value="<?= e($values['mobile'] ?? '') ?>" autocomplete="tel" inputmode="tel" maxlength="24" required<?= field_attributes($errors, 'mobile') ?> placeholder="+91 98765 43210">
                <?= field_error($errors, 'mobile') ?>
              </div>
            </div>

            <div class="form-section-title second"><span>02</span><h3>Academic details</h3></div>
            <div class="field-grid">
              <div class="field field-full">
                <label for="college_name">College / institution name <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="college_name" name="college_name" type="text" value="<?= e($values['college_name'] ?? '') ?>" autocomplete="organization" minlength="2" maxlength="160" required<?= field_attributes($errors, 'college_name') ?> placeholder="Name of your college or institution">
                <?= field_error($errors, 'college_name') ?>
              </div>
              <div class="field">
                <label for="college_city">College city <span class="required-mark" aria-hidden="true">*</span></label>
                <input id="college_city" name="college_city" type="text" value="<?= e($values['college_city'] ?? '') ?>" autocomplete="address-level2" minlength="2" maxlength="100" required<?= field_attributes($errors, 'college_city') ?> placeholder="City">
                <?= field_error($errors, 'college_city') ?>
              </div>
              <div class="field">
                <label for="year_of_study">Year of study <span class="required-mark" aria-hidden="true">*</span></label>
                <select id="year_of_study" name="year_of_study" required<?= field_attributes($errors, 'year_of_study') ?>>
                  <option value="">Select your year</option>
                  <?php foreach (['1' => '1st year', '2' => '2nd year', '3' => '3rd year', '4' => '4th year', '5' => '5th year'] as $value => $label): ?>
                    <option value="<?= $value ?>"<?= ($values['year_of_study'] ?? '') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
                  <?php endforeach; ?>
                </select>
                <?= field_error($errors, 'year_of_study') ?>
              </div>
            </div>

            <div class="consent-block">
              <label class="checkbox-label" for="consent">
                <input id="consent" name="consent" type="checkbox" value="1" required<?= ($values['consent'] ?? '') === '1' ? ' checked' : '' ?><?= field_attributes($errors, 'consent') ?>>
                <span>I agree to the use of my details to manage my registration and send competition-related updates. <span class="required-mark" aria-hidden="true">*</span></span>
              </label>
              <?= field_error($errors, 'consent') ?>
              <p class="data-notice">Data use: Your name, contact details, college and year of study are collected for registration and competition-related communication.</p>
            </div>

            <button class="submit-button" type="submit"><span>Submit registration</span><span class="button-arrow" aria-hidden="true">↗</span></button>
            <p class="submit-note">A registration reference will appear after your details are saved.</p>
          </form>
        <?php endif; ?>
      </section>
    </main>
    <footer class="site-footer"><span>© <?= date('Y') ?> DezignBank</span><span>Architecture Student Competition</span></footer>
  </div>
</body>
</html>

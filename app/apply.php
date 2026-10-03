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
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
  <link rel="canonical" href="<?= e(canonical_url($config, 'apply.php')) ?>">
  <link rel="stylesheet" href="<?= e(path_url($config, 'assets/style.css')) ?>?v=20261003">
  <script src="<?= e(path_url($config, 'assets/form.js')) ?>" defer></script>
  <title>Architecture Student Competition — Registration | DezignBank</title>
</head>
<body>
  <a class="skip-link" href="#registration">Skip to registration</a>
  <div class="site-shell">
    <header class="site-header">
      <a class="brand" href="<?= e(path_url($config, 'index.php')) ?>" aria-label="DezignBank competition registration home">
        <img class="brand-mark" src="<?= e(path_url($config, 'assets/dezignbank-mark.svg')) ?>" alt="" width="31" height="31">
        <span class="brand-name">DezignBank</span>
      </a>
      <span class="header-label">Architecture Student Competition</span>
    </header>

    <main class="main-grid" id="registration">
      <section class="story-panel" aria-labelledby="story-title">
        <div class="story-copy">
          <p class="eyebrow">DezignBank / Student competition</p>
          <h1 id="story-title">Your idea starts <span>here.</span></h1>
          <p>Register with your contact and college details. We’ll use them to manage your registration and send competition-related updates.</p>
        </div>
        <figure class="heritage-art">
          <img src="<?= e(path_url($config, 'assets/adalaj-stepwell.jpg')) ?>" alt="Carved stone columns and galleries at Adalaj Stepwell in Gujarat" width="960" height="1158">
          <figcaption>Adalaj Stepwell, Gujarat · Photograph by <a href="https://commons.wikimedia.org/wiki/File:Adalaj_Stepwell-Adalaj_Ahmedabad-Gujarat-IMG_1021.jpg" rel="noopener noreferrer" target="_blank">Shivajidesai29</a>, <a href="https://creativecommons.org/licenses/by-sa/4.0/" rel="noopener noreferrer" target="_blank">CC BY-SA 4.0</a>. Cropped for this layout.</figcaption>
        </figure>
      </section>

      <section class="form-panel" aria-labelledby="form-title">
        <div class="form-intro">
          <p class="eyebrow">Registration form</p>
          <h2 id="form-title">Your details</h2>
          <p>Fields marked <span class="required-mark">*</span> are required.</p>
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

            <div class="form-section-title"><h3>How are you entering?</h3></div>
            <fieldset class="entry-options" id="entry_type">
              <legend>Choose your entry type <span class="required-mark">*</span></legend>
              <label class="entry-option"><input type="radio" name="entry_type" value="solo" <?= ($values['entry_type'] ?? 'solo') === 'solo' ? 'checked' : '' ?> required><span><strong>Solo entry</strong><small>Just you and your design idea.</small></span></label>
              <label class="entry-option"><input type="radio" name="entry_type" value="team" <?= ($values['entry_type'] ?? '') === 'team' ? 'checked' : '' ?>><span><strong>Team entry</strong><small>Register as lead and add your teammates.</small></span></label>
              <?= field_error($errors, 'entry_type') ?>
            </fieldset>
            <div class="team-fields" id="team-fields" <?= ($values['entry_type'] ?? 'solo') !== 'team' ? 'hidden' : '' ?>>
              <p class="team-help">Enter one teammate to start. Add as many more names as you need.</p>
              <div id="team-members">
                <?php foreach (!empty($values['team_members']) ? $values['team_members'] : [''] as $i => $name): ?>
                  <div class="field member-field"><label for="team-member-<?= (int) $i ?>">Teammate <?= (int) $i + 1 ?> name</label><div class="member-input"><input id="team-member-<?= (int) $i ?>" name="team_members[]" type="text" value="<?= e($name) ?>" minlength="2" maxlength="120" placeholder="Full name" <?= ($values['entry_type'] ?? 'solo') === 'team' ? 'required' : 'disabled' ?>><button type="button" class="remove-member" aria-label="Remove teammate <?= (int) $i + 1 ?>" <?= $i === 0 ? 'hidden' : '' ?>>Remove</button></div></div>
                <?php endforeach; ?>
              </div>
              <?= field_error($errors, 'team_members') ?>
              <button class="add-member" id="add-member" type="button">+ Add another teammate</button>
            </div>

            <div class="form-section-title"><h3>Lead participant details</h3></div>
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

            <div class="form-section-title second"><h3>Academic details</h3></div>
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
                <span>I agree to the use of my details for this registration and confirm any teammates have agreed to be named. Competition updates will go to the lead participant. <span class="required-mark" aria-hidden="true">*</span></span>
              </label>
              <?= field_error($errors, 'consent') ?>
              <p class="data-notice">Data use: We collect the lead’s contact and college details and any teammate names for registration. Only the lead’s contact receives updates.</p>
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

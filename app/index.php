<?php
declare(strict_types=1);
require __DIR__ . '/private/bootstrap.php';
require_once __DIR__ . '/private/database.php';
$sponsors = [];
try {
    $sponsors = database($config)->query('SELECT name, logo_path, website_url FROM sponsors ORDER BY sort_order, id')->fetchAll();
} catch (Throwable $exception) {
    error_log('Competition sponsors unavailable.');
}
$isOpen = ($config['REGISTRATION_OPEN'] ?? false) === true;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#10263b">
  <meta name="description" content="DezignBank Architecture Challenge 2026 — Noida & Greater Noida Edition. Explore the competition, meet the jury and register solo or with a team.">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&family=Playfair+Display:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="canonical" href="<?= e(canonical_url($config, 'index.php')) ?>">
  <link rel="stylesheet" href="<?= e(path_url($config, 'assets/style.css')) ?>?v=20261003">
  <script src="<?= e(path_url($config, 'assets/site.js')) ?>" defer></script>
  <title>DezignBank Architecture Challenge 2026</title>
</head>
<body class="home-page">
  <a class="skip-link" href="#main">Skip to content</a>
  <header class="home-header">
    <a class="home-brand" href="#top" aria-label="DezignBank competition home"><img src="<?= e(path_url($config, 'assets/dezignbank-mark.svg')) ?>" alt="" width="36" height="36"><span>DezignBank<small>CREATE &nbsp;|&nbsp; SHARE &nbsp;|&nbsp; GROW</small></span></a>
    <nav class="home-nav" aria-label="Main navigation"><a href="#about">Overview</a><a href="#rules">Rules</a><a href="#dates">Dates</a><a href="#jury">Jury</a><a href="#sponsors">Sponsors</a></nav>
    <a class="nav-register" href="<?= e(path_url($config, 'apply.php')) ?>">Register now <span aria-hidden="true">↗</span></a>
  </header>
  <main id="main">
    <section class="hero" id="top" aria-labelledby="hero-title">
      <div class="hero-copy">
        <div><p class="home-kicker"><span class="orange-line"></span> STUDENT DESIGN COMPETITION &nbsp; / &nbsp; 2026</p><h1 id="hero-title">Design the<br><em>next story.</em></h1></div>
        <div class="hero-side"><p>DezignBank Architecture Challenge</p><strong>Noida & Greater Noida Edition</strong><span>A platform for emerging architects to put their ideas forward.</span><a class="hero-register" href="<?= e(path_url($config, 'apply.php')) ?>"><?= $isOpen ? 'Register your entry' : 'Registration closed' ?> <span aria-hidden="true">↗</span></a></div>
      </div>
      <div class="carousel" aria-label="Architecture challenge imagery">
        <div class="carousel-stage">
          <figure class="carousel-slide is-active"><img src="<?= e(path_url($config, 'assets/award-certificate-quote.png')) ?>" alt="Architectural trophy and designed competition certificate beside a Zaha Hadid quotation: There are 360 degrees, so why stick to one?" fetchpriority="high"><figcaption><span>01 / 03</span> The ideas that shape tomorrow</figcaption></figure>
          <figure class="carousel-slide"><img src="<?= e(path_url($config, 'assets/adalaj-stepwell.jpg')) ?>" alt="Carved stone architecture of Adalaj Stepwell, Gujarat" loading="lazy"><figcaption><span>02 / 03</span> Heritage is an invitation to reimagine</figcaption></figure>
          <figure class="carousel-slide"><img src="<?= e(path_url($config, 'assets/hawa-mahal.jpg')) ?>" alt="The historic facade of Hawa Mahal in Jaipur, Rajasthan" loading="lazy"><figcaption><span>03 / 03</span> Great design stands across generations</figcaption></figure>
        </div>
        <div class="carousel-controls"><div class="carousel-dots" aria-label="Choose slide"><button type="button" class="is-active" aria-label="Show award slide" aria-current="true"></button><button type="button" aria-label="Show Adalaj Stepwell slide"></button><button type="button" aria-label="Show Hawa Mahal slide"></button></div><div class="carousel-arrows"><button type="button" data-direction="prev" aria-label="Previous slide">←</button><button type="button" data-direction="next" aria-label="Next slide">→</button></div></div>
      </div>
      <div class="hero-bottom"><span>OPEN TO ARCHITECTURE STUDENTS</span><span>SOLO & TEAM ENTRIES</span><span>SCROLL TO EXPLORE ↓</span></div>
    </section>
    <section class="overview section-pad" id="about"><div class="section-heading"><p class="home-kicker">01 / THE CHALLENGE</p><h2>Architecture begins<br><em>with a question.</em></h2></div><div class="overview-copy"><p>What can the next generation of designers contribute to the places we share? Bring your perspective, your curiosity and the courage to explore a different answer.</p><p>This edition brings students from Noida, Greater Noida and beyond into a conversation about architecture, heritage and what comes next.</p><a class="inline-link" href="<?= e(path_url($config, 'apply.php')) ?>">Join the challenge <span aria-hidden="true">↗</span></a></div></section>
    <section class="rules-section section-pad" id="rules"><div class="section-top"><p class="home-kicker">02 / PARTICIPATION</p><h2>How to take part<span class="accent-dot">.</span></h2><p class="section-note">Draft rules · We’ll update these before the final competition brief.</p></div><div class="rule-grid"><article><span>01</span><h3>Solo or together</h3><p>Enter on your own or register a team. One person should be the lead participant and contact for the team.</p></article><article><span>02</span><h3>Students first</h3><p>The challenge is intended for current architecture students. Keep your college and year of study details accurate.</p></article><article><span>03</span><h3>Original ideas</h3><p>Submit work created by you and your named teammates. Credit sources and collaborators wherever they are used.</p></article><article><span>04</span><h3>One clear entry</h3><p>Use one registration per lead email. Later design submission requirements will be shared with registered participants.</p></article></div><p class="rules-fine">The final brief may refine eligibility, team requirements, deliverables and judging criteria. Jury decisions on submitted work will be final.</p></section>
    <section class="dates-section section-pad" id="dates"><div class="section-top"><p class="home-kicker">03 / KEY DATES</p><h2>Mark the journey<span class="accent-dot">.</span></h2><p class="section-note">Illustrative dates only. The confirmed schedule will be announced later.</p></div><div class="date-list"><div><span>01</span><strong>Registration opens</strong><time datetime="2026-10-01">01 Oct 2026</time></div><div><span>02</span><strong>Registration closes</strong><time datetime="2026-10-31">31 Oct 2026</time></div><div><span>03</span><strong>Competition brief</strong><time datetime="2026-11-05">05 Nov 2026</time></div><div><span>04</span><strong>Design submission</strong><time datetime="2026-12-15">15 Dec 2026</time></div></div></section>
    <section class="jury-section section-pad" id="jury"><div class="section-top"><p class="home-kicker">04 / THE JURY</p><h2>Meet the minds<br><em>behind the review.</em></h2><p class="section-note">Experienced voices from architecture education.</p></div><div class="jury-grid"><article class="jury-card"><div class="jury-image"><img src="<?= e(path_url($config, 'assets/jury-devendra.jpg')) ?>" alt="Prof. (Dr.) Devendra Pratap Singh" loading="lazy"></div><div class="jury-copy"><span>01 / JURY MEMBER</span><h3>Prof. (Dr.) Devendra<br>Pratap Singh</h3><p>Dean · Amity School of Architecture & Planning</p><small>Amity University, Noida</small></div></article><article class="jury-card"><div class="jury-image"><img src="<?= e(path_url($config, 'assets/jury-ziauddin.jpg')) ?>" alt="Ar. Mohammad Ziauddin" loading="lazy"></div><div class="jury-copy"><span>02 / JURY MEMBER</span><h3>Ar. Mohammad<br>Ziauddin</h3><p>Associate Professor · Department of Architecture</p><small>Jamia Millia Islamia, New Delhi</small></div></article><article class="jury-card"><div class="jury-image"><img src="<?= e(path_url($config, 'assets/jury-anand.webp')) ?>" alt="Prof. Anand Khatri" loading="lazy"></div><div class="jury-copy"><span>03 / JURY MEMBER</span><h3>Prof. Anand<br>Khatri</h3><p>Director · School of Architecture & Planning</p><small>Apeejay Institute of Technology, Greater Noida</small></div></article></div></section>
    <section class="sponsors-section section-pad" id="sponsors"><div class="section-top"><p class="home-kicker">05 / OUR PARTNERS</p><h2>Supported by<span class="accent-dot">.</span></h2></div><?php if ($sponsors === []): ?><p class="sponsor-empty">Our partners will be announced here soon.</p><?php else: ?><div class="sponsor-grid"><?php foreach ($sponsors as $sponsor): ?><div class="sponsor-card"><?php if ($sponsor['website_url']): ?><a href="<?= e($sponsor['website_url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Visit <?= e($sponsor['name']) ?>"><?php endif; ?><img src="<?= e(path_url($config, $sponsor['logo_path'])) ?>" alt="<?= e($sponsor['name']) ?> logo" loading="lazy"><?php if ($sponsor['website_url']): ?></a><?php endif; ?><span><?= e($sponsor['name']) ?></span></div><?php endforeach; ?></div><?php endif; ?></section>
    <section class="closing-cta"><p class="home-kicker">YOUR NEXT CHAPTER STARTS HERE</p><h2>Have an idea worth<br><em>putting forward?</em></h2><a href="<?= e(path_url($config, 'apply.php')) ?>">Register for the challenge <span aria-hidden="true">↗</span></a></section>
  </main>
  <footer class="home-footer"><div><strong>DezignBank</strong><span>CREATE &nbsp;|&nbsp; SHARE &nbsp;|&nbsp; GROW</span></div><p>© <?= date('Y') ?> DezignBank · Architecture Challenge</p><div class="footer-credits">Photos: <a href="https://commons.wikimedia.org/wiki/File:Adalaj_Stepwell-Adalaj_Ahmedabad-Gujarat-IMG_1021.jpg">Adalaj · Shivajidesai29</a> and <a href="https://commons.wikimedia.org/wiki/File:Hawa_Mahal_at_Jaipur,_Rajasthan.jpg">Hawa Mahal · Aarshi Joshi</a>, <a href="https://creativecommons.org/licenses/by-sa/4.0/">CC BY-SA 4.0</a>. Jury portraits: <a href="https://www.amity.edu/faculty-detail.aspx?facultyID=3313">Amity</a>, <a href="https://jmi.ac.in/ACADEMICS/Departments/Department-Of-Architecture/Faculty-Members/2990/Mohammad_Ziauddin">JMI</a>, <a href="https://www.apeejay.edu/architecture/directors-message/">Apeejay</a>. <a href="https://www.zhfoundation.com/collections/the-world-89-degrees/">Quote source</a>.</div></footer>
</body>
</html>

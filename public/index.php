<?php
require __DIR__ . '/../includes/auth.php';
$user = current_user();

// Want your own photo? Save it as public/images/hero.jpg and it replaces the illustration automatically.
$heroPhoto = is_file(__DIR__ . '/images/hero.jpg') ? 'images/hero.jpg' : null;

$pageTitle = 'Student Consultation Booking';
require __DIR__ . '/../includes/header.php';
?>
<section class="hero mb-5">
  <div class="row align-items-center g-4">
    <div class="col-lg-6">
      <span class="eyebrow">Student consultation booking</span>
      <h1>Book faculty consultations in a few clicks.</h1>
      <p class="lead">
        See when your faculty are available, book a time with a topic, and follow every
        request from pending to completed, all in one place.
      </p>
      <div class="d-flex flex-wrap gap-2">
        <?php if ($user): ?>
          <a class="btn btn-primary btn-lg" href="dashboard.php">Go to my dashboard</a>
        <?php else: ?>
          <a class="btn btn-primary btn-lg" href="login.php">Log in</a>
          <a class="btn btn-outline-primary btn-lg" href="register.php">Create a student account</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-6">
      <?php if ($heroPhoto): ?>
        <img class="hero-photo" src="<?= e($heroPhoto) ?>" alt="Students and faculty on campus">
      <?php else: ?>
        <?php include __DIR__ . '/../includes/hero_art.php'; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="mb-5" aria-labelledby="how-title">
  <h2 id="how-title" class="landing-section-title h3 mb-4">How it works</h2>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card h-100"><div class="card-body">
        <div class="step-head"><span class="step-number">1</span><h3 class="h5">Faculty publish their time</h3></div>
        <p class="text-muted mb-0">Faculty add the dates and hours when students can book them, within school hours.</p>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card h-100"><div class="card-body">
        <div class="step-head"><span class="step-number">2</span><h3 class="h5">Students pick and book</h3></div>
        <p class="text-muted mb-0">Students search faculty, choose an open slot, and say what they want to discuss.</p>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card h-100"><div class="card-body">
        <div class="step-head"><span class="step-number">3</span><h3 class="h5">Approve and stay informed</h3></div>
        <p class="text-muted mb-0">Faculty approve or decline with remarks, and everyone gets a notification.</p>
      </div></div>
    </div>
  </div>
</section>

<section class="mb-5" aria-labelledby="why-title">
  <h2 id="why-title" class="landing-section-title h3 mb-4">Why ConsultEase</h2>
  <div class="row g-3">
    <div class="col-sm-6 col-lg-3">
      <div class="card h-100"><div class="card-body">
        <div class="feature-icon" aria-hidden="true">🔒</div>
        <h3 class="h6">No double bookings</h3>
        <p class="small text-muted mb-0">Each slot can only be booked once, even if two students try at the same moment.</p>
      </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card h-100"><div class="card-body">
        <div class="feature-icon" aria-hidden="true">📋</div>
        <h3 class="h6">A clear record</h3>
        <p class="small text-muted mb-0">See each request's status and the faculty's remarks, from pending to completed.</p>
      </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card h-100"><div class="card-body">
        <div class="feature-icon" aria-hidden="true">🔔</div>
        <h3 class="h6">Instant updates</h3>
        <p class="small text-muted mb-0">Notifications appear in the system whenever a request changes.</p>
      </div></div>
    </div>
    <div class="col-sm-6 col-lg-3">
      <div class="card h-100"><div class="card-body">
        <div class="feature-icon" aria-hidden="true">📱</div>
        <h3 class="h6">Works on any device</h3>
        <p class="small text-muted mb-0">A responsive layout for phones, tablets and computers, with a dark mode.</p>
      </div></div>
    </div>
  </div>
</section>

<section class="mb-4" aria-labelledby="who-title">
  <h2 id="who-title" class="landing-section-title h3 mb-4">Built for everyone involved</h2>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card role-card"><div class="card-body">
        <h3 class="h5">Students</h3>
        <ul class="text-muted">
          <li>Search faculty and filter by department</li>
          <li>Book an open slot with a topic</li>
          <li>Track status and cancel if plans change</li>
        </ul>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card role-card"><div class="card-body">
        <h3 class="h5">Faculty</h3>
        <ul class="text-muted">
          <li>Publish and manage availability</li>
          <li>Approve, decline or complete requests</li>
          <li>Add remarks to every consultation</li>
        </ul>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card role-card"><div class="card-body">
        <h3 class="h5">Administrators</h3>
        <ul class="text-muted">
          <li>Manage user accounts</li>
          <li>See consultation statistics in a chart</li>
          <li>Filter reports and download them as CSV</li>
        </ul>
      </div></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>

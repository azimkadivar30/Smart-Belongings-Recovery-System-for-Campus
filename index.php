<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Smart Belonging System | Campus Lost &amp; Found</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link
    href="https://fonts.googleapis.com/css2?family=Fraunces:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>

  <!-- ===== Navbar ===== -->
  <nav class="navbar navbar-expand-lg sbs-navbar sticky-top">
    <div class="container">
      <a class="navbar-brand" href="index.php">
        <span class="brand-tag"><i class="bi bi-qr-code"></i></span>
        Smart Belonging<span style="color:var(--mid)">System</span>
      </a>
      <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMain">
        <i class="bi bi-list fs-2" style="color:var(--deep)"></i>
      </button>
      <div class="collapse navbar-collapse" id="navMain">
        <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
          <li class="nav-item"><a class="nav-link" href="#how-it-works">How it works</a></li>
          <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
          <li class="nav-item ms-lg-3"><a class="btn btn-outline-brand" href="login.php">Login</a></li>
          <li class="nav-item ms-lg-2 mt-2 mt-lg-0"><a class="btn btn-brand" href="register.php">Register</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- ===== Hero ===== -->
  <header class="hero-section">
    <div class="container">
      <div class="row align-items-center g-5">
        <div class="col-lg-6">
          <span class="hero-eyebrow"><i class="bi bi-mortarboard"></i> Final Year Project · Campus Utility</span>
          <h1>Never lose track of what belongs to you on campus.</h1>
          <p class="lead">A centralized platform where students report lost or found items and the administration
            verifies, tracks and returns them — with QR-tagging for phones, laptops and other smart gadgets.</p>
          <div class="d-flex flex-wrap gap-3 mt-4">
            <a href="register.php" class="btn btn-brand btn-lg px-4"><i class="bi bi-person-plus me-2"></i>Get
              Started</a>
            <a href="login.php" class="btn btn-light-brand btn-lg px-4"><i class="bi bi-box-arrow-in-right me-2"></i>I
              already have an account</a>
          </div>
          <div class="d-flex gap-4 mt-5">
            <div>
              <div class="display-font fw-semibold" style="font-size:1.6rem;color:var(--deep)">24/7</div>
              <div class="text-secondary small">Report anytime</div>
            </div>
            <div>
              <div class="display-font fw-semibold" style="font-size:1.6rem;color:var(--deep)">100%</div>
              <div class="text-secondary small">Admin verified</div>
            </div>
            <div>
              <div class="display-font fw-semibold" style="font-size:1.6rem;color:var(--deep)">QR</div>
              <div class="text-secondary small">Gadget tagging</div>
            </div>
          </div>
        </div>
        <div class="col-lg-6 d-flex justify-content-center">
          <div class="tag-card">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="d-flex gap-3 align-items-center">
                <div class="tag-qr"></div>
                <div>
                  <div class="fw-semibold" style="color:var(--deep)">Dell Laptop — Bag 14"</div>
                  <div class="text-secondary small">Reported by Aditi R.</div>
                </div>
              </div>
              <span class="tag-status">Found</span>
            </div>
            <hr style="border-color:#EEF1EC">
            <div class="small text-secondary mb-1"><i class="bi bi-geo-alt me-1"></i> Central Library, 2nd Floor</div>
            <div class="small text-secondary mb-3"><i class="bi bi-calendar3 me-1"></i> Reported on 03 Jul 2026</div>
            <div class="d-flex justify-content-between align-items-center">
              <span class="small fw-semibold" style="color:var(--deep)">Collection Desk: Admin Office, Block A</span>
              <i class="bi bi-arrow-up-right-circle-fill" style="color:var(--mid);font-size:1.3rem"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- ===== Features ===== -->
  <section id="features" class="py-5 py-lg-6">
    <div class="container py-4">
      <div class="text-center mb-5">
        <div class="section-label">What it does</div>
        <h2 class="mt-2">One system, complete belonging lifecycle</h2>
      </div>
      <div class="row g-4">
        <div class="col-md-6 col-lg-3">
          <div class="feature-card h-100">
            <div class="feature-icon"><i class="bi bi-file-earmark-plus"></i></div>
            <h5 style="color:var(--deep)">Report Items</h5>
            <p class="text-secondary small mb-0">Students submit lost item details — name, description, location and
              date — in a few clicks.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card h-100">
            <div class="feature-icon"><i class="bi bi-qr-code-scan"></i></div>
            <h5 style="color:var(--deep)">QR Gadget Tags</h5>
            <p class="text-secondary small mb-0">Generate a QR code for phones and laptops so finders can identify the
              owner instantly.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card h-100">
            <div class="feature-icon"><i class="bi bi-clipboard-check"></i></div>
            <h5 style="color:var(--deep)">Admin Verification</h5>
            <p class="text-secondary small mb-0">The admin panel reviews every report and updates status: pending, found
              or not found.</p>
          </div>
        </div>
        <div class="col-md-6 col-lg-3">
          <div class="feature-card h-100">
            <div class="feature-icon"><i class="bi bi-envelope-check"></i></div>
            <h5 style="color:var(--deep)">Email Alerts</h5>
            <p class="text-secondary small mb-0">Students get notified automatically the moment their item's status
              changes.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== How it works ===== -->
  <section id="how-it-works" class="py-5" style="background:var(--white)">
    <div class="container py-4">
      <div class="row g-5 align-items-center">
        <div class="col-lg-5">
          <div class="section-label">Process</div>
          <h2 class="mt-2 mb-3">From lost to returned, in four steps</h2>
          <p class="text-secondary">The centralized workflow keeps students and administration on the same page
            throughout the recovery process.</p>
        </div>
        <div class="col-lg-7">
          <div class="step-item">
            <div class="step-num">01</div>
            <div>
              <h6 class="fw-bold mb-1" style="color:var(--deep)">Register & report</h6>
              <p class="text-secondary small mb-0">Create a student account and submit a lost item report with full
                details.</p>
            </div>
          </div>
          <div class="step-item">
            <div class="step-num">02</div>
            <div>
              <h6 class="fw-bold mb-1" style="color:var(--deep)">Admin reviews</h6>
              <p class="text-secondary small mb-0">The admin panel checks incoming reports against items handed in
                physically.</p>
            </div>
          </div>
          <div class="step-item">
            <div class="step-num">03</div>
            <div>
              <h6 class="fw-bold mb-1" style="color:var(--deep)">Status updates</h6>
              <p class="text-secondary small mb-0">Item status moves to found or not found, and the student is emailed
                instantly.</p>
            </div>
          </div>
          <div class="step-item">
            <div class="step-num">04</div>
            <div>
              <h6 class="fw-bold mb-1" style="color:var(--deep)">Collect your item</h6>
              <p class="text-secondary small mb-0">Collection details are shared in-app so the student can pick up their
                belonging.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ===== CTA ===== -->
  <section class="py-5">
    <div class="container py-4 text-center">
      <h2 class="mb-3">Ready to keep track of your belongings?</h2>
      <p class="text-secondary mb-4">Join your campus's centralized lost &amp; found network today.</p>
      <a href="register.php" class="btn btn-brand btn-lg px-5">Create your account</a>
    </div>
  </section>

  <!-- ===== Footer ===== -->
  <footer class="sbs-footer">
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <div><i class="bi bi-qr-code me-2"></i>Smart Belonging System for Campus</div>
      <div class="small">Final Year Major Project · 7th Semester</div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
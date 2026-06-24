<?php
session_start();
// The public landing page reads only the session — no database needed,
// so it stays online even if MySQL is temporarily unavailable.

// Send logged-in users straight to their dashboard.
if (isset($_SESSION['user_id']) && isset($_SESSION['user']['role'])) {
    switch ($_SESSION['user']['role']) {
        case 'admin':   header("Location: admin/dashboard.php"); exit();
        case 'doctor':  header("Location: doctor/doctor_dashboard.php"); exit();
        case 'patient': header("Location: patient/patient_dashboard.php"); exit();
        default: session_destroy();
    }
}
$year = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="IHMS — an integrated healthcare platform connecting patients, doctors and hospitals. Book appointments, manage records, request blood and reach emergency care, all in one secure place.">
  <title>IHMS — Integrated Healthcare, Simplified</title>

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">

  <!-- Reveal-on-scroll (AOS) -->
  <link href="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.css" rel="stylesheet">

  <!-- Theme + page styles -->
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

<!-- ======================= NAVBAR ======================= -->
<nav class="nav" id="nav">
  <div class="container nav__inner">
    <a href="index.php" class="brand">
      <span class="brand__mark"><i data-lucide="heart-pulse"></i></span>
      <span>IHMS<small>Healthcare</small></span>
    </a>

    <div class="nav__menu" id="navMenu">
      <div class="nav__links">
        <a href="#home">Home</a>
        <a href="#features">Features</a>
        <a href="#specialties">Specialties</a>
        <a href="#doctors">Doctors</a>
        <a href="#contact">Contact</a>
      </div>
      <div class="nav__cta">
        <a href="login.php" class="btn btn--ghost"><i data-lucide="log-in"></i> Sign In</a>
        <a href="register.php" class="btn btn--brand"><i data-lucide="user-plus"></i> Get Started</a>
      </div>
    </div>

    <button class="nav__toggle" id="navToggle" aria-label="Open menu" aria-expanded="false">
      <i data-lucide="menu"></i>
    </button>
  </div>
</nav>

<!-- ======================= HERO ======================= -->
<header class="hero" id="home">
  <div class="hero__dots"></div>
  <div class="container">
    <div class="hero__grid">
      <div class="hero__copy" data-aos="fade-up">
        <span class="eyebrow"><i data-lucide="sparkles"></i> Trusted by 25,000+ patients</span>
        <h1>Your whole health,<br><span class="text-grad">beautifully connected.</span></h1>
        <p class="hero__lead">
          IHMS brings appointments, medical records, prescriptions, blood-bank requests and
          emergency care into one secure platform — for patients, doctors and hospitals alike.
        </p>
        <div class="hero__actions">
          <a href="register.php" class="btn btn--brand btn--lg"><i data-lucide="calendar-check"></i> Book an Appointment</a>
          <a href="#platform" class="btn btn--ghost btn--lg"><i data-lucide="play-circle"></i> See how it works</a>
        </div>
        <div class="hero__trust">
          <div class="avatars">
            <img src="assets/img/doctor-2.jpg" alt="Patient">
            <img src="assets/img/patient-1.jpg" alt="Patient">
            <img src="assets/img/patient-2.jpg" alt="Patient">
            <img src="assets/img/doctor-4.jpg" alt="Patient">
          </div>
          <div>
            <span class="stars">
              <i data-lucide="star"></i><i data-lucide="star"></i><i data-lucide="star"></i><i data-lucide="star"></i><i data-lucide="star"></i>
            </span>
            <small><b>4.9/5</b> from 3,200+ verified reviews</small>
          </div>
        </div>
      </div>

      <div class="hero__visual" data-aos="fade-left" data-aos-delay="150">
        <span class="hero__blob"></span>
        <span class="hero__ring"></span>
        <div class="hero__photo">
          <img src="assets/img/hero-main.jpg" alt="Doctor consulting with a patient" loading="eager">
        </div>
        <div class="float-card float-card--tl">
          <span class="fc-ico fc-ico--green"><i data-lucide="heart-pulse"></i></span>
          <div><b>98%</b><span>Patient satisfaction</span></div>
        </div>
        <div class="float-card float-card--br">
          <span class="fc-ico fc-ico--accent"><i data-lucide="calendar-check"></i></span>
          <div><b>Booked</b><span>Dr. Edwards · 10:00 AM</span></div>
        </div>
      </div>
    </div>
  </div>
</header>

<!-- ======================= TRUST STRIP ======================= -->
<section class="trust-strip">
  <div class="container">
    <div class="trust-item" data-aos="fade-up">
      <i data-lucide="shield-check"></i>
      <div><b>HIPAA-ready</b><span>Encrypted &amp; private</span></div>
    </div>
    <div class="trust-item" data-aos="fade-up" data-aos-delay="80">
      <i data-lucide="clock"></i>
      <div><b>24/7 access</b><span>Care that never sleeps</span></div>
    </div>
    <div class="trust-item" data-aos="fade-up" data-aos-delay="160">
      <i data-lucide="users"></i>
      <div><b>320+ specialists</b><span>Across 40 departments</span></div>
    </div>
    <div class="trust-item" data-aos="fade-up" data-aos-delay="240">
      <i data-lucide="award"></i>
      <div><b>Award-winning</b><span>Top-rated care 2025</span></div>
    </div>
  </div>
</section>

<!-- ======================= FEATURES ======================= -->
<section class="section" id="features">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <span class="eyebrow"><i data-lucide="layout-grid"></i> Everything in one place</span>
      <h2 class="section-title">A complete platform for modern care</h2>
      <p class="section-sub">From the first booking to follow-up care, every tool your hospital and patients need — thoughtfully designed and seamlessly integrated.</p>
    </div>

    <div class="grid-3">
      <?php
        $features = [
          ['calendar-check','Smart Appointments','Book, reschedule and track visits in seconds with real-time doctor availability.'],
          ['file-text','Digital Records','Secure medical history, lab reports and visit summaries, accessible anytime.'],
          ['stethoscope','Doctor &amp; Patient Portals','Tailored dashboards for admins, doctors and patients — everyone stays in sync.'],
          ['droplet','Blood Bank','Search live blood stock, register donors and raise urgent requests instantly.'],
          ['bot','AI Health Assistant','A built-in chatbot answers questions and guides patients to the right care.'],
          ['bell','Reminders &amp; Alerts','Automated notifications for appointments, prescriptions and reports.'],
        ];
        $d = 0;
        foreach ($features as $f):
      ?>
      <div class="feature" data-aos="fade-up" data-aos-delay="<?= $d ?>">
        <div class="feature__ico"><i data-lucide="<?= $f[0] ?>"></i></div>
        <h3><?= $f[1] ?></h3>
        <p><?= $f[2] ?></p>
      </div>
      <?php $d += 80; endforeach; ?>
    </div>
  </div>
</section>

<!-- ======================= PLATFORM PREVIEW ======================= -->
<section class="section section--tint" id="platform">
  <div class="container">
    <div class="platform__grid">
      <div class="platform__copy" data-aos="fade-right">
        <span class="eyebrow"><i data-lucide="monitor"></i> One dashboard, total control</span>
        <h2 class="section-title">Manage your hospital from a single, elegant workspace</h2>
        <p class="muted">No more juggling spreadsheets and paperwork. IHMS gives every role a clean, role-aware dashboard so the right people see the right information at the right time.</p>
        <ul class="checklist">
          <li>
            <span class="ck"><i data-lucide="check"></i></span>
            <div><b>Role-based dashboards</b><span>Admins, doctors and patients each get a focused view.</span></div>
          </li>
          <li>
            <span class="ck"><i data-lucide="check"></i></span>
            <div><b>Real-time scheduling</b><span>Live availability prevents double-booking and long waits.</span></div>
          </li>
          <li>
            <span class="ck"><i data-lucide="check"></i></span>
            <div><b>Reports &amp; insights</b><span>Track appointments, revenue and patient flow at a glance.</span></div>
          </li>
        </ul>
        <a href="register.php" class="btn btn--brand" style="margin-top:1.6rem"><i data-lucide="arrow-right"></i> Start free today</a>
      </div>

      <div class="platform__media" data-aos="zoom-in" data-aos-delay="120">
        <div class="browser">
          <div class="browser__bar">
            <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            <span class="url">app.ihms.health/dashboard</span>
          </div>
          <img src="assets/img/about-team.jpg" alt="IHMS care team using the platform">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ======================= SPECIALTIES ======================= -->
<section class="section" id="specialties">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <span class="eyebrow"><i data-lucide="activity"></i> Departments</span>
      <h2 class="section-title">Specialised care, all under one roof</h2>
      <p class="section-sub">Connect with experienced specialists across the departments that matter most to your health.</p>
    </div>

    <div class="grid-3">
      <?php
        $depts = [
          ['heart-pulse','Cardiology','Heart screenings, diagnostics and ongoing cardiac care.'],
          ['brain','Neurology','Expert care for the brain, spine and nervous system.'],
          ['baby','Pediatrics','Gentle, attentive healthcare for infants and children.'],
          ['bone','Orthopedics','Bone, joint and mobility treatment that gets you moving.'],
          ['smile','Dentistry','Comprehensive dental care and confident, healthy smiles.'],
          ['ambulance','Emergency','Round-the-clock emergency response and ambulance booking.'],
        ];
        $d = 0;
        foreach ($depts as $s):
      ?>
      <div class="dept" data-aos="fade-up" data-aos-delay="<?= $d ?>">
        <div class="dept__ico"><i data-lucide="<?= $s[0] ?>"></i></div>
        <h3><?= $s[1] ?></h3>
        <p><?= $s[2] ?></p>
        <a href="register.php" class="dept__more">Learn more <i data-lucide="arrow-right"></i></a>
      </div>
      <?php $d += 70; endforeach; ?>
    </div>
  </div>
</section>

<!-- ======================= HOW IT WORKS ======================= -->
<section class="section section--soft">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <span class="eyebrow"><i data-lucide="route"></i> How it works</span>
      <h2 class="section-title">Care in four simple steps</h2>
      <p class="section-sub">Getting the treatment you need has never been this straightforward.</p>
    </div>

    <div class="steps">
      <?php
        $steps = [
          ['Create account','Sign up as a patient in under a minute — no paperwork.'],
          ['Find a doctor','Browse specialists by department, rating and availability.'],
          ['Book a slot','Pick a time that suits you and confirm instantly.'],
          ['Get care','Attend your visit, then track records and prescriptions online.'],
        ];
        $n = 1;
        foreach ($steps as $st):
      ?>
      <div class="step" data-aos="fade-up" data-aos-delay="<?= ($n-1)*100 ?>">
        <div class="step__num"><?= $n ?></div>
        <h3><?= $st[0] ?></h3>
        <p><?= $st[1] ?></p>
      </div>
      <?php $n++; endforeach; ?>
    </div>
  </div>
</section>

<!-- ======================= STATS BAND ======================= -->
<section class="section stats">
  <div class="container">
    <div class="stats__grid">
      <div class="stat" data-aos="fade-up">
        <div class="stat__num"><span class="text-grad-light" data-count="25" data-suffix="k+">0</span></div>
        <div class="stat__label">Patients served</div>
      </div>
      <div class="stat" data-aos="fade-up" data-aos-delay="100">
        <div class="stat__num"><span class="text-grad-light" data-count="320" data-suffix="+">0</span></div>
        <div class="stat__label">Specialist doctors</div>
      </div>
      <div class="stat" data-aos="fade-up" data-aos-delay="200">
        <div class="stat__num"><span class="text-grad-light" data-count="40" data-suffix="+">0</span></div>
        <div class="stat__label">Departments</div>
      </div>
      <div class="stat" data-aos="fade-up" data-aos-delay="300">
        <div class="stat__num"><span class="text-grad-light" data-count="99.9" data-suffix="%">0</span></div>
        <div class="stat__label">Platform uptime</div>
      </div>
    </div>
  </div>
</section>

<!-- ======================= DOCTORS ======================= -->
<section class="section" id="doctors">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <span class="eyebrow"><i data-lucide="stethoscope"></i> Meet the team</span>
      <h2 class="section-title">Specialists you can trust</h2>
      <p class="section-sub">Highly rated, board-certified doctors dedicated to your wellbeing.</p>
    </div>

    <div class="grid-4">
      <?php
        $docs = [
          ['doctor-1.jpg','Dr. Ralph Edwards','Dermatologist','4.9'],
          ['doctor-2.jpg','Dr. Amara Okoye','Cardiologist','5.0'],
          ['doctor-3.jpg','Dr. Daniel Cruz','Neurologist','4.8'],
          ['doctor-4.jpg','Dr. Hana Suzuki','Pediatrician','4.9'],
        ];
        $d = 0;
        foreach ($docs as $doc):
      ?>
      <div class="doc" data-aos="fade-up" data-aos-delay="<?= $d ?>">
        <div class="doc__photo">
          <img src="assets/img/<?= $doc[0] ?>" alt="<?= $doc[1] ?>">
          <div class="doc__social">
            <a href="#" aria-label="Facebook"><i data-lucide="facebook"></i></a>
            <a href="#" aria-label="Twitter"><i data-lucide="twitter"></i></a>
            <a href="#" aria-label="LinkedIn"><i data-lucide="linkedin"></i></a>
          </div>
        </div>
        <div class="doc__body">
          <h3><?= $doc[1] ?></h3>
          <div class="doc__spec"><?= $doc[2] ?></div>
          <div class="doc__rate">
            <span class="stars"><i data-lucide="star"></i></span> <?= $doc[3] ?> · 200+ reviews
          </div>
        </div>
      </div>
      <?php $d += 90; endforeach; ?>
    </div>
  </div>
</section>

<!-- ======================= TESTIMONIALS ======================= -->
<section class="section section--tint">
  <div class="container">
    <div class="section-head" data-aos="fade-up">
      <span class="eyebrow"><i data-lucide="message-circle"></i> Patient stories</span>
      <h2 class="section-title">Loved by patients and providers</h2>
    </div>

    <div class="grid-3">
      <?php
        $reviews = [
          ['patient-1.jpg','Sophia Martin','Patient','Booking an appointment took less than a minute and my records were ready before I arrived. This is how healthcare should feel.'],
          ['patient-2.jpg','James Carter','Patient','The blood-bank request feature helped my family during an emergency. Fast, clear and genuinely life-saving.'],
          ['patient-3.jpg','Daniel Lee','Patient','As someone who hates paperwork, having every prescription and report in one place is a relief. Beautifully simple.'],
        ];
        $d = 0;
        foreach ($reviews as $r):
      ?>
      <div class="testi" data-aos="fade-up" data-aos-delay="<?= $d ?>">
        <span class="testi__quote">&rdquo;</span>
        <div class="stars">
          <i data-lucide="star"></i><i data-lucide="star"></i><i data-lucide="star"></i><i data-lucide="star"></i><i data-lucide="star"></i>
        </div>
        <p><?= $r[3] ?></p>
        <div class="testi__who">
          <img src="assets/img/<?= $r[0] ?>" alt="<?= $r[1] ?>">
          <div><b><?= $r[1] ?></b><span><?= $r[2] ?></span></div>
        </div>
      </div>
      <?php $d += 100; endforeach; ?>
    </div>
  </div>
</section>

<!-- ======================= CTA ======================= -->
<section class="section cta">
  <div class="container">
    <div class="cta__box" data-aos="zoom-in">
      <span class="eyebrow" style="background:rgba(255,255,255,.14); color:#fff; border-color:rgba(255,255,255,.2)"><i data-lucide="heart-handshake"></i> Join IHMS today</span>
      <h2>Ready to take control of your health?</h2>
      <p>Create a free account and experience healthcare that's organised, accessible and built around you.</p>
      <div class="cta__actions">
        <a href="register.php" class="btn btn--light btn--lg"><i data-lucide="user-plus"></i> Create free account</a>
        <a href="login.php" class="btn btn--outline-light btn--lg"><i data-lucide="log-in"></i> Sign in</a>
      </div>
    </div>
  </div>
</section>

<!-- ======================= FOOTER ======================= -->
<footer class="footer" id="contact">
  <div class="container">
    <div class="footer__grid">
      <div>
        <a href="index.php" class="brand">
          <span class="brand__mark"><i data-lucide="heart-pulse"></i></span>
          <span>IHMS<small>Healthcare</small></span>
        </a>
        <p style="margin-top:1rem; max-width:22rem">An integrated healthcare management platform connecting patients, doctors and hospitals with secure, modern tools.</p>
        <div class="socials">
          <a href="#" aria-label="Facebook"><i data-lucide="facebook"></i></a>
          <a href="#" aria-label="Twitter"><i data-lucide="twitter"></i></a>
          <a href="#" aria-label="Instagram"><i data-lucide="instagram"></i></a>
          <a href="#" aria-label="LinkedIn"><i data-lucide="linkedin"></i></a>
        </div>
      </div>

      <div>
        <h5>Platform</h5>
        <ul class="footer__links">
          <li><a href="#features">Features</a></li>
          <li><a href="#specialties">Specialties</a></li>
          <li><a href="#doctors">Doctors</a></li>
          <li><a href="register.php">Get Started</a></li>
        </ul>
      </div>

      <div>
        <h5>Services</h5>
        <ul class="footer__links">
          <li><a href="register.php">Appointments</a></li>
          <li><a href="register.php">Medical Records</a></li>
          <li><a href="register.php">Blood Bank</a></li>
          <li><a href="register.php">Emergency Care</a></li>
        </ul>
      </div>

      <div>
        <h5>Stay in touch</h5>
        <ul class="foot-contact">
          <li><i data-lucide="mail"></i> contact@ihms.health</li>
          <li><i data-lucide="phone"></i> +1 (234) 567-890</li>
          <li><i data-lucide="map-pin"></i> 123 Healthcare Avenue, Medical District</li>
        </ul>
        <form class="newsletter" action="#">
          <input type="email" placeholder="Your email address" aria-label="Email" required>
          <button type="submit" class="btn btn--brand" aria-label="Subscribe"><i data-lucide="send"></i></button>
        </form>
      </div>
    </div>

    <div class="footer__bottom">
      <p>&copy; <?= $year ?> IHMS — Integrated Healthcare Management System. All rights reserved.</p>
      <p><a href="#home">Privacy</a> &nbsp;·&nbsp; <a href="#home">Terms</a> &nbsp;·&nbsp; <a href="#home">Security</a></p>
    </div>
  </div>
</footer>

<button class="to-top" aria-label="Back to top"><i data-lucide="chevron-up"></i></button>

<!-- Scripts -->
<script src="https://unpkg.com/lucide@latest"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="assets/js/landing.js"></script>
</body>
</html>

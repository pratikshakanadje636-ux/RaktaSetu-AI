<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | RaktaSetu</title>

    <style>
        :root {
            --red: #c51f32;
            --red-dark: #a81729;
            --red-soft: #fff0f2;
            --ink: #202938;
            --muted: #6f7b8b;
            --line: #e8ebf0;
            --surface: #ffffff;
            --page: #f6f8fb;
            --green: #17835d;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: "Segoe UI", Arial, sans-serif;
            color: var(--ink);
            background: var(--page);
        }

        .topbar {
            min-height: 36px;
            padding: 8px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: #fff;
            background: var(--red);
            font-size: 12px;
        }

        .topbar .service-name { font-weight: 600; letter-spacing: .1px; }
        .topbar .tagline-top { opacity: .92; }

        .navbar {
            min-height: 86px;
            padding: 16px 6%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            background: #fff;
            border-bottom: 1px solid var(--line);
        }

        .brand {
            color: var(--ink);
            text-decoration: none;
            font-size: 27px;
            line-height: 1.1;
            font-weight: 850;
            letter-spacing: -1.1px;
        }

        .brand span { color: var(--red); }

        .tagline {
            display: block;
            margin-top: 5px;
            color: var(--muted);
            font-size: 11px;
            font-weight: 400;
            letter-spacing: .1px;
        }

        .nav-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 42px;
            padding: 0 17px;
            border: 1px solid #f0c5cb;
            border-radius: 9px;
            background: #fff;
            color: var(--red-dark);
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            transition: background .2s, border-color .2s, transform .2s;
        }

        .nav-button:hover {
            background: var(--red-soft);
            border-color: #e5a2ac;
            transform: translateY(-1px);
        }

        .main-content {
            flex: 1;
            padding: 52px 22px;
            display: grid;
            place-items: center;
            background:
                radial-gradient(ellipse at 12% 15%, rgba(255, 224, 229, .72), transparent 34%),
                radial-gradient(ellipse at 88% 88%, rgba(255, 232, 235, .62), transparent 32%),
                var(--page);
        }

        .login-container {
            width: min(100%, 980px);
            display: grid;
            grid-template-columns: .92fr 1.08fr;
            overflow: hidden;
            background: var(--surface);
            border: 1px solid #e9edf2;
            border-radius: 20px;
            box-shadow: 0 22px 60px rgba(29, 41, 57, .09);
        }

        /* Soft information panel: avoids a large, heavy dark-red block. */
        .login-info {
            position: relative;
            overflow: hidden;
            padding: 46px 42px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            background: linear-gradient(145deg, #fff7f8 0%, #fff 78%);
            border-right: 1px solid #f2e6e8;
        }

        .login-info::before {
            content: "";
            position: absolute;
            width: 250px;
            height: 250px;
            right: -145px;
            top: -135px;
            border: 1px solid rgba(197, 31, 50, .12);
            border-radius: 50%;
        }

        .login-info::after {
            content: "";
            position: absolute;
            width: 210px;
            height: 210px;
            left: -145px;
            bottom: -145px;
            border: 1px solid rgba(197, 31, 50, .10);
            border-radius: 50%;
        }

        .brand-mark {
            position: relative;
            z-index: 1;
            width: 58px;
            height: 58px;
            display: grid;
            place-items: center;
            margin-bottom: 25px;
            border-radius: 16px;
            color: var(--red);
            background: #fff;
            border: 1px solid #f2d8dc;
            box-shadow: 0 7px 20px rgba(197, 31, 50, .08);
        }

        .brand-mark svg { width: 30px; height: 30px; }

        .info-kicker {
            position: relative;
            z-index: 1;
            margin-bottom: 10px;
            color: var(--red);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .login-info h1 {
            position: relative;
            z-index: 1;
            max-width: 340px;
            margin-bottom: 14px;
            color: var(--ink);
            font-size: clamp(27px, 3vw, 34px);
            line-height: 1.18;
            letter-spacing: -1.1px;
        }

        .login-info h1 span { color: var(--red); }

        .login-info > p {
            position: relative;
            z-index: 1;
            max-width: 350px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.85;
        }

        .info-features {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 13px;
            margin-top: 28px;
            padding-top: 23px;
            border-top: 1px solid #f0dfe2;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 11px;
            color: #465263;
            font-size: 12px;
            font-weight: 600;
        }

        .feature-icon {
            flex: 0 0 29px;
            width: 29px;
            height: 29px;
            display: grid;
            place-items: center;
            border-radius: 9px;
            color: var(--red);
            background: #ffe9ec;
            font-size: 14px;
        }

        .login-form-section {
            padding: 48px clamp(28px, 5vw, 58px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 12px;
            color: var(--red);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .eyebrow::before {
            content: "";
            width: 20px;
            height: 2px;
            border-radius: 2px;
            background: var(--red);
        }

        .login-form-section h2 {
            margin-bottom: 9px;
            font-size: 30px;
            line-height: 1.2;
            letter-spacing: -.9px;
        }

        .description {
            margin-bottom: 29px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.75;
        }

        .form-group { margin-bottom: 20px; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #303b4a;
            font-size: 12px;
            font-weight: 750;
        }

        .input-wrapper { position: relative; }

        .input-wrapper input {
            width: 100%;
            min-height: 49px;
            padding: 13px 15px;
            border: 1px solid #dfe4eb;
            border-radius: 9px;
            outline: none;
            background: #fbfcfe;
            color: var(--ink);
            font: inherit;
            font-size: 13px;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .input-wrapper input::placeholder { color: #9aa3b1; }

        .input-wrapper input:focus {
            border-color: #d65b69;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(197, 31, 50, .08);
        }

        .input-wrapper input.password-field { padding-right: 68px; }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 13px;
            transform: translateY(-50%);
            padding: 5px 2px;
            border: 0;
            background: transparent;
            color: var(--red);
            font-size: 12px;
            font-weight: 750;
            cursor: pointer;
        }

        .password-toggle:focus-visible,
        .login-button:focus-visible,
        .nav-button:focus-visible {
            outline: 3px solid rgba(197, 31, 50, .25);
            outline-offset: 3px;
        }

        .login-button {
            width: 100%;
            min-height: 50px;
            margin-top: 3px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 9px;
            background: var(--red);
            color: white;
            font-size: 13px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 8px 18px rgba(197, 31, 50, .16);
            transition: background .2s, transform .2s, box-shadow .2s;
        }

        .login-button:hover {
            background: var(--red-dark);
            transform: translateY(-1px);
            box-shadow: 0 11px 22px rgba(197, 31, 50, .20);
        }

        .secure-note {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 7px;
            margin-top: 20px;
            color: #7c8795;
            font-size: 11px;
        }

        .secure-note svg { width: 14px; height: 14px; color: var(--green); }

        .footer {
            padding: 16px 6%;
            display: flex;
            justify-content: space-between;
            gap: 15px;
            background: #fff;
            border-top: 1px solid var(--line);
            color: #7a8492;
            font-size: 11px;
        }

        .footer strong { color: var(--red); font-weight: 700; }

        @media (max-width: 760px) {
            .topbar { justify-content: center; text-align: center; }
            .tagline-top { display: none; }
            .navbar { min-height: 76px; padding: 14px 5%; }
            .brand { font-size: 24px; }
            .main-content { padding: 30px 16px; }
            .login-container { max-width: 500px; grid-template-columns: 1fr; }
            .login-info { padding: 30px 30px 27px; border-right: 0; border-bottom: 1px solid #f2e6e8; }
            .brand-mark { width: 48px; height: 48px; margin-bottom: 18px; border-radius: 14px; }
            .brand-mark svg { width: 25px; height: 25px; }
            .login-info h1 { font-size: 27px; }
            .login-info > p { font-size: 13px; }
            .info-features { grid-template-columns: 1fr; gap: 10px; margin-top: 19px; padding-top: 17px; }
            .login-form-section { padding: 32px 30px 35px; }
            .login-form-section h2 { font-size: 27px; }
            .footer { flex-direction: column; align-items: center; text-align: center; }
        }

        @media (max-width: 390px) {
            .navbar { gap: 10px; }
            .brand { font-size: 21px; }
            .tagline { font-size: 10px; }
            .nav-button { min-height: 38px; padding: 0 12px; font-size: 12px; }
            .login-info { padding: 26px 23px 23px; }
            .login-form-section { padding: 28px 23px 30px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { transition: none !important; }
        }
    </style>
</head>

<body>
    <div class="topbar">
        <span class="service-name">♥ &nbsp; RaktaSetu Blood Services</span>
        <span class="tagline-top">Connecting Blood, Saving Lives</span>
    </div>

    <nav class="navbar" aria-label="Main navigation">
        <a href="index.php" class="brand">
            Rakta<span>Setu</span>
            <small class="tagline">Connecting Blood, Saving Lives</small>
        </a>

        <a href="index.php" class="nav-button">
            <span aria-hidden="true">⌂</span> Home
        </a>
    </nav>

    <main class="main-content">
        <div class="login-container">
            <section class="login-info" aria-labelledby="welcome-title">
                <div class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M16 3.5C13.7 8.1 7 14.3 7 20a9 9 0 0 0 18 0c0-5.7-6.7-11.9-9-16.5Z" fill="currentColor" opacity=".14"/>
                        <path d="M16 3.5C13.7 8.1 7 14.3 7 20a9 9 0 0 0 18 0c0-5.7-6.7-11.9-9-16.5Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M11.4 20.2a4.7 4.7 0 0 0 4.7 4.7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </div>

                <div class="info-kicker">RaktaSetu administration</div>
                <h1 id="welcome-title">Care starts with <span>connection.</span></h1>
                <p>
                    Manage donor information, review blood requests, and monitor
                    blood inventory from one secure administration workspace.
                </p>

                <div class="info-features">
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">♥</span>
                        <span>Donor and recipient coordination</span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">▤</span>
                        <span>Blood stock and request management</span>
                    </div>
                    <div class="feature">
                        <span class="feature-icon" aria-hidden="true">✓</span>
                        <span>Restricted access for administrators</span>
                    </div>
                </div>
            </section>

            <section class="login-form-section" aria-labelledby="login-title">
                <div class="eyebrow">Secure access</div>
                <h2 id="login-title">Admin Login</h2>
                <p class="description">
                    Enter your administrator credentials to continue to your dashboard.
                </p>

                <form action="admin_check.php" method="POST">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <input
                                type="text"
                                id="username"
                                name="username"
                                placeholder="Enter your username"
                                autocomplete="username"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input
                                class="password-field"
                                type="password"
                                id="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                            >
                            <button
                                type="button"
                                class="password-toggle"
                                id="togglePassword"
                                aria-controls="password"
                                aria-label="Show password"
                            >Show</button>
                        </div>
                    </div>

                    <button type="submit" class="login-button">
                        Login to Dashboard <span aria-hidden="true">→</span>
                    </button>
                </form>

                <div class="secure-note">
                    <svg viewBox="0 0 20 20" fill="none" aria-hidden="true">
                        <rect x="4.2" y="8.3" width="11.6" height="8.5" rx="1.8" stroke="currentColor" stroke-width="1.5"/>
                        <path d="M6.8 8.3V5.9a3.2 3.2 0 0 1 6.4 0v2.4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        <path d="M10 11.5v2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    </svg>
                    Authorized administrators only
                </div>
            </section>
        </div>
    </main>

    <footer class="footer">
        <span>© <?php echo date('Y'); ?> <strong>RaktaSetu</strong></span>
        <span>Made with ♥ to support blood donation.</span>
    </footer>

    <script>
        const passwordInput = document.getElementById("password");
        const togglePassword = document.getElementById("togglePassword");

        togglePassword.addEventListener("click", function () {
            const isHidden = passwordInput.type === "password";
            passwordInput.type = isHidden ? "text" : "password";
            togglePassword.textContent = isHidden ? "Hide" : "Show";
            togglePassword.setAttribute("aria-label", isHidden ? "Hide password" : "Show password");
        });
    </script>
</body>
</html>

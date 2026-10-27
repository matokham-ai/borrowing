<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Sign In | LendFlow</title>

    @vite([
        'resources/css/admin.css',
        'resources/js/admin.js'
    ])
</head>

<body class="login-page">
<main class="login-shell">
    <section class="login-showcase">
        <div class="login-showcase-top">
            <div class="login-brand login-brand-light">
                <div class="login-brand-icon"><i class="bi bi-layers-fill"></i></div>
                <span>LendFlow</span>
            </div>
            <span class="login-status"><span></span> Secure workspace</span>
        </div>
        <div class="login-showcase-copy">
            <p class="login-kicker">LENDING, REFINED</p>
            <h1>Make every lending decision feel effortless.</h1>
            <p>One calm workspace for your portfolio, borrowers, and next best action.</p>
        </div>
        <div class="login-insight">
            <div class="insight-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div><strong>Portfolio intelligence</strong><span>See the signal behind every number.</span></div>
            <i class="bi bi-arrow-up-right"></i>
        </div>
    </section>

    <section class="login-panel">
        <div class="login-panel-inner">
            <div class="login-mobile-brand login-brand">
                <div class="login-brand-icon"><i class="bi bi-layers-fill"></i></div>
                <span>LendFlow</span>
            </div>
            <div class="login-heading">
                <p class="login-kicker">WELCOME BACK</p>
                <h2>Sign in to your workspace</h2>
                <p>Enter your details to continue to the dashboard.</p>
            </div>

        @if ($errors->any())
            <div class="alert alert-danger login-alert">
                <i class="bi bi-exclamation-circle me-2"></i>

                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login.attempt') }}">

            @csrf

            <div class="login-field">
                <label for="email" class="form-label">Email address</label>
                <div class="login-input">
                    <i class="bi bi-envelope"></i>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           placeholder="you@example.com" autocomplete="email" required autofocus>
                </div>
            </div>
            <div class="login-field">
                <div class="login-label-row">
                    <label for="password" class="form-label">Password</label>
                    <span>Protected access</span>
                </div>
                <div class="login-input">
                    <i class="bi bi-lock"></i>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="Enter your password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Show password">
                        <i class="bi bi-eye" id="passwordIcon"></i>
                    </button>
                </div>
            </div>
            <div class="login-options">
                <label class="login-check"><input type="checkbox" id="remember" name="remember" value="1"><span></span> Remember me</label>
                <span class="login-help">Need help?</span>
            </div>
            <button type="submit" class="login-button">Sign in <i class="bi bi-arrow-up-right"></i></button>

        </form>
        <div class="login-footer"><i class="bi bi-shield-check"></i> Your connection is encrypted and secure</div>
        </div>
    </div>
</section>
</main>

<script>
    function togglePassword() {

        const password = document.getElementById('password');
        const icon = document.getElementById('passwordIcon');

        if (password.type === 'password') {

            password.type = 'text';

            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');

        } else {

            password.type = 'password';

            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
</script>

</body>
</html>

<section class="login-panel">
    <div class="login-copy">
        <div class="login-orb orb-one"></div>
        <div class="login-orb orb-two"></div>
        <div class="login-orb orb-three"></div>
        <div class="login-copy-inner">
            <a class="login-logo" href="<?= e(app_url('login')); ?>">FlowForm <span>&#9889;</span></a>
            <h1>Streamline your workflow</h1>
            <p class="login-subtitle">One intelligent form at a time</p>
            <ul class="feature-list">
                <li>Sequential approval workflows</li>
                <li>AI-powered form generation</li>
                <li>Real-time notifications</li>
            </ul>
            <blockquote>&ldquo;FlowForm brings structure, accountability, and speed to every internal approval.&rdquo;</blockquote>
        </div>
    </div>

    <div class="login-form-shell">
        <form class="login-form" method="POST" action="<?= e(app_url('login')); ?>" novalidate>
            <input type="hidden" name="csrf_token" value="<?= e($csrfToken); ?>">
            <div class="login-heading">
                <h2>Welcome back <span class="wave-hand" aria-hidden="true">&#128075;</span></h2>
                <p>Enter your credentials to access FlowForm</p>
            </div>

            <label class="field-stack icon-field">
                <span>Email address</span>
                <div class="input-shell">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Zm8 8 8-5V7l-8 5-8-5v1l8 5Z"/></svg>
                    <input type="email" name="email" autocomplete="username" placeholder="admin@flowform.com" required>
                </div>
            </label>

            <label class="field-stack icon-field">
                <span>Password</span>
                <div class="input-shell">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8h14v-8a2 2 0 0 0-2-2Zm-7-2a2 2 0 1 1 4 0v2h-4V7Z"/></svg>
                    <input type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                    <span class="eye-toggle">&#9673;</span>
                </div>
            </label>

            <div class="login-options">
                <label class="remember-row">
                    <input type="checkbox" name="remember">
                    <span>Remember me</span>
                </label>
            </div>

            <button type="submit" class="btn-primary btn-block">Sign In</button>
            <div class="login-divider"><span>or</span></div>
            <a class="forgot-link" href="#">Forgot password?</a>
            <p class="login-copyright">&copy; 2026 FlowForm. All rights reserved.</p>
        </form>
    </div>
</section>

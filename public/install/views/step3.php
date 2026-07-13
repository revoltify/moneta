<div>
    <h2 class="text-lg font-semibold tracking-tight mb-1">Admin Account</h2>
    <p class="text-sm text-[var(--muted-foreground)] mb-6">Create the administrator account and initial company.</p>

    <form method="POST" action="?step=3" id="adminForm">
        <input type="hidden" name="_token" value="<?= $token ?>">

        <div class="space-y-5">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="name">Your Name</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars(old('name', 'Admin')) ?>" required class="input">
                </div>
                <div>
                    <label class="label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars(old('email', 'admin@admin.com')) ?>" required class="input">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label" for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="8" class="input">
                    <p class="text-xs text-[var(--muted-foreground)] mt-1">Minimum 8 characters</p>
                </div>
                <div>
                    <label class="label" for="password_confirmation">Confirm Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8" class="input">
                </div>
            </div>

            <div>
                <label class="label" for="company">Company Name</label>
                <input type="text" id="company" name="company" value="<?= htmlspecialchars(old('company', 'My Company')) ?>" required class="input">
                <p class="text-xs text-[var(--muted-foreground)] mt-1">Your default company for managing finances</p>
            </div>

            <div>
                <label class="label" for="app_url">Application URL</label>
                <input type="url" id="app_url" name="app_url" value="<?= htmlspecialchars(old('app_url', $detectedUrl ?? 'http://localhost')) ?>" required class="input" placeholder="https://example.com">
                <p class="text-xs text-[var(--muted-foreground)] mt-1">Used for email links and API URLs. Auto-detected from your browser.</p>
            </div>
        </div>

        <div class="flex items-center justify-end mt-8 pt-6 border-t border-[var(--border)]">
            <div class="flex items-center gap-3">
                <a href="?step=2" class="btn btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
                <button type="submit" id="installBtn" class="btn btn-primary btn-lg">
                    Install Moneta
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </button>
            </div>
        </div>
    </form>

    <?php if (! empty($installOutput)) { ?>
        <div class="mt-6 code-block">
            <pre class="text-xs text-green-400 font-mono whitespace-pre-wrap overflow-auto max-h-60"><?= htmlspecialchars($installOutput) ?></pre>
        </div>
    <?php } ?>

    <script>
        (function() {
            var form = document.getElementById('adminForm');
            var password = document.getElementById('password');
            var confirm = document.getElementById('password_confirmation');
            var passwordError = document.createElement('p');
            passwordError.className = 'text-xs text-red-500 dark:text-red-400 mt-1 hidden';
            confirm.parentNode.appendChild(passwordError);

            function checkPasswordMatch() {
                if (confirm.value.length === 0) {
                    passwordError.classList.add('hidden');
                    return;
                }
                if (password.value !== confirm.value) {
                    passwordError.textContent = 'Passwords do not match.';
                    passwordError.classList.remove('hidden');
                } else {
                    passwordError.classList.add('hidden');
                }
            }

            password.addEventListener('input', checkPasswordMatch);
            confirm.addEventListener('input', checkPasswordMatch);

            form.addEventListener('submit', function(e) {
                if (password.value !== confirm.value) {
                    e.preventDefault();
                    passwordError.textContent = 'Passwords do not match.';
                    passwordError.classList.remove('hidden');
                    confirm.focus();
                    return;
                }
                if (password.value.length < 8) {
                    e.preventDefault();
                    alert('Password must be at least 8 characters.');
                    password.focus();
                    return;
                }
                var btn = document.getElementById('installBtn');
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner"></span> Installing...';
            });
        })();
    </script>
</div>

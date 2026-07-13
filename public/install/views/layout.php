<!DOCTYPE html>
<html lang="en" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moneta - Installation</title>
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' };
    </script>
    <script>
        (function() {
            var theme = localStorage.getItem('theme');
            if (!theme) {
                theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            }
            if (theme === 'dark') {
                document.documentElement.classList.add('dark');
            }
            document.documentElement.style.colorScheme = theme;
        })();
    </script>
    <style>
        :root {
            --background: oklch(1 0 0);
            --foreground: oklch(0.145 0 0);
            --card: oklch(1 0 0);
            --card-foreground: oklch(0.145 0 0);
            --primary: oklch(0.205 0 0);
            --primary-foreground: oklch(0.985 0 0);
            --secondary: oklch(0.97 0 0);
            --secondary-foreground: oklch(0.205 0 0);
            --muted: oklch(0.97 0 0);
            --muted-foreground: oklch(0.556 0 0);
            --accent: oklch(0.97 0 0);
            --accent-foreground: oklch(0.205 0 0);
            --destructive: oklch(0.577 0.245 27.325);
            --destructive-foreground: oklch(0.577 0.245 27.325);
            --border: oklch(0.922 0 0);
            --input: oklch(0.922 0 0);
            --ring: oklch(0.87 0 0);
            --radius: 0.625rem;
            --radius-md: 0.5rem;
            --radius-sm: 0.375rem;
        }
        .dark {
            --background: oklch(0.145 0 0);
            --foreground: oklch(0.985 0 0);
            --card: oklch(0.145 0 0);
            --card-foreground: oklch(0.985 0 0);
            --primary: oklch(0.985 0 0);
            --primary-foreground: oklch(0.205 0 0);
            --secondary: oklch(0.269 0 0);
            --secondary-foreground: oklch(0.985 0 0);
            --muted: oklch(0.269 0 0);
            --muted-foreground: oklch(0.708 0 0);
            --accent: oklch(0.269 0 0);
            --accent-foreground: oklch(0.985 0 0);
            --destructive: oklch(0.396 0.141 25.723);
            --destructive-foreground: oklch(0.637 0.237 25.331);
            --border: oklch(0.269 0 0);
            --input: oklch(0.269 0 0);
            --ring: oklch(0.439 0 0);
        }
        * { border-color: var(--border); }
        body { background: var(--background); color: var(--foreground); font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
        .step-dot { width: 2.5rem; height: 2.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 600; transition: all 0.3s; }
        .step-dot.active { background: var(--primary); color: var(--primary-foreground); box-shadow: 0 0 0 4px color-mix(in srgb, var(--primary) 15%, transparent); }
        .step-dot.completed { background: #10b981; color: white; }
        .step-dot.inactive { background: var(--muted); color: var(--muted-foreground); }
        .step-line { flex: 1; height: 2px; margin: 0 1rem; margin-top: -0.5rem; }
        .step-line.completed { background: #10b981; }
        .step-line.inactive { background: var(--border); }
        .card { background: var(--card); color: var(--card-foreground); border-radius: 0.75rem; border: 1px solid; border-color: var(--border); box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); }
        .card-header { padding: 1.5rem 1.5rem 0.75rem; }
        .card-content { padding: 0.75rem 1.5rem 1.5rem; }
        .card-footer { padding: 0.75rem 1.5rem 1.5rem; border-top: 1px solid var(--border); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; white-space: nowrap; border-radius: var(--radius-md); font-size: 0.875rem; font-weight: 500; transition: color 0.15s, box-shadow 0.15s; outline: none; cursor: pointer; border: 0; }
        .btn:focus-visible { border-color: var(--ring); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ring) 50%, transparent); }
        .btn:disabled { pointer-events: none; opacity: 0.5; }
        .btn-primary { background: var(--primary); color: var(--primary-foreground); box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); height: 2.25rem; padding: 0.5rem 1rem; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-outline { background: var(--background); color: var(--foreground); border: 1px solid var(--input); box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); height: 2.25rem; padding: 0.5rem 1rem; }
        .btn-outline:hover { background: var(--accent); color: var(--accent-foreground); }
        .btn-ghost { background: transparent; color: var(--muted-foreground); height: 2.25rem; padding: 0.5rem 0.75rem; }
        .btn-ghost:hover { background: var(--accent); color: var(--accent-foreground); }
        .btn-lg { height: 2.5rem; padding: 0.5rem 1.5rem; border-radius: var(--radius-md); }
        .input { width: 100%; border-radius: var(--radius-md); border: 1px solid; border-color: var(--input); background: transparent; padding: 0.25rem 0.75rem; font-size: 1rem; height: 2.25rem; box-shadow: 0 1px 2px 0 rgb(0 0 0 / 0.05); transition: color 0.15s, box-shadow 0.15s; outline: none; }
        .input:focus-visible { border-color: var(--ring); box-shadow: 0 0 0 3px color-mix(in srgb, var(--ring) 50%, transparent); }
        .input::placeholder { color: var(--muted-foreground); }
        @media (min-width: 768px) { .input { font-size: 0.875rem; } }
        .label { display: block; font-size: 0.875rem; font-weight: 500; margin-bottom: 0.375rem; }
        .req-row { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem; border-radius: var(--radius-md); }
        .req-row.pass { background: var(--secondary); }
        .req-row.warn { background: color-mix(in srgb, #f59e0b 10%, transparent); }
        .req-row.fail { background: color-mix(in srgb, var(--destructive) 10%, transparent); }
        .badge { display: inline-flex; align-items: center; justify-content: center; border-radius: var(--radius-md); border: 1px solid transparent; padding: 0.125rem 0.5rem; font-size: 0.75rem; font-weight: 500; white-space: nowrap; }
        .badge-pass { background: color-mix(in srgb, #10b981 15%, transparent); color: #059669; border-color: transparent; }
        .badge-warn { background: color-mix(in srgb, #f59e0b 15%, transparent); color: #d97706; border-color: transparent; }
        .badge-fail { background: color-mix(in srgb, var(--destructive) 15%, transparent); color: var(--destructive); border-color: transparent; }
        .dark .badge-pass { background: color-mix(in srgb, #10b981 20%, transparent); color: #34d399; }
        .dark .badge-warn { background: color-mix(in srgb, #f59e0b 20%, transparent); color: #fbbf24; }
        .dark .badge-fail { background: color-mix(in srgb, var(--destructive) 20%, transparent); color: var(--destructive-foreground); }
        .card-radio { padding: 0.75rem; border: 2px solid var(--border); border-radius: var(--radius-md); text-align: center; transition: border-color 0.15s, background 0.15s; cursor: pointer; }
        .card-radio:hover { border-color: color-mix(in srgb, var(--ring) 50%, transparent); }
        .card-radio.selected { border-color: var(--primary); background: color-mix(in srgb, var(--primary) 8%, transparent); }
        .dark .card-radio.selected { background: color-mix(in srgb, var(--primary) 12%, transparent); }
        .code-block { background: #1a1a2e; border-radius: var(--radius-md); padding: 1rem; overflow-x: auto; }
        .code-block code { color: #34d399; font-size: 0.875rem; font-family: ui-monospace, SFMono-Regular, monospace; }
        .alert { border-radius: var(--radius-md); border: 1px solid; padding: 0.75rem 1rem; font-size: 0.875rem; }
        .alert-error { background: color-mix(in srgb, var(--destructive) 10%, transparent); border-color: color-mix(in srgb, var(--destructive) 30%, transparent); color: var(--destructive); }
        .dark .alert-error { color: var(--destructive-foreground); }
        .alert-success { background: color-mix(in srgb, #10b981 10%, transparent); border-color: color-mix(in srgb, #10b981 30%, transparent); color: #059669; }
        .dark .alert-success { color: #34d399; }
        .alert-warning { background: color-mix(in srgb, #f59e0b 10%, transparent); border-color: color-mix(in srgb, #f59e0b 30%, transparent); color: #d97706; }
        .dark .alert-warning { color: #fbbf24; }
        .spinner { border: 2px solid var(--muted); border-top-color: var(--primary); border-radius: 50%; width: 1rem; height: 1rem; animation: spin 0.6s linear infinite; display: inline-block; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-start justify-center py-8 px-4 sm:py-12">
        <div class="w-full max-w-2xl">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h1 class="text-xl font-semibold tracking-tight">Moneta</h1>
                    <p class="text-sm text-[var(--muted-foreground)] mt-0.5">Installation Wizard</p>
                </div>
                <button onclick="toggleTheme()" class="btn btn-ghost size-9 rounded-md p-0 flex items-center justify-center" title="Toggle theme">
                    <svg class="size-[1.2rem] rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                    <svg class="size-[1.2rem] absolute rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z"/></svg>
                </button>
            </div>

            <div class="card overflow-hidden">
                <div class="card-header">
                    <div class="flex items-center justify-between">
                        <?php $steps = [
                            1 => ['Requirements', 'Check server requirements'],
                            2 => ['Database', 'Configure database connection'],
                            3 => ['Admin', 'Create admin account'],
                            4 => ['Finish', 'Finalize installation'],
                        ]; ?>
                        <?php $displayStep = $is_installed ? 4 : $step; ?>
                        <?php foreach ($steps as $num => [$title, $desc]) { ?>
                            <div class="flex items-center <?= $num === 4 ? '' : 'flex-1' ?>">
                                <div class="flex flex-col items-center">
                                    <div class="step-dot <?= $displayStep == $num ? 'active' : ($displayStep > $num ? 'completed' : 'inactive') ?>">
                                        <?php if ($step > $num) { ?>
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <?php } else { ?>
                                            <?= $num ?>
                                        <?php } ?>
                                    </div>
                                    <span class="text-xs font-medium mt-2 <?= $displayStep == $num ? 'text-[var(--foreground)]' : 'text-[var(--muted-foreground)]' ?>"><?= $title ?></span>
                                </div>
                                <?php if ($num < 4) { ?>
                                    <div class="step-line <?= $displayStep > $num ? 'completed' : 'inactive' ?>"></div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <div class="card-content">
                    <?php if ($is_installed) { ?>
                        <div class="text-center py-6">
                            <div class="size-16 bg-emerald-500/10 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h2 class="text-lg font-semibold mb-1">Already Installed</h2>
                            <p class="text-sm text-[var(--muted-foreground)] mb-6">Moneta has already been installed on this server.</p>
                            <?php $adminEmail = function_exists('getAdminEmail') ? getAdminEmail() : null; ?>
                            <?php if ($adminEmail) { ?>
                                <p class="text-sm text-[var(--muted-foreground)] mb-6">Admin email: <strong class="text-[var(--foreground)]"><?= htmlspecialchars($adminEmail) ?></strong></p>
                            <?php } ?>
                            <div class="text-left mb-6 border border-red-500/30 dark:border-red-500/20 rounded-lg overflow-hidden">
                                <div class="bg-red-50 dark:bg-red-950/50 px-4 py-3 border-b border-red-500/30 dark:border-red-500/20">
                                    <h3 class="text-sm font-semibold text-red-700 dark:text-red-400">Security: Remove Install Folder</h3>
                                </div>
                                <div class="p-4">
                                    <p class="text-sm text-[var(--muted-foreground)]">Delete the <code class="bg-[var(--muted)] px-1.5 py-0.5 rounded text-xs">install/</code> folder first, then click <strong>Go to Login</strong> below to access Moneta.</p>
                                </div>
                            </div>
                            <a href="../login" class="btn btn-primary btn-lg" onclick="showButtonLoading(this, 'Redirecting...')">Go to Login</a>
                        </div>
                    <?php } else { ?>
                        <?php if (! empty($errors)) { ?>
                            <div class="alert alert-error mb-6">
                                <?php foreach ($errors as $error) { ?>
                                    <p class="flex items-center gap-2 <?= $error !== reset($errors) ? 'mt-2' : '' ?>">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                        <?= htmlspecialchars($error) ?>
                                    </p>
                                <?php } ?>
                            </div>
                        <?php } ?>

                        <?php if (! empty($success)) { ?>
                            <div class="alert alert-success mb-6">
                                <p class="flex items-center gap-2">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    <?= htmlspecialchars($success) ?>
                                </p>
                            </div>
                        <?php } ?>

                        <?php require INSTALL_BASE.'/views/step'.$step.'.php'; ?>
                    <?php } ?>
                </div>
            </div>

            <p class="text-center text-xs text-[var(--muted-foreground)] mt-6">Moneta &copy; <?= date('Y') ?> &mdash; Open Source Money Management</p>
        </div>
    </div>

    <script>
        function toggleTheme() {
            var html = document.documentElement;
            var isDark = html.classList.toggle('dark');
            html.style.colorScheme = isDark ? 'dark' : 'light';
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        }

        function showButtonLoading(btn, loadingText) {
            if (btn.dataset.loading) return;
            btn.dataset.originalHtml = btn.innerHTML;
            btn.dataset.loading = 'true';
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span>' + (loadingText ? ' ' + loadingText : '');
        }
    </script>
</body>
</html>

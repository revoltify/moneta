<div>
    <h2 class="text-lg font-semibold tracking-tight mb-1">Server Requirements</h2>
    <p class="text-sm text-[var(--muted-foreground)] mb-6">Check if your server meets the minimum requirements.</p>

    <?php $reqs = checkRequirements(); ?>
    <?php $allPassed = ! in_array(false, array_column($reqs, 'passed')); ?>
    <?php $warnings = array_filter($reqs, fn ($r) => ! $r['passed'] && ($r['optional'] ?? false)); ?>
    <?php $failures = array_filter($reqs, fn ($r) => ! $r['passed'] && ! ($r['optional'] ?? false)); ?>

    <div class="space-y-1.5 mb-8">
        <?php foreach ($reqs as $req) { ?>
            <?php
                $isPass = $req['passed'];
            $isWarn = ! $req['passed'] && ($req['optional'] ?? false);
            $isFail = ! $req['passed'] && ! ($req['optional'] ?? false);
            ?>
            <div class="req-row <?= $isPass ? 'pass' : ($isWarn ? 'warn' : 'fail') ?>">
                <div class="flex items-center gap-3 min-w-0">
                    <?php if ($isPass) { ?>
                        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    <?php } elseif ($isWarn) { ?>
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <?php } else { ?>
                        <svg class="w-5 h-5 text-[var(--destructive)] dark:text-[var(--destructive-foreground)] flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    <?php } ?>
                    <span class="text-sm truncate"><?= htmlspecialchars($req['label']) ?></span>
                </div>
                <span class="badge <?= $isPass ? 'badge-pass' : ($isWarn ? 'badge-warn' : 'badge-fail') ?> flex-shrink-0 ml-2">
                    <?= htmlspecialchars($req['value']) ?>
                </span>
            </div>
            <?php if (! $req['passed'] && $req['description']) { ?>
                <p class="text-xs text-[var(--muted-foreground)] ml-10 pb-1"><?= htmlspecialchars($req['description']) ?></p>
            <?php } ?>
        <?php } ?>
    </div>

    <?php
    $composerReq = current(array_filter($reqs, fn ($r) => $r['label'] === 'Composer Dependencies'));
    $composerSoleFailure = $composerReq && ! $composerReq['passed'] && count($failures) === 1;
    ?>

    <?php if ($composerSoleFailure) { ?>
        <div class="mt-6 pt-6 border-t border-[var(--border)]">
            <h3 class="text-sm font-semibold mb-2">Automatic Installation</h3>
            <p class="text-xs text-[var(--muted-foreground)] mb-3">Try to install Composer dependencies automatically using the server's PHP CLI.</p>
            <div class="flex items-center gap-3">
                <button id="installDepsBtn" class="btn btn-outline" onclick="installComposerDependencies(this)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                    Install Dependencies
                </button>
            </div>
            <div id="composerOutput" class="code-block mt-4" style="display:none;">
                <code id="composerOutputText" class="text-xs leading-relaxed"></code>
            </div>
            <div id="composerFallback" class="mt-4" style="display:none;">
                <div class="alert alert-warning">
                    <p class="flex items-center gap-2 font-medium">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Manual Installation Required
                    </p>
                    <p class="text-xs mt-2">We could not install dependencies automatically. Please upload the <code class="bg-[var(--muted)] px-1.5 py-0.5 rounded">vendor</code> folder from your local installation, or run <code class="bg-[var(--muted)] px-1.5 py-0.5 rounded">composer install</code> via SSH/terminal, then click <strong>Refresh Checks</strong>.</p>
                </div>
            </div>
        </div>
        <script>
        function installComposerDependencies(btn) {
            if (btn.dataset.loading) return;

            showButtonLoading(btn, 'Installing...');
            document.getElementById('composerOutput').style.display = '';
            document.getElementById('composerOutputText').textContent = 'Running composer install...\n';
            document.getElementById('composerFallback').style.display = 'none';

            var formData = new FormData();
            formData.append('_token', '<?= $token ?>');
            formData.append('_action', 'install_composer');

            fetch('?step=1&ajax=1', { method: 'POST', body: formData })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    document.getElementById('composerOutputText').textContent += data.output || '';
                    if (data.success) {
                        document.getElementById('composerOutputText').textContent += '\n\n\u2705 Dependencies installed! Refreshing checks...';
                        setTimeout(function() { window.location.href = '?step=1'; }, 1500);
                    } else {
                        btn.dataset.loading = '';
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.originalHtml || 'Install Dependencies';
                        document.getElementById('composerFallback').style.display = '';
                    }
                })
                .catch(function(err) {
                    document.getElementById('composerOutputText').textContent += '\n\n\u274c Error: ' + err.message;
                    btn.dataset.loading = '';
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.originalHtml || 'Install Dependencies';
                    document.getElementById('composerFallback').style.display = '';
                });
        }
        </script>
    <?php } ?>

    <?php
    $frontendReq = current(array_filter($reqs, fn ($r) => $r['label'] === 'Frontend Assets (Build)'));
    $frontendMissing = $frontendReq && ! $frontendReq['passed'];
    ?>

    <?php if ($frontendMissing) { ?>
        <div class="mt-6 pt-6 border-t border-[var(--border)]">
            <h3 class="text-sm font-semibold mb-2">Frontend Assets</h3>
            <p class="text-xs text-[var(--muted-foreground)] mb-3">Try to build frontend assets automatically using the server's Node.js.</p>
            <div class="flex items-center gap-3">
                <button id="buildAssetsBtn" class="btn btn-outline" onclick="buildFrontendAssets(this)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3v-1m-4-4-4 4m0 0-4-4m4 4V4"/></svg>
                    Build Frontend Assets
                </button>
                <span class="text-xs text-[var(--muted-foreground)]">(Optional — skip and build later)</span>
            </div>
            <div id="buildOutput" class="code-block mt-4" style="display:none;">
                <code id="buildOutputText" class="text-xs leading-relaxed"></code>
            </div>
            <div id="buildFallback" class="mt-4" style="display:none;">
                <div class="alert alert-warning">
                    <p class="flex items-center gap-2 font-medium">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                        Manual Build Required
                    </p>
                    <ul class="text-xs mt-2 ml-4 list-disc space-y-1">
                        <li>Upload the <code class="bg-[var(--muted)] px-1.5 py-0.5 rounded">public/build</code> folder from your local installation</li>
                        <li>Run <code class="bg-[var(--muted)] px-1.5 py-0.5 rounded">npm install && npm run build</code> via SSH/terminal</li>
                        <li>Skip for now — the application will still work</li>
                    </ul>
                </div>
            </div>
        </div>
        <script>
        function buildFrontendAssets(btn) {
            if (btn.dataset.loading) return;

            showButtonLoading(btn, 'Building...');
            document.getElementById('buildOutput').style.display = '';
            document.getElementById('buildOutputText').textContent = 'Installing npm packages...\n';
            document.getElementById('buildFallback').style.display = 'none';

            var formData = new FormData();
            formData.append('_token', '<?= $token ?>');
            formData.append('_action', 'build_frontend');

            fetch('?step=1&ajax=1', { method: 'POST', body: formData })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    document.getElementById('buildOutputText').textContent += data.output || '';
                    if (data.success) {
                        document.getElementById('buildOutputText').textContent += '\n\n\u2705 Frontend assets built! Refreshing checks...';
                        setTimeout(function() { window.location.href = '?step=1'; }, 1500);
                    } else {
                        btn.dataset.loading = '';
                        btn.disabled = false;
                        btn.innerHTML = btn.dataset.originalHtml || 'Build Frontend Assets';
                        document.getElementById('buildFallback').style.display = '';
                    }
                })
                .catch(function(err) {
                    document.getElementById('buildOutputText').textContent += '\n\n\u274c Error: ' + err.message;
                    btn.dataset.loading = '';
                    btn.disabled = false;
                    btn.innerHTML = btn.dataset.originalHtml || 'Build Frontend Assets';
                    document.getElementById('buildFallback').style.display = '';
                });
        }
        </script>
    <?php } ?>

    <?php if (! empty($failures)) { ?>
        <div class="alert alert-error mb-6">
            <p class="flex items-center gap-2 font-medium">
                <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                Please fix the required issues above before proceeding.
            </p>
        </div>
        <div class="flex items-center justify-end">
            <a href="?step=1" class="btn btn-outline" onclick="showButtonLoading(this, 'Checking...')">Refresh Checks</a>
        </div>
    <?php } else { ?>
        <form method="POST" action="?step=1" id="step1Form">
            <input type="hidden" name="_token" value="<?= $token ?>">
            <?php if (! empty($warnings)) { ?>
                <div class="alert alert-warning mb-6">
                    <p class="flex items-center gap-2">
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                        Some optional checks failed. You can proceed, but may need to address these later.
                    </p>
                </div>
            <?php } ?>
            <div class="flex items-center justify-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    Continue
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </form>
        <script>
            document.getElementById('step1Form').addEventListener('submit', function(e) {
                showButtonLoading(this.querySelector('button[type="submit"]'), 'Continuing...');
            });
        </script>
    <?php } ?>
</div>

<div>
    <h2 class="text-lg font-semibold tracking-tight mb-1">Installation Complete</h2>
    <p class="text-sm text-[var(--muted-foreground)] mb-6">Moneta has been successfully installed. Review the final steps below.</p>

    <div class="alert-success p-4 rounded-lg mb-6 flex items-center gap-3 border">
        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
        <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Moneta has been installed successfully!</p>
    </div>

    <div class="space-y-3 mb-6">
        <div class="card overflow-hidden">
            <div class="bg-[var(--secondary)] px-4 py-3 border-b border-[var(--border)]">
                <h3 class="text-sm font-semibold">Setup Cron Job</h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-[var(--muted-foreground)] mb-3">Add the following cron entry to process recurring transactions and scheduled tasks:</p>
                <?php $projectPath = str_replace('\\', '/', BASE_PATH); ?>
                <div class="code-block mb-3">
                    <code>* * * * * cd <?= htmlspecialchars($projectPath) ?> && php artisan schedule:run >> /dev/null 2>&amp;1</code>
                </div>
                <p class="text-xs text-[var(--muted-foreground)]">This runs every minute. Laravel will execute only the tasks scheduled for that time.</p>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="bg-[var(--secondary)] px-4 py-3 border-b border-[var(--border)]">
                <h3 class="text-sm font-semibold">Queue Worker <span class="text-xs font-normal text-[var(--muted-foreground)]">(Optional)</span></h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-[var(--muted-foreground)] mb-3">If you plan to use queued jobs, run this as a daemon:</p>
                <div class="code-block mb-3">
                    <code>php artisan queue:work</code>
                </div>
                <p class="text-xs text-[var(--muted-foreground)]">This is optional. The default sync queue works without it.</p>
            </div>
        </div>

        <div class="card overflow-hidden">
            <div class="bg-[var(--secondary)] px-4 py-3 border-b border-[var(--border)]">
                <h3 class="text-sm font-semibold">Frontend Assets <span class="text-xs font-normal text-[var(--muted-foreground)]">(if not built)</span></h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-[var(--muted-foreground)] mb-3">If you haven't built the frontend assets yet, run:</p>
                <div class="code-block mb-3">
                    <code>npm install && npm run build</code>
                </div>
                <p class="text-xs text-[var(--muted-foreground)]">Required for the UI to render correctly.</p>
            </div>
        </div>

        <div class="card overflow-hidden border-red-500/30 dark:border-red-500/20">
            <div class="bg-red-50 dark:bg-red-950/50 px-4 py-3 border-b border-red-500/30 dark:border-red-500/20">
                <h3 class="text-sm font-semibold text-red-700 dark:text-red-400">Security: Remove Install Folder</h3>
            </div>
            <div class="p-4">
                <p class="text-sm text-[var(--muted-foreground)] mb-3">
                    For security, you must delete the <code class="bg-[var(--muted)] px-1.5 py-0.5 rounded text-xs">install/</code> directory before using Moneta.
                </p>
                <div class="alert-error p-3 rounded-lg border flex items-start gap-3">
                    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <div>
                        <p class="text-sm font-medium">Delete the <code class="bg-[var(--muted)] px-1.5 py-0.5 rounded text-xs">install/</code> folder now</p>
                        <p class="text-xs mt-1 opacity-80">Leaving this folder accessible is a security risk.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="flex items-center justify-end pt-6 border-t border-[var(--border)]">
        <a href="../login" class="btn btn-primary btn-lg" onclick="showButtonLoading(this, 'Redirecting...')">
            Go to Login
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        </a>
    </div>
</div>

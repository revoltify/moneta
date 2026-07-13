<div>
    <h2 class="text-lg font-semibold tracking-tight mb-1">Database Configuration</h2>
    <p class="text-sm text-[var(--muted-foreground)] mb-6">Configure your database connection. SQLite is the simplest option.</p>

    <form method="POST" action="?step=2" id="dbForm">
        <input type="hidden" name="_token" value="<?= $token ?>">

        <div class="space-y-5">
            <div>
                <label class="label">Database Type</label>
                <div class="grid grid-cols-4 gap-3">
                    <?php $connections = [
                        'sqlite' => ['SQLite', 'File-based, no server needed'],
                        'mysql' => ['MySQL', 'Popular relational DB'],
                        'mariadb' => ['MariaDB', 'MySQL-compatible fork'],
                        'pgsql' => ['PostgreSQL', 'Advanced relational DB'],
                    ]; ?>
                    <?php foreach ($connections as $key => [$name, $desc]) { ?>
                        <label class="cursor-pointer">
                            <input type="radio" name="db_connection" value="<?= $key ?>" class="sr-only peer" <?= old('db_connection', 'sqlite') === $key ? 'checked' : '' ?> onchange="toggleDBFields()">
                            <div class="card-radio peer-checked:selected">
                                <div class="text-sm font-medium"><?= $name ?></div>
                                <div class="text-xs text-[var(--muted-foreground)] mt-0.5"><?= $desc ?></div>
                            </div>
                        </label>
                    <?php } ?>
                </div>
            </div>

            <div id="sqliteFields" class="space-y-4">
                <div>
                    <label class="label">Database File Path</label>
                    <input type="text" name="db_database" value="<?= htmlspecialchars(old('db_database', 'database.sqlite')) ?>" class="input">
                    <p class="text-xs text-[var(--muted-foreground)] mt-1">Relative to <code class="bg-[var(--muted)] px-1 py-0.5 rounded text-xs">storage/</code> directory</p>
                </div>
            </div>

            <div id="serverFields" class="space-y-4 hidden">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="label">Host</label>
                        <input type="text" name="db_host" value="<?= htmlspecialchars(old('db_host', '127.0.0.1')) ?>" class="input">
                    </div>
                    <div>
                        <label class="label">Port</label>
                        <input type="text" name="db_port" value="<?= htmlspecialchars(old('db_port', '')) ?>" placeholder="Auto" class="input">
                    </div>
                </div>
                <div>
                    <label class="label">Database Name</label>
                    <input type="text" name="db_database" value="<?= htmlspecialchars(old('db_database', '')) ?>" class="input">
                </div>
                <div>
                    <label class="label">Username</label>
                    <input type="text" name="db_username" value="<?= htmlspecialchars(old('db_username', '')) ?>" class="input">
                </div>
                <div>
                    <label class="label">Password</label>
                    <input type="password" name="db_password" value="<?= htmlspecialchars(old('db_password', '')) ?>" class="input">
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end mt-8 pt-6 border-t border-[var(--border)]">
            <div class="flex items-center gap-3">
                <a href="?step=1" class="btn btn-outline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Back
                </a>
                <button type="button" onclick="testConnection()" id="testBtn" class="btn btn-outline">
                    Test Connection
                </button>
                <button type="submit" class="btn btn-primary btn-lg">
                    Continue
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>
        </div>
    </form>

    <div id="testResult" class="mt-4 hidden"></div>

    <script>
        function toggleDBFields() {
            var val = document.querySelector('input[name="db_connection"]:checked').value;
            var isSqlite = val === 'sqlite';
            document.getElementById('sqliteFields').classList.toggle('hidden', !isSqlite);
            document.getElementById('serverFields').classList.toggle('hidden', isSqlite);
            document.querySelectorAll('#sqliteFields input, #serverFields input').forEach(function(el) {
                el.disabled = el.closest('.hidden') !== null;
            });
        }
        toggleDBFields();

        async function testConnection() {
            var btn = document.getElementById('testBtn');
            var result = document.getElementById('testResult');
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner"></span> Testing...';
            result.classList.add('hidden');

            var form = document.getElementById('dbForm');
            var data = new FormData(form);
            data.set('_action', 'test_connection');

            try {
                var res = await fetch('?step=2&ajax=1', { method: 'POST', body: data });
                var text = await res.text();
                result.classList.remove('hidden');
                var isSuccess = text.startsWith('CONNECTION_OK');
                result.className = 'mt-4 p-4 rounded-lg ' + (isSuccess ? 'alert-success' : 'alert-error');
                result.innerHTML = isSuccess
                    ? '<p class="flex items-center gap-2"><svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Connection successful!</p>'
                    : '<p class="flex items-center gap-2"><svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg> ' + text.replace('CONNECTION_FAIL:', '') + '</p>';
            } catch (e) {
                result.classList.remove('hidden');
                result.className = 'mt-4 p-4 rounded-lg alert-error';
                result.innerHTML = '<p class="flex items-center gap-2"><svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg> Connection failed: ' + e.message + '</p>';
            }

            btn.disabled = false;
            btn.innerHTML = 'Test Connection';
        }

        document.getElementById('dbForm').addEventListener('submit', function(e) {
            document.querySelectorAll('#sqliteFields input, #serverFields input').forEach(function(el) {
                el.disabled = el.closest('.hidden') !== null;
            });
            showButtonLoading(this.querySelector('button[type="submit"]'), 'Continuing...');
        });
    </script>
</div>

<div class="space-y-6 max-w-5xl mx-auto" id="user-api-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/account" class="hover:text-white transition">&larr; Account Overview</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">API Credentials</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">API Management & Documentation</h1>
            <p class="text-sm text-slate-400 mt-1">Integrate our SMM order dispatch services directly into your own website, bot, or application.</p>
        </div>
        <a href="/api-docs" target="_blank" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 border border-slate-700 transition">
            View Full API Specs &rarr;
        </a>
    </div>

    <!-- API Key Card -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
        <h2 class="text-base font-bold text-white mb-2">Your Live API Secret Key</h2>
        <p class="text-xs text-slate-400 mb-4">Keep this key confidential. Never expose it in client-side code or public repositories.</p>

        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 max-w-2xl">
            <div class="flex-1 relative">
                <input id="api-key-input" type="password" readonly value="<?= e($apiKey ?? 'NO_KEY_GENERATED') ?>" class="w-full bg-slate-950 border border-slate-700 rounded-xl px-4 py-2.5 text-xs text-emerald-400 font-mono focus:outline-none select-all">
                <button type="button" onclick="const el = document.getElementById('api-key-input'); el.type = el.type === 'password' ? 'text' : 'password';" class="absolute right-3 top-2.5 text-slate-400 hover:text-white text-xs">
                    👁️
                </button>
            </div>
            <button type="button" onclick="navigator.clipboard.writeText('<?= e($apiKey ?? '') ?>'); alert('API Key copied to clipboard!');" class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl border border-slate-700 transition">
                Copy Key
            </button>
            <form action="/account/api/generate" method="POST">
                <?= csrf_field() ?>
                <button type="submit" onclick="return confirm('Generating a new key will immediately invalidate your old key. Continue?')" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition">
                    <?= empty($apiKey) ? 'Generate Key' : 'Regenerate' ?>
                </button>
            </form>
            <?php if (!empty($apiKey)): ?>
                <form action="/account/api/revoke" method="POST">
                    <?= csrf_field() ?>
                    <button type="submit" onclick="return confirm('Revoking this key will disable all external API calls. Continue?')" class="px-4 py-2.5 bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 border border-rose-600/20 text-xs font-semibold rounded-xl transition">
                        Revoke
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Usage & Endpoints -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-3">API Endpoint & Specs</h3>
            <div class="space-y-3 text-xs">
                <div>
                    <span class="text-slate-400 block mb-1">HTTP Method & URL</span>
                    <code class="px-2 py-1 rounded bg-slate-950 text-indigo-400 font-mono text-[11px] block break-all">POST <?= config('app.url', 'https://domain.com') ?>/api/v2</code>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Data Format</span>
                    <span class="text-slate-200 font-mono">multipart/form-data or application/x-www-form-urlencoded</span>
                </div>
                <div>
                    <span class="text-slate-400 block mb-1">Rate Limit</span>
                    <span class="text-slate-200">120 requests per minute per key</span>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <h3 class="text-sm font-bold text-white mb-3">Quick Implementation Example (cURL)</h3>
            <pre class="bg-slate-950 p-4 rounded-xl text-indigo-300 font-mono text-[11px] overflow-x-auto border border-slate-800 leading-relaxed">
# Place a new order
curl -X POST "<?= config('app.url', 'https://domain.com') ?>/api/v2" \
  -d "key=YOUR_API_KEY" \
  -d "action=add" \
  -d "service=1" \
  -d "link=https://instagram.com/profile" \
  -d "quantity=1000"

# Check order status
curl -X POST "<?= config('app.url', 'https://domain.com') ?>/api/v2" \
  -d "key=YOUR_API_KEY" \
  -d "action=status" \
  -d "order=12345"
            </pre>
        </div>
    </div>

    <!-- API Request Log -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800 flex items-center justify-between">
            <h3 class="text-sm font-bold text-white">Recent API Calls (Last 50)</h3>
            <span class="text-xs text-slate-400"><?= count($logs) ?> calls recorded</span>
        </div>

        <?php if (empty($logs)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No external API calls recorded for this key yet.</div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead>
                        <tr class="border-b border-slate-800 text-slate-400 bg-slate-950/40">
                            <th class="py-3 px-4">Timestamp</th>
                            <th class="py-3 px-4">Action</th>
                            <th class="py-3 px-4">Caller IP</th>
                            <th class="py-3 px-4 text-right">HTTP Code</th>
                            <th class="py-3 px-4 text-right">Latency</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60 text-slate-300">
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td class="py-2.5 px-4 font-mono text-slate-400"><?= e($l['created_at']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-indigo-400 font-semibold"><?= e($l['action']) ?></td>
                                <td class="py-2.5 px-4 font-mono text-slate-300"><?= e($l['ip_address']) ?></td>
                                <td class="py-2.5 px-4 text-right">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold font-mono <?= (int)$l['response_code'] < 400 ? 'bg-emerald-500/10 text-emerald-400' : 'bg-rose-500/10 text-rose-400' ?>">
                                        <?= (int)$l['response_code'] ?>
                                    </span>
                                </td>
                                <td class="py-2.5 px-4 text-right font-mono text-slate-400"><?= number_format((float)$l['response_time_ms'], 0) ?>ms</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="max-w-5xl mx-auto space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-white tracking-tight">System Configuration & Background Jobs</h1>
        <p class="text-xs text-slate-400 mt-1">Manage global panel branding, currency conversions, payment credentials, and cron automation.</p>
    </div>

    <form action="/admin/settings" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <!-- Branding & Core Settings -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl space-y-6">
            <h2 class="text-base font-bold text-white pb-3 border-b border-slate-800 flex items-center gap-2">
                <span>⚙️</span> Platform Branding & General
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Platform Brand Name</label>
                    <input type="text" name="site_name" value="<?= e($settings['site_name'] ?? 'Apex SMM') ?>" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Support Email</label>
                    <input type="email" name="support_email" value="<?= e($settings['support_email'] ?? 'support@example.com') ?>"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Telegram Support / Channel</label>
                    <input type="text" name="telegram_channel" value="<?= e($settings['telegram_channel'] ?? '@ApexSMMOfficial') ?>"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>
            </div>

            <!-- Maintenance Mode Toggle -->
            <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 flex items-center justify-between">
                <div>
                    <div class="text-xs font-bold text-white">Platform Maintenance Mode</div>
                    <div class="text-[11px] text-slate-400 mt-0.5">When active, client-side visitors see a maintenance screen. Staff can still access /admin.</div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="maintenance_mode" value="1" <?= !empty($settings['maintenance_mode']) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-rose-600"></div>
                </label>
            </div>
        </div>

        <!-- Currency & Pricing Margins -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl space-y-6">
            <h2 class="text-base font-bold text-white pb-3 border-b border-slate-800 flex items-center gap-2">
                <span>💱</span> Currency & Margins
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Base System Currency</label>
                    <input type="text" name="currency" value="<?= e($settings['currency'] ?? 'INR') ?>" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs font-mono focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Currency Symbol</label>
                    <input type="text" name="currency_symbol" value="<?= e($settings['currency_symbol'] ?? '₹') ?>" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-xs font-mono focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">USD to Base Rate (₹)</label>
                    <input type="number" step="0.01" name="usd_to_inr_rate" value="<?= (float)($settings['usd_to_inr_rate'] ?? 86.50) ?>" required
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-emerald-400 font-bold font-mono text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                    <p class="text-[10px] text-slate-500 mt-1">Converts wholesale USD provider costs into base currency.</p>
                </div>
            </div>
        </div>

        <!-- Payment Gateway Credentials -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl space-y-6">
            <h2 class="text-base font-bold text-white pb-3 border-b border-slate-800 flex items-center gap-2">
                <span>💳</span> Razorpay Payment Gateway Keys
            </h2>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Razorpay Key ID</label>
                    <input type="text" name="razorpay_key_id" value="<?= e($settings['razorpay_key_id'] ?? '') ?>" placeholder="rzp_test_..."
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 font-mono text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Razorpay Key Secret</label>
                    <input type="password" name="razorpay_key_secret" value="<?= e($settings['razorpay_key_secret'] ?? '') ?>" placeholder="Secret Key"
                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 font-mono text-xs focus:ring-2 focus:ring-rose-500 focus:outline-none transition">
                </div>
            </div>
        </div>

        <button type="submit" class="w-full py-3.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-lg shadow-rose-600/25 transition">
            Save System Configurations
        </button>
    </form>

    <!-- Cron Automation & Execution Logs Section -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl space-y-6">
        <div>
            <h2 class="text-base font-bold text-white flex items-center gap-2">
                <span>⏱️</span> Background Cron Automations
            </h2>
            <p class="text-xs text-slate-400 mt-1">The SMM engine synchronizes order statuses with upstream wholesale providers every 1-2 minutes.</p>
        </div>

        <div class="p-4 rounded-xl bg-slate-950/70 border border-slate-800 text-xs text-slate-300 font-mono space-y-1">
            <div class="text-slate-400 text-[11px] mb-2 font-sans font-semibold">Crontab Command (Run every minute on production server):</div>
            <div class="text-emerald-400 select-all">* * * * * php /path/to/artisan-cron.php >> /dev/null 2>&1</div>
        </div>

        <!-- Recent Cron Logs -->
        <div>
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Recent Background Execution Log</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800">
                        <tr>
                            <th class="py-2.5 px-3">Job Name</th>
                            <th class="py-2.5 px-3">Execution Time</th>
                            <th class="py-2.5 px-3">Duration</th>
                            <th class="py-2.5 px-3">Status</th>
                            <th class="py-2.5 px-3">Output / Summary</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($cron_logs)): ?>
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">No cron runs recorded yet. (Auto-records when cron script runs).</td></tr>
                        <?php else: ?>
                            <?php foreach ($cron_logs as $log): ?>
                                <tr class="hover:bg-slate-800/40 transition">
                                    <td class="py-2.5 px-3 font-mono font-semibold text-rose-400"><?= e($log['task_name'] ?? $log['job_name'] ?? 'sync_orders') ?></td>
                                    <td class="py-2.5 px-3 text-slate-400 whitespace-nowrap"><?= date('M d, H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td class="py-2.5 px-3 font-mono text-slate-400"><?= number_format((float)($log['duration_seconds'] ?? ($log['duration_ms'] ?? 0)), 1) ?>s</td>
                                    <td class="py-2.5 px-3">
                                        <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold <?= $log['status'] === 'success' ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-400 border border-rose-500/20' ?>">
                                            <?= ucfirst($log['status']) ?>
                                        </span>
                                    </td>
                                    <td class="py-2.5 px-3 text-slate-400 max-w-[260px] truncate font-mono text-[11px]"><?= e($log['message'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

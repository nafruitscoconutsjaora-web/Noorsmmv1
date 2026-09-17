<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <a href="/admin/gateways" class="inline-flex items-center gap-1.5 text-xs text-slate-400 hover:text-white transition mb-2">
                &larr; Back to Payment Gateways
            </a>
            <h1 class="text-2xl font-bold text-white tracking-tight flex items-center gap-2.5">
                <span>Configure <?= e($gateway['name']) ?></span>
                <span class="font-mono text-xs px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 font-normal">
                    <?= e($gateway['code']) ?>
                </span>
            </h1>
            <p class="text-xs text-slate-400 mt-1">Manage credentials, fee rules, deposit bonuses, and webhook configuration.</p>
        </div>
        <div>
            <span class="inline-flex px-3 py-1 rounded-xl text-xs font-bold uppercase tracking-wider <?= !empty($gateway['is_enabled']) ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400 border border-slate-700' ?>">
                <?= !empty($gateway['is_enabled']) ? '● Enabled' : '○ Disabled' ?>
            </span>
        </div>
    </div>

    <!-- Webhook Integration Card -->
    <div class="p-5 rounded-2xl bg-indigo-950/40 border border-indigo-500/20 shadow-lg space-y-2">
        <div class="flex items-center justify-between">
            <span class="text-xs font-bold text-indigo-300 flex items-center gap-1.5">
                <span>🔗</span> Gateway Webhook / Instant Notification URL (IPN)
            </span>
            <span class="text-[11px] text-slate-400">Paste this URL into your <?= e($gateway['name']) ?> developer portal</span>
        </div>
        <div class="flex items-center gap-2">
            <input type="text" readonly id="webhookUrlInput" value="<?= e($gateway['webhook_url']) ?>"
                   class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono select-all">
            <button type="button" onclick="copyWebhookUrl()" id="copyBtn"
                    class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs transition whitespace-nowrap">
                Copy URL
            </button>
        </div>
    </div>

    <!-- Edit Gateway Form -->
    <form action="/admin/gateways/<?= (int)$gateway['id'] ?>/update" method="POST" class="space-y-6">
        <?= csrf_field() ?>

        <!-- 1. General Settings -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-5">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3">
                1. General Settings
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Gateway Display Name</label>
                    <input type="text" name="name" required value="<?= e($gateway['name']) ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Default Currency</label>
                    <input type="text" name="currency" required value="<?= e($gateway['currency'] ?? 'INR') ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-mono font-semibold focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Minimum Deposit Limit (₹)</label>
                    <input type="number" step="0.01" min="0.01" name="min_amount" required value="<?= (float)$gateway['min_amount'] ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Maximum Deposit Limit (₹)</label>
                    <input type="number" step="0.01" min="0.01" name="max_amount" required value="<?= (float)$gateway['max_amount'] ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">Display Sort Order</label>
                    <input type="number" name="sort_order" value="<?= (int)$gateway['sort_order'] ?>"
                           class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>

                <div class="flex items-center pt-5">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="is_enabled" value="1" <?= !empty($gateway['is_enabled']) ? 'checked' : '' ?>
                               class="w-4 h-4 rounded text-indigo-600 bg-slate-950 border-slate-750 focus:ring-indigo-500 focus:ring-offset-slate-900">
                        <span class="text-xs font-bold text-white">Enable Gateway for User Checkout</span>
                    </label>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Short Description / Subtitle</label>
                <input type="text" name="description" value="<?= e($gateway['description'] ?? '') ?>" placeholder="e.g. Instant payment via UPI, Credit/Debit Cards, Netbanking"
                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
            </div>
        </div>

        <!-- 2. Processing Fees & Deposit Bonus Rules -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-5">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3">
                2. Fee Surcharge & Deposit Bonus Engine
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <!-- Fee Box -->
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-3">
                    <span class="text-xs font-bold text-amber-400">Gateway Transaction Surcharge</span>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Percentage Surcharge (%)</label>
                        <input type="number" step="0.01" min="0" name="percent_fee" value="<?= (float)$gateway['percent_fee'] ?>"
                               class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Fixed Surcharge Fee (₹)</label>
                        <input type="number" step="0.01" min="0" name="fixed_fee" value="<?= (float)$gateway['fixed_fee'] ?>"
                               class="w-full px-3.5 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                </div>

                <!-- Bonus Box -->
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-emerald-400">Incentive Deposit Bonus</span>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="bonus_enabled" value="1" <?= !empty($gateway['bonus_enabled']) ? 'checked' : '' ?>
                                   class="w-3.5 h-3.5 rounded text-emerald-600 bg-slate-950 border-slate-750 focus:ring-emerald-500">
                            <span class="text-[11px] text-slate-300 font-semibold">Enable Bonus</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Bonus Type</label>
                            <select name="bonus_type" class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs">
                                <option value="percentage" <?= ($gateway['bonus_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                <option value="fixed" <?= ($gateway['bonus_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed (₹)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-400 mb-1">Bonus Value</label>
                            <input type="number" step="0.01" min="0" name="bonus_value" value="<?= (float)$gateway['bonus_value'] ?>"
                                   class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-400 mb-1">Max Bonus Cap (₹, 0 = unlimited)</label>
                        <input type="number" step="0.01" min="0" name="max_bonus" value="<?= (float)$gateway['max_bonus'] ?>"
                               class="w-full px-3 py-2 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs font-mono">
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Gateway API Credentials -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-5">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h2 class="text-sm font-bold text-white uppercase tracking-wider">
                    3. Merchant Credentials & API Keys
                </h2>
                <span class="text-[11px] text-slate-500 font-mono flex items-center gap-1">
                    🔒 Stored with 256-bit AES Encryption
                </span>
            </div>

            <?php if (empty($gateway['credential_fields'])): ?>
                <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-400">
                    No custom merchant credentials required for this adapter.
                </div>
            <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($gateway['credential_fields'] as $fieldKey => $field): ?>
                        <?php
                            $currentVal = $gateway['decrypted_credentials'][$fieldKey] ?? '';
                            $fieldType = $field['type'] ?? 'text';
                        ?>
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                                <?= e($field['label'] ?? ucfirst($fieldKey)) ?>
                                <?php if (!empty($field['required'])): ?>
                                    <span class="text-rose-400">*</span>
                                <?php endif; ?>
                            </label>

                            <?php if ($fieldType === 'select'): ?>
                                <select name="credentials[<?= e($fieldKey) ?>]"
                                        class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-white text-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <?php foreach (($field['options'] ?? []) as $optVal => $optLabel): ?>
                                        <option value="<?= e($optVal) ?>" <?= (string)$currentVal === (string)$optVal ? 'selected' : '' ?>>
                                            <?= e($optLabel) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php elseif ($fieldType === 'textarea'): ?>
                                <textarea name="credentials[<?= e($fieldKey) ?>]" rows="3" placeholder="<?= e($field['placeholder'] ?? '') ?>"
                                          class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none"><?= e($currentVal) ?></textarea>
                            <?php elseif ($fieldType === 'password'): ?>
                                <div class="relative">
                                    <input type="password" name="credentials[<?= e($fieldKey) ?>]" id="cred_<?= e($fieldKey) ?>"
                                           placeholder="<?= !empty($currentVal) ? '•••••••••••••••••••• (Leave blank to keep unchanged)' : ($field['placeholder'] ?? '') ?>"
                                           class="w-full pl-3.5 pr-20 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                                    <button type="button" onclick="togglePasswordVisibility('cred_<?= e($fieldKey) ?>')"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-white text-xs">
                                        Show/Hide
                                    </button>
                                </div>
                            <?php else: ?>
                                <input type="text" name="credentials[<?= e($fieldKey) ?>]" value="<?= e($currentVal) ?>"
                                       placeholder="<?= e($field['placeholder'] ?? '') ?>"
                                       class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs font-mono focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <?php endif; ?>

                            <?php if (!empty($field['help'])): ?>
                                <p class="text-[11px] text-slate-500 mt-1"><?= e($field['help']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- 4. Deposit Instructions (for Manual, Bank, QR) -->
        <div class="p-6 rounded-2xl bg-slate-900 border border-slate-800 shadow-xl space-y-4">
            <h2 class="text-sm font-bold text-white uppercase tracking-wider border-b border-slate-800 pb-3">
                4. User Notice & Payment Instructions
            </h2>
            <div>
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Instructions displayed to customers during deposit</label>
                <textarea name="instructions" rows="4" placeholder="Enter bank account details, UPI ID, or guidance notes for the customer..."
                          class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-200 text-xs leading-relaxed focus:ring-2 focus:ring-indigo-500 focus:outline-none"><?= e($gateway['instructions'] ?? '') ?></textarea>
                <p class="text-[11px] text-slate-500 mt-1">Especially helpful for manual bank transfers, cryptocurrency networks, or custom UPI payment slips.</p>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="/admin/gateways" class="px-5 py-3 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 font-semibold text-xs transition">
                Cancel
            </a>
            <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/25 transition">
                Save Gateway Configuration &rarr;
            </button>
        </div>
    </form>
</div>

<script>
function copyWebhookUrl() {
    const input = document.getElementById('webhookUrlInput');
    input.select();
    navigator.clipboard.writeText(input.value).then(() => {
        const btn = document.getElementById('copyBtn');
        const orig = btn.textContent;
        btn.textContent = 'Copied!';
        btn.classList.add('bg-emerald-600');
        setTimeout(() => {
            btn.textContent = orig;
            btn.classList.remove('bg-emerald-600');
        }, 2000);
    });
}

function togglePasswordVisibility(id) {
    const input = document.getElementById(id);
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>

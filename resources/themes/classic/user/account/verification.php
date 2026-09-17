<div class="space-y-6 max-w-3xl mx-auto" id="user-verification-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/account" class="hover:text-white transition">&larr; Account Overview</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Verification</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Account Verification Center</h1>
            <p class="text-sm text-slate-400 mt-1">Verify your identity contact points to unlock higher daily deposit thresholds and API access.</p>
        </div>
    </div>

    <!-- Overall Trust Badge -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 flex items-center justify-between shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-2xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center text-2xl border border-indigo-500/20">
                🛡️
            </div>
            <div>
                <div class="text-sm font-bold text-white flex items-center gap-2">
                    <span>Account Trust Status:</span>
                    <?php if (!empty($user['email_verified_at'])): ?>
                        <span class="text-emerald-400">Verified Member</span>
                    <?php else: ?>
                        <span class="text-amber-400">Action Required</span>
                    <?php endif; ?>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">Two-step verified accounts receive expedited dispute resolution and priority ticket queues.</p>
            </div>
        </div>
    </div>

    <!-- Verification Items -->
    <div class="space-y-4">
        <!-- Email Verification -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-base">📧</span>
                        <h3 class="text-sm font-bold text-white">Email Address</h3>
                        <?php if (!empty($user['email_verified_at'])): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">VERIFIED</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-amber-500/10 text-amber-400 border border-amber-500/20">UNVERIFIED</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-400 font-mono mt-1"><?= e($user['email']) ?></p>
                </div>
                <?php if (empty($user['email_verified_at'])): ?>
                    <form action="/account/verification/email" method="POST">
                        <?= csrf_field() ?>
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                            Send Verification Link
                        </button>
                    </form>
                <?php else: ?>
                    <span class="text-xs text-slate-500 font-mono">Verified on <?= substr($user['email_verified_at'], 0, 10) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Phone Verification -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-base">📱</span>
                        <h3 class="text-sm font-bold text-white">Phone / WhatsApp Number</h3>
                        <?php if (!empty($user['phone_number'])): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">LINKED</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-800 text-slate-400">OPTIONAL</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">Used for urgent order alerts and fast-track Telegram/WhatsApp support access.</p>
                </div>
            </div>

            <form action="/account/verification/phone" method="POST" class="mt-4 flex items-center gap-3 max-w-md">
                <?= csrf_field() ?>
                <input type="tel" name="phone" value="<?= e($user['phone_number'] ?? '') ?>" placeholder="+91 98765 43210" class="flex-1 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                <button type="submit" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-semibold rounded-xl border border-slate-700 transition">
                    Save Phone
                </button>
            </form>
        </div>
    </div>
</div>

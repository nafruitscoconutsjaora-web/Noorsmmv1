<div class="space-y-6 max-w-3xl mx-auto" id="user-preferences-page">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/account" class="hover:text-white transition">&larr; Account Overview</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Preferences</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">User Display & Notification Preferences</h1>
            <p class="text-sm text-slate-400 mt-1">Customize interface themes, regional timezone, and notification channels.</p>
        </div>
    </div>

    <!-- Preferences Form -->
    <form action="/account/preferences" method="POST" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6 shadow-sm">
        <?= csrf_field() ?>

        <!-- Regional Settings -->
        <div>
            <h2 class="text-sm font-bold text-white mb-4">Regional & Timezone</h2>
            <div class="max-w-md">
                <label class="block text-xs font-semibold text-slate-300 mb-1.5">Account Timezone</label>
                <select name="timezone" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    <option value="Asia/Kolkata" <?= ($prefs['timezone'] ?? '') === 'Asia/Kolkata' ? 'selected' : '' ?>>Asia/Kolkata (IST +5:30)</option>
                    <option value="UTC" <?= ($prefs['timezone'] ?? '') === 'UTC' ? 'selected' : '' ?>>UTC (Coordinated Universal Time)</option>
                    <option value="America/New_York" <?= ($prefs['timezone'] ?? '') === 'America/New_York' ? 'selected' : '' ?>>America/New_York (EST/EDT)</option>
                    <option value="Europe/London" <?= ($prefs['timezone'] ?? '') === 'Europe/London' ? 'selected' : '' ?>>Europe/London (GMT/BST)</option>
                    <option value="Asia/Dubai" <?= ($prefs['timezone'] ?? '') === 'Asia/Dubai' ? 'selected' : '' ?>>Asia/Dubai (GST +4:00)</option>
                </select>
            </div>
        </div>

        <hr class="border-slate-800">

        <!-- Theme Mode -->
        <div>
            <h2 class="text-sm font-bold text-white mb-4">Appearance Theme</h2>
            <div class="grid grid-cols-2 gap-4 max-w-md">
                <label class="p-4 rounded-xl border cursor-pointer transition flex items-center gap-3 <?= ($prefs['theme'] ?? 'dark') === 'dark' ? 'bg-indigo-600/10 border-indigo-500 text-white' : 'bg-slate-950 border-slate-800 text-slate-400' ?>">
                    <input type="radio" name="theme" value="dark" <?= ($prefs['theme'] ?? 'dark') === 'dark' ? 'checked' : '' ?> class="text-indigo-600">
                    <div>
                        <span class="text-xs font-bold block text-white">Dark Slate (Default)</span>
                        <span class="text-[11px] text-slate-400">High contrast dark background</span>
                    </div>
                </label>
                <label class="p-4 rounded-xl border cursor-pointer transition flex items-center gap-3 <?= ($prefs['theme'] ?? '') === 'light' ? 'bg-indigo-600/10 border-indigo-500 text-white' : 'bg-slate-950 border-slate-800 text-slate-400' ?>">
                    <input type="radio" name="theme" value="light" <?= ($prefs['theme'] ?? '') === 'light' ? 'checked' : '' ?> class="text-indigo-600">
                    <div>
                        <span class="text-xs font-bold block text-white">Classic Light</span>
                        <span class="text-[11px] text-slate-400">Bright clean office styling</span>
                    </div>
                </label>
            </div>
        </div>

        <hr class="border-slate-800">

        <!-- Notification Channels -->
        <div>
            <h2 class="text-sm font-bold text-white mb-4">Notification Alerts</h2>
            <div class="space-y-4 max-w-md">
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="email_order_updates" value="1" <?= !empty($prefs['email_order_updates']) ? 'checked' : '' ?> class="mt-0.5 rounded border-slate-700 bg-slate-950 text-indigo-600">
                    <div>
                        <span class="text-xs font-semibold text-white block">Email Order Status Notifications</span>
                        <span class="text-[11px] text-slate-400">Receive an email receipt whenever your order completes or cancels.</span>
                    </div>
                </label>
                <label class="flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="email_newsletters" value="1" <?= !empty($prefs['email_newsletters']) ? 'checked' : '' ?> class="mt-0.5 rounded border-slate-700 bg-slate-950 text-indigo-600">
                    <div>
                        <span class="text-xs font-semibold text-white block">Promotional Announcements & Price Drops</span>
                        <span class="text-[11px] text-slate-400">Get notified when new wholesale services or bonus deposit promotions go live.</span>
                    </div>
                </label>
            </div>
        </div>

        <div class="pt-2">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                Save Preferences
            </button>
        </div>
    </form>
</div>

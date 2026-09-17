<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <a href="/tickets" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-white transition mb-3">
            &larr; Back to Tickets
        </a>
        <h1 class="text-2xl font-bold text-white tracking-tight">Open Support Ticket</h1>
        <p class="text-xs text-slate-400 mt-1">Provide details regarding your order, refund, or technical question.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-xl">
        <form action="/tickets" method="POST" class="space-y-5">
            <?= csrf_field() ?>

            <div>
                <label for="subject" class="block text-xs font-semibold text-slate-300 mb-1.5">Subject</label>
                <input type="text" id="subject" name="subject" required placeholder="e.g. Order #10429 Drop Refill Request"
                    value="<?= e(old('subject')) ?>"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
            </div>

            <div>
                <label for="priority" class="block text-xs font-semibold text-slate-300 mb-1.5">Priority Level</label>
                <select id="priority" name="priority" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition">
                    <option value="low">Low - General inquiry</option>
                    <option value="medium" selected>Medium - Order status / refill check</option>
                    <option value="high">High - Payment / API issue</option>
                </select>
            </div>

            <div>
                <label for="message" class="block text-xs font-semibold text-slate-300 mb-1.5">Message / Details</label>
                <textarea id="message" name="message" rows="5" required placeholder="Please describe your issue thoroughly, including relevant Order IDs and target links..."
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-750 text-slate-200 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none transition"><?= e(old('message')) ?></textarea>
            </div>

            <button type="submit" class="w-full py-3 px-4 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-md shadow-indigo-600/20 transition">
                Submit Ticket
            </button>
        </form>
    </div>
</div>

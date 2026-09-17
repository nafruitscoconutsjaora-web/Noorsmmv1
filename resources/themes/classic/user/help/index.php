<div class="space-y-8 max-w-5xl mx-auto" id="help-center-page">
    <!-- Header -->
    <div class="text-center max-w-2xl mx-auto space-y-3 pt-4">
        <h1 class="text-3xl font-extrabold text-white">Help & Knowledge Center</h1>
        <p class="text-sm text-slate-400">Everything you need to know about placing orders, links formatting, API integration, and refill warranties.</p>
    </div>

    <!-- Search / Filter Bar -->
    <div class="max-w-xl mx-auto">
        <form method="GET" action="/help" class="relative">
            <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Search guides, tutorials, and FAQs..." class="w-full bg-slate-900 border border-slate-800 rounded-2xl py-3.5 pl-12 pr-4 text-sm text-white focus:outline-none focus:border-indigo-500 shadow-sm">
            <svg class="w-5 h-5 text-slate-500 absolute left-4 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </form>
    </div>

    <!-- Knowledge Base Articles by Category -->
    <div class="space-y-4">
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
            <span>📚</span> Guides & Tutorials
        </h2>
        
        <?php if (empty($articles)): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-500 text-xs">
                No guides found matching your search term.
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($articles as $a): ?>
                    <a href="/help/article/<?= e($a['slug']) ?>" class="bg-slate-900 border border-slate-800 rounded-2xl p-5 hover:border-indigo-500/50 transition group flex flex-col justify-between">
                        <div>
                            <span class="text-[10px] font-mono uppercase text-indigo-400 font-bold"><?= e($a['category']) ?></span>
                            <h3 class="text-sm font-bold text-white mt-1 group-hover:text-indigo-400 transition leading-snug"><?= e($a['title']) ?></h3>
                            <p class="text-xs text-slate-400 mt-2 line-clamp-2"><?= strip_tags(substr($a['content'], 0, 150)) ?>...</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500">
                            <span>Read Guide &rarr;</span>
                            <span class="font-mono"><?= substr($a['created_at'], 0, 10) ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Frequently Asked Questions -->
    <div class="space-y-4 pt-4">
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
            <span>❓</span> Frequently Asked Questions (FAQs)
        </h2>

        <?php if (empty($faqs)): ?>
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-8 text-center text-slate-500 text-xs">
                No FAQs available.
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($faqs as $idx => $f): ?>
                    <details class="group bg-slate-900 border border-slate-800 rounded-2xl p-5 open:border-slate-700 transition">
                        <summary class="cursor-pointer font-bold text-sm text-white flex items-center justify-between list-none">
                            <span><?= e($f['question']) ?></span>
                            <span class="text-slate-500 group-open:rotate-180 transition transform text-xs">&darr;</span>
                        </summary>
                        <div class="mt-3 pt-3 border-t border-slate-800 text-xs text-slate-300 leading-relaxed">
                            <?= nl2br(e($f['answer'])) ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Still need help card -->
    <div class="bg-gradient-to-r from-indigo-900/40 to-slate-900 border border-indigo-500/20 rounded-2xl p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-white">Can't find what you're looking for?</h3>
            <p class="text-xs text-slate-400 mt-0.5">Our support engineers are available 24/7 to assist with your orders or technical questions.</p>
        </div>
        <a href="/tickets/new" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm shrink-0">
            Open Support Ticket
        </a>
    </div>
</div>

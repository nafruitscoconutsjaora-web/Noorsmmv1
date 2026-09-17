<div class="space-y-6 max-w-4xl mx-auto" id="kb-article-page">
    <!-- Breadcrumb Header -->
    <div class="flex items-center gap-2 text-xs text-slate-400">
        <a href="/help" class="hover:text-white transition">&larr; Knowledge Center</a>
        <span class="text-slate-600">/</span>
        <span class="text-indigo-400 font-semibold"><?= e($article['category']) ?></span>
    </div>

    <!-- Article Content -->
    <article class="bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-sm space-y-6">
        <div>
            <span class="text-xs font-mono uppercase text-indigo-400 font-bold"><?= e($article['category']) ?></span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-white mt-2 leading-tight"><?= e($article['title']) ?></h1>
            <div class="text-xs text-slate-500 font-mono mt-3">Published: <?= substr($article['created_at'], 0, 10) ?></div>
        </div>

        <hr class="border-slate-800">

        <div class="prose prose-invert max-w-none text-slate-300 text-sm leading-relaxed space-y-4">
            <?= nl2br(e($article['content'])) ?>
        </div>

        <div class="pt-6 border-t border-slate-800 flex items-center justify-between">
            <a href="/help" class="text-xs text-indigo-400 hover:underline">&larr; Back to all articles</a>
            <a href="/tickets/new" class="text-xs text-slate-400 hover:text-white transition">Have a question? Open a ticket &rarr;</a>
        </div>
    </article>
</div>

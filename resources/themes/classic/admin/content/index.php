<div class="space-y-6 max-w-7xl mx-auto" id="admin-content-management">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400">
                <a href="/admin" class="hover:text-white transition">&larr; Admin</a>
                <span class="text-slate-600">/</span>
                <span class="text-indigo-400 font-semibold">Content Management</span>
            </div>
            <h1 class="text-2xl font-extrabold text-white mt-1">Knowledge Base & FAQ CMS</h1>
            <p class="text-sm text-slate-400 mt-1">Publish tutorials, user help guides, and frequently asked questions.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Add Article Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-base font-bold text-white mb-2">Publish Knowledge Base Article</h2>
            <p class="text-xs text-slate-400 mb-4">Write documentation for users in the Help Center.</p>

            <form action="/admin/content/articles" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Category</label>
                        <input type="text" name="category" required placeholder="e.g. Orders, API, Billing" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">URL Slug</label>
                        <input type="text" name="slug" required placeholder="e.g. how-to-refill-drops" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Article Title</label>
                    <input type="text" name="title" required placeholder="Article title..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Content (Markdown / Text)</label>
                    <textarea name="content" rows="6" required placeholder="Write detailed guide content..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>
                <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                    Publish Article
                </button>
            </form>
        </div>

        <!-- Add FAQ Form -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-sm">
            <h2 class="text-base font-bold text-white mb-2">Add FAQ Entry</h2>
            <p class="text-xs text-slate-400 mb-4">Question and answer items shown on the help portal.</p>

            <form action="/admin/content/faqs" method="POST" class="space-y-4">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Question</label>
                    <input type="text" name="question" required placeholder="e.g. What is the start time of Instagram views?" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Answer</label>
                    <textarea name="answer" rows="6" required placeholder="Detailed answer explanation..." class="w-full bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="0" class="w-24 bg-slate-950 border border-slate-800 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-indigo-500">
                </div>
                <button type="submit" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-xl transition shadow-sm">
                    Add FAQ
                </button>
            </form>
        </div>
    </div>

    <!-- Existing Articles List -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-sm">
        <div class="p-5 border-b border-slate-800">
            <h2 class="text-base font-bold text-white">Published Articles (<?= count($articles) ?>)</h2>
        </div>

        <?php if (empty($articles)): ?>
            <div class="p-8 text-center text-slate-500 text-xs">No articles published yet.</div>
        <?php else: ?>
            <div class="divide-y divide-slate-800/60">
                <?php foreach ($articles as $a): ?>
                    <div class="p-4 flex items-center justify-between hover:bg-slate-800/30 transition">
                        <div>
                            <span class="text-[10px] font-mono uppercase text-indigo-400 font-bold"><?= e($a['category']) ?></span>
                            <h3 class="text-sm font-bold text-white"><?= e($a['title']) ?></h3>
                            <span class="text-xs text-slate-500 font-mono">/help/article/<?= e($a['slug']) ?></span>
                        </div>
                        <form action="/admin/content/articles/<?= $a['id'] ?>/delete" method="POST" onsubmit="return confirm('Delete this article?')">
                            <?= csrf_field() ?>
                            <button type="submit" class="px-3 py-1 bg-rose-600/10 hover:bg-rose-600/20 text-rose-400 border border-rose-600/20 rounded-lg text-xs transition">
                                Delete
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

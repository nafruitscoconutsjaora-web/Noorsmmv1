<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContentRepository;

class AdminContentController extends BaseController
{
    private ContentRepository $content;

    public function __construct()
    {
        $this->content = new ContentRepository();
    }

    public function index(Request $request): Response
    {
        $articles = $this->content->getAllArticlesAdmin();
        $faqs = $this->content->getAllFaqsAdmin();

        return view('admin/content/index', [
            'articles' => $articles,
            'faqs' => $faqs,
        ], 'admin');
    }

    public function saveArticle(Request $request): Response
    {
        $id = $request->input('id') ? (int)$request->input('id') : null;
        $title = trim((string)$request->input('title'));
        $slug = trim((string)$request->input('slug')) ?: strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
        $category = trim((string)$request->input('category', 'General'));
        $content = trim((string)$request->input('content'));
        $isPublished = $request->input('is_published') === '1';

        $this->content->saveArticle([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'category' => $category,
            'content' => $content,
            'is_published' => $isPublished,
            'sort_order' => (int)$request->input('sort_order', 0),
        ]);

        flash('success', 'Knowledge base article saved successfully.');
        return $this->redirect('/admin/content');
    }

    public function saveFaq(Request $request): Response
    {
        $id = $request->input('id') ? (int)$request->input('id') : null;
        $question = trim((string)$request->input('question'));
        $answer = trim((string)$request->input('answer'));
        $category = trim((string)$request->input('category', 'General'));
        $isPublished = $request->input('is_published') === '1';

        $this->content->saveFaq([
            'id' => $id,
            'question' => $question,
            'answer' => $answer,
            'category' => $category,
            'is_published' => $isPublished,
            'sort_order' => (int)$request->input('sort_order', 0),
        ]);

        flash('success', 'FAQ item saved successfully.');
        return $this->redirect('/admin/content');
    }
}

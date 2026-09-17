<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\ContentRepository;

class HelpController extends BaseController
{
    private ContentRepository $content;

    public function __construct()
    {
        $this->content = new ContentRepository();
    }

    public function index(Request $request): Response
    {
        $category = $request->query('category');
        $articles = $this->content->getPublishedArticles($category);
        $faqs = $this->content->getPublishedFaqs($category);

        return view('user/help/index', [
            'articles' => $articles,
            'faqs' => $faqs,
            'selected_category' => $category,
        ], 'user');
    }

    public function article(Request $request, string $slug): Response
    {
        $article = $this->content->findArticleBySlug($slug);
        if (!$article) {
            flash('error', 'Article not found.');
            return $this->redirect('/help');
        }

        return view('user/help/article', [
            'article' => $article,
        ], 'user');
    }
}

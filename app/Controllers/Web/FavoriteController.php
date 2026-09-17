<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\FavoriteRepository;

class FavoriteController extends BaseController
{
    private FavoriteRepository $favorites;

    public function __construct()
    {
        $this->favorites = new FavoriteRepository();
    }

    public function index(Request $request): Response
    {
        $user = $this->user();
        $items = $this->favorites->getFavorites($user['id']);

        // Group by category
        $categories = [];
        foreach ($items as $s) {
            $cat = $s['category_name'];
            $categories[$cat][] = $s;
        }

        return view('user/services/favorites', [
            'favorites' => $items,
            'grouped' => $categories,
        ], 'user');
    }

    public function toggle(Request $request, string $id): Response
    {
        $user = $this->user();
        $serviceId = (int)$id;

        $added = $this->favorites->toggle($user['id'], $serviceId);

        if ($request->isAjax()) {
            return $this->json(['success' => true, 'is_favorite' => $added]);
        }

        flash('success', $added ? 'Service saved to your favorites.' : 'Service removed from your favorites.');
        return $this->redirect('/services-list');
    }
}

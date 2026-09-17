<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ServiceRepository;

class ServiceController extends BaseController
{
    private ServiceRepository $serviceRepo;
    private CategoryRepository $categoryRepo;

    public function __construct()
    {
        $this->serviceRepo = new ServiceRepository();
        $this->categoryRepo = new CategoryRepository();
    }

    public function index(Request $request): Response
    {
        $categories = $this->categoryRepo->getAllActive();
        $services = $this->serviceRepo->getAllActiveWithCategory();

        return view('user/services/index', [
            'categories' => $categories,
            'services' => $services,
        ], 'user');
    }
}

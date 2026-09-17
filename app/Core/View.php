<?php

declare(strict_types=1);

namespace App\Core;

use App\Exceptions\NotFoundException;

class View
{
    private string $theme;
    private string $themePath;

    public function __construct(?string $theme = null)
    {
        $this->theme = $theme ?? config('app.theme', 'classic');
        $this->themePath = dirname(__DIR__, 2) . '/resources/themes/' . $this->theme;
    }

    public function render(string $template, array $data = [], ?string $layout = null): string
    {
        $viewFile = $this->resolvePath($template);
        if (!file_exists($viewFile)) {
            throw new NotFoundException("View template not found: {$template} at {$viewFile}");
        }

        // Render template to buffer
        $content = $this->renderPhpFile($viewFile, $data);

        // If layout specified or template defines layout
        if ($layout !== null) {
            $layoutFile = $this->themePath . '/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new NotFoundException("Layout file not found: {$layoutFile}");
            }
            $layoutData = array_merge($data, ['content' => $content]);
            return $this->renderPhpFile($layoutFile, $layoutData);
        }

        return $content;
    }

    public function component(string $name, array $data = []): string
    {
        $componentFile = $this->themePath . '/components/' . $name . '.php';
        if (!file_exists($componentFile)) {
            return "<!-- Component {$name} not found -->";
        }
        return $this->renderPhpFile($componentFile, $data);
    }

    private function resolvePath(string $template): string
    {
        // template can be 'landing/index' or 'user/dashboard'
        return $this->themePath . '/' . ltrim($template, '/') . '.php';
    }

    private function renderPhpFile(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return ob_get_clean() ?: '';
    }

    public function getTheme(): string
    {
        return $this->theme;
    }
}

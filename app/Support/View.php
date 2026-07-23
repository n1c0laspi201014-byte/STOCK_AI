<?php
declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'layouts/app'): string
    {
        $viewFile = base_path('app/Views/' . $view . '.php');
        if (!is_file($viewFile)) {
            throw new RuntimeException("View not found: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $viewFile;
        $content = (string) ob_get_clean();

        if ($layout === '') {
            return $content;
        }
        $layoutFile = base_path('app/Views/' . $layout . '.php');
        if (!is_file($layoutFile)) {
            throw new RuntimeException("Layout not found: {$layout}");
        }
        ob_start();
        require $layoutFile;
        return (string) ob_get_clean();
    }

    public static function display(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        echo self::render($view, $data, $layout);
    }
}


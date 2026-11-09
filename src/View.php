<?php

declare(strict_types=1);

namespace App;

final class View
{
    public function __construct(private readonly string $templateDir)
    {
    }

    /** @param array<string, mixed> $data */
    public function render(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        $content = $this->capture($template, $data);

        echo $this->capture('layout', [
            'content' => $content,
            'title'   => $data['title'] ?? 'Prêts entre amis',
            'flashes' => Flash::pull(),
        ]);
    }

    /** @param array<string, mixed> $data */
    private function capture(string $template, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $this->templateDir . '/' . $template . '.php';

        return (string) ob_get_clean();
    }
}

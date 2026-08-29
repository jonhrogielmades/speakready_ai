<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminModalMarkupTest extends TestCase
{
    public function test_admin_modals_are_not_nested_inside_table_bodies(): void
    {
        $views = [
            'resources/views/desktop/admin/categories.blade.php',
            'resources/views/desktop/admin/questions.blade.php',
            'resources/views/desktop/admin/game.blade.php',
            'resources/views/mobile/admin/categories.blade.php',
            'resources/views/mobile/admin/questions.blade.php',
            'resources/views/mobile/admin/game.blade.php',
        ];

        foreach ($views as $view) {
            $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$view;

            $this->assertFileExists($path);

            $contents = file_get_contents($path);

            preg_match_all('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $contents, $matches);

            $this->assertNotEmpty($matches[1], $view.' should contain at least one table body.');

            foreach ($matches[1] as $tbody) {
                $this->assertStringNotContainsString(
                    '<div class="modal',
                    $tbody,
                    $view.' contains modal markup inside a table body.'
                );
            }
        }
    }
}

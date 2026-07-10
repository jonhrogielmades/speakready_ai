<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminModalMarkupTest extends TestCase
{
    public function test_admin_modals_are_not_nested_inside_table_bodies(): void
    {
        $views = [
            'resources/views/admin/categories.blade.php',
            'resources/views/admin/questions.blade.php',
            'resources/views/admin/game.blade.php',
        ];

        foreach ($views as $view) {
            $contents = file_get_contents(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$view);

            preg_match_all('/<tbody\b[^>]*>(.*?)<\/tbody>/is', $contents, $matches);

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

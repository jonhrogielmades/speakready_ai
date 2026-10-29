<?php

namespace Tests\Unit;

use App\Http\Controllers\UserController;
use ReflectionMethod;
use Tests\TestCase;

class CoachAttachmentDecompressionTest extends TestCase
{
    public function test_document_inflation_rejects_output_larger_than_its_budget(): void
    {
        $controller = app(UserController::class);
        $inflate = new ReflectionMethod($controller, 'inflateWithLimit');
        $compressed = gzdeflate(str_repeat('A', 1024 * 1024));

        $this->assertIsString($compressed);
        $this->assertSame('', $inflate->invoke($controller, $compressed, 4096, false));
    }

    public function test_document_inflation_accepts_small_bounded_content(): void
    {
        $controller = app(UserController::class);
        $inflate = new ReflectionMethod($controller, 'inflateWithLimit');
        $plainText = '<document><p>Interview preparation notes</p></document>';
        $compressed = gzdeflate($plainText);

        $this->assertIsString($compressed);
        $this->assertSame($plainText, $inflate->invoke($controller, $compressed, 4096, false));
    }
}

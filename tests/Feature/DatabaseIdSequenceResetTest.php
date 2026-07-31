<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseIdSequenceResetTest extends TestCase
{
    private string $table = 'sequence_reset_examples';

    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists($this->table);
        Schema::create($this->table, function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists($this->table);

        parent::tearDown();
    }

    public function test_id_sequence_resets_to_one_after_table_is_emptied_by_delete(): void
    {
        $firstId = $this->insertExample('First record');
        $secondId = $this->insertExample('Second record');
        $thirdId = $this->insertExample('Third record');

        $this->assertSame(1, $firstId);
        $this->assertSame(2, $secondId);
        $this->assertSame(3, $thirdId);

        DB::table($this->table)->delete();

        $nextId = $this->insertExample('Next record');

        $this->assertSame(1, $nextId);
    }

    public function test_id_sequence_does_not_reuse_ids_while_table_still_has_rows(): void
    {
        $firstId = $this->insertExample('First record');
        $secondId = $this->insertExample('Second record');
        $thirdId = $this->insertExample('Third record');

        DB::table($this->table)->where('id', $firstId)->delete();

        $nextId = $this->insertExample('Next record');

        $this->assertSame(2, $secondId);
        $this->assertSame(3, $thirdId);
        $this->assertSame(4, $nextId);
    }

    private function insertExample(string $name): int
    {
        return (int) DB::table($this->table)->insertGetId([
            'name' => $name,
        ]);
    }
}

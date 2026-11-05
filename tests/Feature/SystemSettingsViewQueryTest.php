<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Support\SystemSettings;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SystemSettingsViewQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_view_settings_use_one_query_and_preserve_values_defaults_and_request_cache(): void
    {
        Setting::setVal('sys_name', 'Updated Name', 'general');
        Setting::setVal('acc_registration', false, 'account', 'boolean');
        Setting::setVal('sys_desc', null, 'general');
        Setting::setVal('file_max_size', 24, 'files', 'integer');

        $settingsSelects = 0;
        DB::listen(function (QueryExecuted $query) use (&$settingsSelects): void {
            if (preg_match('/^select\b.*\bfrom\s+["`]?settings(?:["`]|\b)/i', $query->sql)) {
                $settingsSelects++;
            }
        });

        $values = SystemSettings::forView();

        $this->assertSame(1, $settingsSelects);
        $this->assertCount(count(SystemSettings::definitions()), $values);
        $this->assertSame('Updated Name', $values['sys_name']);
        $this->assertSame('false', $values['acc_registration']);
        $this->assertSame((string) SystemSettings::default('sys_desc'), $values['sys_desc']);
        $this->assertSame('24', $values['file_max_size']);
        $this->assertSame('Updated Name', Setting::getVal('sys_name'));
        $this->assertSame(false, Setting::getVal('acc_registration'));
        $this->assertSame(1, $settingsSelects, 'Single-key reads should reuse the batch-loaded request cache.');

        Setting::setVal('sys_name', 'Changed Again', 'general');
        $selectsAfterWrite = $settingsSelects;

        $this->assertSame('Changed Again', Setting::getVal('sys_name'));
        $this->assertSame($selectsAfterWrite, $settingsSelects, 'setVal should refresh the request cache.');
        $this->assertSame('Changed Again', SystemSettings::forView()['sys_name']);
        $this->assertSame($selectsAfterWrite + 1, $settingsSelects);
    }

    public function test_login_and_register_pages_do_not_issue_one_query_per_setting(): void
    {
        $settingsSelects = 0;
        DB::listen(function (QueryExecuted $query) use (&$settingsSelects): void {
            if (preg_match('/^select\b.*\bfrom\s+["`]?settings(?:["`]|\b)/i', $query->sql)) {
                $settingsSelects++;
            }
        });

        foreach ([route('login'), route('register')] as $url) {
            $settingsSelects = 0;
            $this->get($url)->assertOk();
            $this->assertLessThanOrEqual(6, $settingsSelects, $url.' should batch its view settings.');
        }
    }
}

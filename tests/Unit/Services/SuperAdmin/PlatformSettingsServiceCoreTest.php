<?php

namespace Tests\Unit\Services\SuperAdmin;

use App\Models\PlatformSetting;
use App\Services\SuperAdmin\PlatformSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class PlatformSettingsServiceCoreTest extends TestCase
{
    use RefreshDatabase;

    private PlatformSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::forget(PlatformSettingsService::CACHE_KEY);
        $this->service = app(PlatformSettingsService::class);
    }

    public function test_get_bool_interprets_truthy_and_falsy_values(): void
    {
        PlatformSetting::query()->insert([
            ['key' => 'flag_on', 'value' => '1', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'flag_yes', 'value' => 'yes', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'flag_off', 'value' => '0', 'created_at' => now(), 'updated_at' => now()],
        ]);
        Cache::forget(PlatformSettingsService::CACHE_KEY);

        $this->assertTrue($this->service->getBool('flag_on'));
        $this->assertTrue($this->service->getBool('flag_yes'));
        $this->assertFalse($this->service->getBool('flag_off'));
        $this->assertFalse($this->service->getBool('missing', false));
        $this->assertTrue($this->service->getBool('missing', true));
    }

    public function test_get_int_returns_numeric_value_or_default(): void
    {
        PlatformSetting::query()->updateOrCreate(
            ['key' => 'custom_int_setting'],
            ['value' => '21'],
        );
        Cache::forget(PlatformSettingsService::CACHE_KEY);

        $this->assertSame(21, $this->service->getInt('custom_int_setting'));
        $this->assertSame(14, $this->service->getInt('missing_int', 14));

        PlatformSetting::query()->updateOrCreate(
            ['key' => 'bad_int'],
            ['value' => 'not-a-number'],
        );
        Cache::forget(PlatformSettingsService::CACHE_KEY);
        $this->assertSame(7, $this->service->getInt('bad_int', 7));
    }

    public function test_set_many_persists_values_and_clears_cache(): void
    {
        Cache::put(PlatformSettingsService::CACHE_KEY, ['stale' => '1'], 300);

        $this->service->setMany([
            'platform_name' => 'Northline CRM',
            'registration_enabled' => true,
            'smtp_port' => 587,
        ]);

        $this->assertFalse(Cache::has(PlatformSettingsService::CACHE_KEY));
        $this->assertSame('Northline CRM', $this->service->get('platform_name'));
        $this->assertTrue($this->service->getBool('registration_enabled'));
        $this->assertSame(587, $this->service->getInt('smtp_port'));
    }

    public function test_set_encrypted_and_get_decrypted_round_trip(): void
    {
        $this->service->setEncrypted('smtp_password', 'secret-smtp-pass');

        $stored = PlatformSetting::query()->where('key', 'smtp_password')->value('value');
        $this->assertNotSame('secret-smtp-pass', $stored);

        Cache::forget(PlatformSettingsService::CACHE_KEY);
        $this->assertSame('secret-smtp-pass', $this->service->getDecrypted('smtp_password'));
    }

    public function test_get_decrypted_returns_default_for_invalid_payload(): void
    {
        PlatformSetting::query()->create([
            'key' => 'smtp_password',
            'value' => 'not-encrypted',
        ]);
        Cache::forget(PlatformSettingsService::CACHE_KEY);

        $this->assertSame('fallback', $this->service->getDecrypted('smtp_password', 'fallback'));
    }

    public function test_set_encrypted_skips_blank_values(): void
    {
        $this->service->setEncrypted('smtp_password', '');

        $this->assertNull(PlatformSetting::query()->where('key', 'smtp_password')->value('value'));
    }

    public function test_announcement_returns_null_when_blank(): void
    {
        $this->assertNull($this->service->announcement());

        $this->service->setMany(['broadcast_announcement' => 'Scheduled maintenance tonight.']);

        $this->assertSame('Scheduled maintenance tonight.', $this->service->announcement());
    }

    public function test_email_verification_required_defaults_to_true(): void
    {
        $this->assertTrue($this->service->emailVerificationRequired());
    }

    public function test_platform_name_falls_back_to_app_name(): void
    {
        PlatformSetting::query()->where('key', 'platform_name')->delete();
        Cache::forget(PlatformSettingsService::CACHE_KEY);

        config(['app.name' => 'Algos CRM']);

        $this->assertSame('Algos CRM', $this->service->platformName());
    }
}

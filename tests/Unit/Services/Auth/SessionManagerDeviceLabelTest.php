<?php

namespace Tests\Unit\Services\Auth;

use App\Services\Auth\SessionManager;
use Tests\TestCase;

class SessionManagerDeviceLabelTest extends TestCase
{
    private SessionManager $sessionManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sessionManager = app(SessionManager::class);
    }

    public function test_device_label_returns_unknown_device_for_empty_user_agent(): void
    {
        $this->assertSame('Unknown device', $this->sessionManager->deviceLabel(null));
        $this->assertSame('Unknown device', $this->sessionManager->deviceLabel(''));
        $this->assertSame('Unknown device', $this->sessionManager->deviceLabel('   '));
    }

    public function test_device_label_detects_chrome_on_windows(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

        $this->assertSame('Chrome on Windows', $this->sessionManager->deviceLabel($userAgent));
    }

    public function test_device_label_detects_edge_instead_of_chrome(): void
    {
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0';

        $this->assertSame('Edge on Windows', $this->sessionManager->deviceLabel($userAgent));
    }

    public function test_device_label_detects_firefox_on_linux(): void
    {
        $userAgent = 'Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0';

        $this->assertSame('Firefox on Linux', $this->sessionManager->deviceLabel($userAgent));
    }

    public function test_device_label_detects_safari_on_macos(): void
    {
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_2) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15';

        $this->assertSame('Safari on macOS', $this->sessionManager->deviceLabel($userAgent));
    }

    public function test_device_label_detects_chrome_on_android(): void
    {
        $userAgent = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36';

        $this->assertSame('Chrome on Android', $this->sessionManager->deviceLabel($userAgent));
    }

    public function test_device_label_detects_safari_on_ios(): void
    {
        $userAgent = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1';

        $this->assertSame('Safari on iOS', $this->sessionManager->deviceLabel($userAgent));
    }

    public function test_device_label_detects_ipad_as_ios(): void
    {
        $userAgent = 'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1';

        $this->assertSame('Safari on iOS', $this->sessionManager->deviceLabel($userAgent));
    }

    public function test_device_label_falls_back_to_generic_browser_and_unknown_os(): void
    {
        $userAgent = 'CustomBot/1.0';

        $this->assertSame('Browser on Unknown OS', $this->sessionManager->deviceLabel($userAgent));
    }
}

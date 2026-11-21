<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileAppPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_download_page_is_public_and_explains_installation(): void
    {
        $this->get(route('mobile.app'))
            ->assertOk()
            ->assertSee('Application mobile')
            ->assertSee('Télécharger l’APK')
            ->assertSee('même réseau Wi-Fi');
    }

    public function test_unknown_page_uses_the_localized_404_view(): void
    {
        $this->get('/cette-page-nexiste-pas')
            ->assertNotFound()
            ->assertSee('Page introuvable')
            ->assertSee('Retour à l’accueil');
    }

    public function test_download_returns_503_when_no_release_is_published(): void
    {
        Storage::fake('local');

        $this->get(route('mobile.app.download'))->assertStatus(503);
    }

    public function test_signed_download_link_is_available_without_login(): void
    {
        $this->getJson('/api/mobile/v1/app-update/download-url')
            ->assertOk()
            ->assertJsonStructure(['url']);
    }

    public function test_published_apk_can_be_downloaded_without_login(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('mobile-updates/android.json', json_encode([
            'version_code' => 3,
            'version_name' => '1.0.2',
            'sha256' => 'empreinte-factice',
        ], JSON_THROW_ON_ERROR));
        Storage::disk('local')->put('mobile-updates/latest.apk', 'apk-factice');

        $this->get(route('mobile.app'))
            ->assertOk()
            ->assertSee('Version publiée : 1.0.2');

        $response = $this->get(route('mobile.app.download'));
        $response->assertOk();
        $this->assertStringContainsString(
            'Marches-Maraichers-1.0.2.apk',
            (string) $response->headers->get('Content-Disposition'),
        );
    }
}

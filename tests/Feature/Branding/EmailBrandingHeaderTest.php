<?php

namespace Tests\Feature\Branding;

use Core\Branding\BrandingResolver;
use Core\Contracts\OrganizationContext;
use Core\Contracts\SettingsRepository;
use Core\Organization\Models\Organization;
use Core\Settings\Enums\SettingScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmailBrandingHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_renders_logo_image_when_branding_logo_set(): void
    {
        Storage::fake('public');
        $org = Organization::factory()->create();
        app(OrganizationContext::class)->set($org);
        app(SettingsRepository::class)->set('branding.logo', 'brand/logo.png', SettingScope::Organization, $org->id);

        $html = (string) app(Markdown::class)->render('mail::message', ['level' => 'info', 'slot' => 'Body content.']);

        $this->assertStringContainsString(
            '<img src="'.app(BrandingResolver::class)->url('branding.logo').'"',
            $html,
        );
    }

    public function test_header_renders_app_name_text_when_no_logo_set(): void
    {
        $html = (string) app(Markdown::class)->render('mail::message', ['level' => 'info', 'slot' => 'Body content.']);

        $this->assertStringContainsString((string) config('app.name'), $html);
        $this->assertStringNotContainsString('<img', $html);
    }
}

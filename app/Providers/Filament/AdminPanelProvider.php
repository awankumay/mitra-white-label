<?php

namespace App\Providers\Filament;

use App\Filament\Resources\Users\UserResource;
use App\Http\Middleware\ForceSuperAdminTwoFactor;
use App\Livewire\Security\BrowserSessions;
use App\Livewire\Security\Passkeys;
use App\Livewire\Security\TwoFactorAuthentication;
use App\Livewire\Security\UpdatePassword;
use App\Models\User;
use Awcodes\QuickCreate\QuickCreatePlugin;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use CraftForge\FilamentLanguageSwitcher\FilamentLanguageSwitcherPlugin;
use DutchCodingCompany\FilamentDeveloperLogins\FilamentDeveloperLoginsPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use FilamentWhiteLabel\Resources\WhiteLabelSettingsResource;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\View\View;
use Jacobtims\FilamentLogger\FilamentLoggerPlugin;
use Jeffgreco13\FilamentBreezy\BreezyCore;
use SpyApp\ThemeEdinburgh\ThemeEdinburghPlugin;
use Swis\Filament\Backgrounds\FilamentBackgroundsPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->colors([
                'primary' => Color::Taupe,
            ])
            ->whiteLabel()                                      // wires up branding
            ->resources([
                WhiteLabelSettingsResource::class,              // adds the nav item
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 3,
                    ])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                        'lg' => 4,
                    ])
                    ->resourceCheckboxListColumns([
                        'default' => 1,
                        'sm' => 2,
                    ]),
                ThemeEdinburghPlugin::make(),
                FilamentBackgroundsPlugin::make(),
                FilamentLoggerPlugin::make(),
                BreezyCore::make()
                    ->myProfile()
                    ->myProfileComponents([
                        'update_password' => UpdatePassword::class,
                        'two_factor_authentication' => TwoFactorAuthentication::class,
                        'passkeys' => Passkeys::class,
                        'browser_sessions' => BrowserSessions::class,
                    ])
                    ->enableTwoFactorAuthentication(
                        force: config('core.auth.two_factor.force'),
                    )
                    ->enablePasskeys(
                        relyingPartyName: config('core.auth.passkey.relying_party_name'),
                        relyingPartyId: config('core.auth.passkey.relying_party_id') ?: null,
                    )
                    ->enableBrowserSessions(),
                QuickCreatePlugin::make()
                    ->includes([
                        UserResource::class,
                    ]),
                FilamentDeveloperLoginsPlugin::make()
                    ->enabled(app()->environment('local'))
                    ->switchable(true)
                    ->users(fn () => User::pluck('email', 'name')->toArray()),
                FilamentLanguageSwitcherPlugin::make()
                    ->showOnAuthPages()
                    ->locales(['en', 'id'])
                    ->rememberLocale()
                    ->renderHook(PanelsRenderHook::USER_MENU_BEFORE),
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): View => view('panel.unit-switcher'),
            )
            ->sidebarWidth('14rem')
            ->authMiddleware([
                Authenticate::class,
                ForceSuperAdminTwoFactor::class,
            ])
            ->maxContentWidth(Width::Full)
            ->viteTheme('resources/css/filament/admin/theme.css');
    }
}

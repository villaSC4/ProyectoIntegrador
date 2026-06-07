<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login() 
            ->authGuard('admin')
            ->colors([
                'primary' => Color::Emerald, 
                'gray' => Color::Slate,      
            ])
            ->brandLogo(asset('imagenes/Logo/Logo-grande.webp'))
            ->brandLogoHeight('5rem')
            ->darkMode(false)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
            ])
            ->renderHook(
                'panels::styles.after', 
                fn (): string => Blade::render('
                    <style>
                        .fi-sidebar, 
                        .fi-sidebar-header { 
                            background-color: #0c4a43 !important; 
                        }

                        .fi-sidebar-header { 
                            display: flex !important;
                            justify-content: center !important;
                            align-items: center !important;
                            border-bottom: none !important;
                            padding-top: 1.5rem !important; 
                            padding-bottom: 0.25rem !important;
                        }

                        .fi-sidebar-header > a {
                            display: flex !important;
                            justify-content: center !important;
                            width: 100% !important;
                        }

                        .medisign-admin-title {
                            color: #a7f3d0 !important;
                            font-size: 0.75rem !important;
                            font-weight: 700 !important;
                            text-transform: uppercase !important;
                            letter-spacing: 0.05em !important;
                            text-align: center !important; 
                            margin-top: 0.25rem !important;
                            display: block !important;
                        }

                        .fi-sidebar-nav-label, 
                        .fi-sidebar-nav-link-label, 
                        .fi-sidebar-group-label { 
                            color: #ffffff !important; 
                        }
                        .fi-sidebar-nav-link-icon { 
                            color: #34d399 !important; 
                        }
                        .fi-sidebar-nav-link:hover {
                            background-color: #115e54 !important;
                        }
                    </style>
                '),
            )
            ->renderHook(
                'panels::sidebar.nav.start', 
                fn (): string => Blade::render('
                    <div style="padding: 0 1rem 1rem 1rem;">
                        <span class="medisign-admin-title">MediSign Administradores</span>
                    </div>
                '),
            )
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
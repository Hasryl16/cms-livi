<?php

namespace App\Filament\Admin\Pages;

use App\Services\AiApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

class Analytics extends Page
{
    protected string $view = 'filament.admin.pages.analytics';

    protected static \UnitEnum|string|null $navigationGroup = 'AI Chatbot';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;
    protected static ?string $navigationLabel = 'Analytics';
    protected static ?string $title = 'Analytics';
    protected static ?int $navigationSort = 4;

    public array $analytics = [];
    public bool $loaded = false;

    public function mount(): void
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics(): void
    {
        try {
            $this->analytics = app(AiApiService::class)->getAnalytics();
            $this->loaded    = true;
            $this->dispatch('analytics-loaded', data: $this->analytics);
        } catch (Throwable $e) {
            Notification::make()->title('Failed to load analytics')->body($e->getMessage())->danger()->send();
            $this->analytics = [];
        }
    }

    public function refresh(): void
    {
        $this->loadAnalytics();
    }
}

<?php

namespace App\Filament\Admin\Pages;

use App\Services\AiApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Throwable;

class ChatLogDetail extends Page
{
    protected string $view = 'filament.admin.pages.chat-log-detail';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Chat Session Detail';

    public string $sessionId = '';
    public array $messages = [];
    public ?array $lead = null;
    public int $messageCount = 0;
    public string $startedAt = '';

    public function mount(): void
    {
        $this->sessionId = request()->query('session_id', '');

        if (! $this->sessionId) {
            $this->redirect(ChatLogs::getUrl());
            return;
        }

        try {
            $data = app(AiApiService::class)->getChatLogDetail($this->sessionId);

            $this->messages     = $data['messages'] ?? [];
            $this->lead         = $data['lead'] ?? null;
            $this->messageCount = $data['message_count'] ?? 0;
            $this->startedAt    = $data['started_at'] ?? $this->lead['created_at'] ?? '';
        } catch (Throwable $e) {
            Notification::make()
                ->title('Failed to load session')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->redirect(ChatLogs::getUrl());
        }
    }

    public function backUrl(): string
    {
        return ChatLogs::getUrl();
    }
}

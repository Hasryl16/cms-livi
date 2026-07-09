<?php

namespace App\Filament\Admin\Pages;

use App\Services\AiApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ChatLogs extends Page
{
    protected string $view = 'filament.admin.pages.chat-logs';

    protected static \UnitEnum|string|null $navigationGroup = 'AI Chatbot';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $navigationLabel = 'Chat Logs';

    protected static ?string $title = 'Chat Log History';

    protected static ?int $navigationSort = 2;

    public string $search = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $leadStatus = '';
    public int $currentPage = 1;

    public array $sessions = [];
    public array $paginationMeta = [];

    public function mount(): void
    {
        $this->loadSessions();
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        $this->loadSessions();
    }

    public function updatedDateFrom(): void
    {
        $this->currentPage = 1;
        $this->loadSessions();
    }

    public function updatedDateTo(): void
    {
        $this->currentPage = 1;
        $this->loadSessions();
    }

    public function updatedLeadStatus(): void
    {
        $this->currentPage = 1;
        $this->loadSessions();
    }

    public function loadSessions(): void
    {
        try {
            $result = app(AiApiService::class)->getChatLogs(array_filter([
                'search'      => $this->search ?: null,
                'date_from'   => $this->dateFrom ?: null,
                'date_to'     => $this->dateTo ?: null,
                'lead_status' => $this->leadStatus ?: null,
                'page'        => $this->currentPage,
                'per_page'    => 20,
            ]));

            $this->sessions       = $result['data'] ?? [];
            $this->paginationMeta = $result['pagination'] ?? [];
        } catch (Throwable $e) {
            Notification::make()
                ->title('Failed to load chat logs')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->sessions       = [];
            $this->paginationMeta = [];
        }
    }

    public function applyFilters(): void
    {
        $this->currentPage = 1;
        $this->loadSessions();
    }

    public function resetFilters(): void
    {
        $this->search      = '';
        $this->dateFrom    = '';
        $this->dateTo      = '';
        $this->leadStatus  = '';
        $this->currentPage = 1;
        $this->loadSessions();
    }

    public function nextPage(): void
    {
        $totalPages = $this->paginationMeta['total_pages'] ?? 1;
        if ($this->currentPage < $totalPages) {
            $this->currentPage++;
            $this->loadSessions();
        }
    }

    public function prevPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadSessions();
        }
    }

    public function detailUrl(string $sessionId): string
    {
        return ChatLogDetail::getUrl(['session_id' => $sessionId]);
    }

    public function getLeadStatusLabel(mixed $leadId): string
    {
        return $leadId ? 'Lead Captured' : 'No Lead';
    }

    public function getLeadStatusColor(mixed $leadId): string
    {
        return $leadId ? 'success' : 'gray';
    }
}

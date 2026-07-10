<?php

namespace App\Filament\Admin\Pages;

use App\Services\AiApiService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Throwable;

class KnowledgeBase extends Page
{
    protected string $view = 'filament.admin.pages.knowledge-base';

    protected static \UnitEnum|string|null $navigationGroup = 'AI Chatbot';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
    protected static ?string $navigationLabel = 'Knowledge Base';
    protected static ?string $title = 'Knowledge Base';
    protected static ?int $navigationSort = 3;

    public array $documents = [];
    public array $stats = ['total' => 0, 'processing' => 0, 'failed' => 0, 'indexed' => 0];
    public bool $showUploadModal = false;

    public function mount(): void
    {
        $this->loadDocuments();
    }

    public function loadDocuments(): void
    {
        try {
            $result          = app(AiApiService::class)->getKnowledgeBaseDocuments();
            $this->documents = $result['documents'] ?? [];
            $this->stats     = $result['stats'] ?? ['total' => 0, 'processing' => 0, 'failed' => 0, 'indexed' => 0];
        } catch (Throwable $e) {
            Notification::make()->title('Failed to load documents')->body($e->getMessage())->danger()->send();
            $this->documents = [];
        }
    }

    public function openUploadModal(): void
    {
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
    }

    public function uploadDocument(): void
    {
        $file = request()->file('document');
        if (!$file) {
            Notification::make()->title('No file selected')->warning()->send();
            return;
        }

        $ext = strtolower($file->getClientOriginalExtension());
        if (!in_array($ext, ['pdf', 'docx', 'xlsx'])) {
            Notification::make()->title('Invalid file type')->body('Only PDF, DOCX, and XLSX are supported.')->danger()->send();
            return;
        }
        if ($file->getSize() > 50 * 1024 * 1024) {
            Notification::make()->title('File too large')->body('Maximum file size is 50 MB.')->danger()->send();
            return;
        }

        try {
            app(AiApiService::class)->ingestDocument($file);
            $this->showUploadModal = false;
            $this->loadDocuments();
            Notification::make()->title('Document uploaded')->body('Processing has started in the background.')->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Upload failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function rollback(int $id): void
    {
        try {
            app(AiApiService::class)->rollbackDocument($id);
            $this->loadDocuments();
            Notification::make()->title('Rollback started')->body('Previous version is being restored.')->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Rollback failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function retry(int $id): void
    {
        try {
            app(AiApiService::class)->retryDocument($id);
            $this->loadDocuments();
            Notification::make()->title('Retry started')->body('Document is being re-processed.')->success()->send();
        } catch (Throwable $e) {
            Notification::make()->title('Retry failed')->body($e->getMessage())->danger()->send();
        }
    }

    public function refresh(): void
    {
        $this->loadDocuments();
    }
}

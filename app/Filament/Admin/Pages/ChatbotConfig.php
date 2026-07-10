<?php

namespace App\Filament\Admin\Pages;

use App\Services\AiApiService;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ChatbotConfig extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.admin.pages.chatbot-config';

    protected static \UnitEnum|string|null $navigationGroup = 'AI Chatbot';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'System Configuration';

    protected static ?string $title = 'System Configuration';

    protected static ?int $navigationSort = 1;

    public ?array $data = [];

    private string $lastUpdatedBy = '';
    private string $lastUpdatedAt = '';

    public function mount(): void
    {
        try {
            $config = app(AiApiService::class)->getConfig();

            $this->lastUpdatedBy = $config['system_prompt']['updated_by'] ?? '';
            $this->lastUpdatedAt = $config['system_prompt']['updated_at'] ?? '';

            $keywords = $config['lead_trigger_keywords']['value'] ?? '';

            $this->form->fill([
                'system_prompt'         => $config['system_prompt']['value'] ?? '',
                'welcome_message'       => $config['welcome_message']['value'] ?? '',
                'lead_trigger_keywords' => $keywords ? explode(',', $keywords) : [],
            ]);
        } catch (Throwable $e) {
            Notification::make()
                ->title('Could not load configuration')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Textarea::make('system_prompt')
                    ->label('System Prompt')
                    ->required()
                    ->rows(6)
                    ->helperText('Persona & behavior rules. Changes take effect on the next chatbot request.')
                    ->columnSpanFull(),

                TextInput::make('welcome_message')
                    ->label('Welcome Message')
                    ->required()
                    ->helperText('First message shown to the user when the chatbot opens.')
                    ->columnSpanFull(),

                TagsInput::make('lead_trigger_keywords')
                    ->label('Lead Trigger Keywords')
                    ->separator(',')
                    ->helperText('Words that trigger lead capture flow in n8n.')
                    ->columnSpanFull(),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $updatedBy = auth()->user()?->name ?? auth()->user()?->email ?? 'admin';
        $service = app(AiApiService::class);

        try {
            $service->setConfig('system_prompt', $data['system_prompt'], $updatedBy);
            $service->setConfig('welcome_message', $data['welcome_message'], $updatedBy);
            $keywords = $data['lead_trigger_keywords'] ?? [];
            $service->setConfig(
                'lead_trigger_keywords',
                is_array($keywords) ? implode(',', $keywords) : $keywords,
                $updatedBy
            );

            Notification::make()
                ->title('Configuration saved')
                ->success()
                ->send();
        } catch (Throwable $e) {
            Notification::make()
                ->title('Failed to save configuration')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getLastUpdatedInfo(): string
    {
        if (! $this->lastUpdatedBy) {
            return '';
        }

        return "Last updated by {$this->lastUpdatedBy}";
    }
}

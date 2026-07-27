<?php

declare(strict_types=1);

namespace TresPontosTech\PanelApp\Filament\Widgets;

use App\Models\Users\User;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use TresPontosTech\Consultants\Models\Document;
use TresPontosTech\PanelApp\Filament\Resources\SharedDocuments\SharedDocumentResource;

class SharedMaterialsWidget extends Widget implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    protected string $view = 'filament.app.widgets.shared-materials';

    protected int|string|array $columnSpan = ['default' => 'full', 'md' => 5];

    private const PREVIEW_LIMIT = 4;

    /**
     * @var array<string, Document|null>
     */
    private array $resolvedDocuments = [];

    protected function getViewData(): array
    {
        /** @var User $user */
        $user = auth()->user();

        $sharesQuery = $user->sharedDocuments()
            ->where('active', true)
            ->whereHas('document');

        $totalCount = (clone $sharesQuery)->count();

        $shares = $sharesQuery
            ->with('document.media')
            ->latest()
            ->limit(self::PREVIEW_LIMIT)
            ->get();

        // Reaproveita os documentos (com mídia) já carregados aqui, evitando que a
        // action de download re-resolva cada documento e dispare lazy-load de mídia por item.
        foreach ($shares as $share) {
            if ($share->document !== null) {
                $this->resolvedDocuments[$share->document->getKey()] = $share->document;
            }
        }

        return [
            'shares' => $shares,
            'totalCount' => $totalCount,
            'remainingCount' => max(0, $totalCount - $shares->count()),
            'hasMore' => $totalCount > $shares->count(),
            'listUrl' => SharedDocumentResource::getUrl('index'),
        ];
    }

    public function downloadDocumentAction(): Action
    {
        return Action::make('downloadDocument')
            ->link()
            ->size(Size::Small)
            ->label(function (array $arguments): string {
                $document = $this->resolveSharedDocument($arguments['documentId'] ?? null);

                return $document?->hasLink()
                    ? __('panel-app::widgets.shared_materials.open')
                    : __('panel-app::widgets.shared_materials.download');
            })
            ->icon(function (array $arguments): Heroicon {
                $document = $this->resolveSharedDocument($arguments['documentId'] ?? null);

                return $document?->hasLink()
                    ? Heroicon::ArrowTopRightOnSquare
                    : Heroicon::ArrowDownTray;
            })
            ->visible(fn (array $arguments): bool => $this->resolveDownloadUrl($arguments['documentId'] ?? null) !== null)
            ->url(fn (array $arguments): ?string => $this->resolveDownloadUrl($arguments['documentId'] ?? null), shouldOpenInNewTab: true);
    }

    private function resolveDownloadUrl(?string $documentId): ?string
    {
        $document = $this->resolveSharedDocument($documentId);

        if (! $document instanceof Document) {
            return null;
        }

        if ($document->hasLink()) {
            return $document->link;
        }

        $media = $document->getFirstMedia('documents');

        return $media instanceof Media ? $this->buildDownloadUrl($media) : null;
    }

    private function resolveSharedDocument(?string $documentId): ?Document
    {
        if (blank($documentId)) {
            return null;
        }

        if (array_key_exists($documentId, $this->resolvedDocuments)) {
            return $this->resolvedDocuments[$documentId];
        }

        /** @var User $user */
        $user = auth()->user();

        return $this->resolvedDocuments[$documentId] = Document::query()
            ->whereHas('shares', function ($query) use ($user): void {
                $query->where('employee_id', $user->getKey())
                    ->where('active', true);
            })
            ->find($documentId);
    }

    private function buildDownloadUrl(Media $media): string
    {
        // Remove aspas e caracteres de controle do nome do arquivo para evitar
        // injeção/quebra no header Content-Disposition.
        $safeFilename = preg_replace('/["\x00-\x1F\x7F]/', '', basename($media->file_name)) ?? '';

        return Storage::disk($media->disk)->temporaryUrl(
            $media->getPathRelativeToRoot(),
            now()->addMinutes(5),
            ['ResponseContentDisposition' => sprintf('attachment; filename="%s"', $safeFilename)],
        );
    }
}

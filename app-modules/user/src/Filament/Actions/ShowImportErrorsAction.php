<?php

namespace TresPontosTech\User\Filament\Actions;

use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class ShowImportErrorsAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'show-import-errors';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->modalHeading('Erros na importação');
        $this->modalIcon(Heroicon::ExclamationTriangle);
        $this->modalIconColor('warning');
        $this->modalWidth('3xl');
        $this->modalSubmitAction(false);
        $this->modalCancelActionLabel('Fechar');

        $this->modalContent(function (array $arguments): HtmlString {
            $count = count($arguments['errors'] ?? []);

            $rows = collect($arguments['errors'] ?? [])
                ->map(fn (array $e): string => sprintf(
                    '<tr class="border-b border-gray-100 dark:border-white/5 last:border-0">
                        <td class="px-3 py-2.5 text-sm font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap align-top">Linha %d</td>
                        <td class="px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap align-top max-w-[200px] truncate">%s</td>
                        <td class="px-3 py-2.5 text-sm text-gray-700 dark:text-gray-300 align-top">%s</td>
                    </tr>',
                    $e['row'],
                    htmlspecialchars($e['email']),
                    htmlspecialchars($e['message']),
                ))
                ->join('');

            return new HtmlString(
                '<div class="px-1">
                    <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">' . $count . ' erro(s) encontrado(s). Corrija a planilha e tente novamente.</p>
                    <div class="rounded-lg border border-gray-200 dark:border-white/10 overflow-hidden">
                        <table class="w-full min-w-[480px] table-fixed">
                            <colgroup>
                                <col style="width: 80px">
                                <col style="width: 200px">
                                <col>
                            </colgroup>
                            <thead>
                                <tr class="bg-gray-50 dark:bg-white/5 border-b border-gray-200 dark:border-white/10">
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Linha</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Email</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Erro</th>
                                </tr>
                            </thead>
                        </table>
                        <div class="overflow-y-auto max-h-80">
                            <table class="w-full min-w-[480px] table-fixed">
                                <colgroup>
                                    <col style="width: 80px">
                                    <col style="width: 200px">
                                    <col>
                                </colgroup>
                                <tbody class="divide-y divide-gray-100 dark:divide-white/5">' . $rows . '</tbody>
                            </table>
                        </div>
                    </div>
                </div>'
            );
        });
    }
}

<?php

declare(strict_types=1);

/**
 * __() devolve a própria chave quando a tradução não existe, então um teste que
 * compara __(chave) com o render passa mesmo com a chave perdida — foi assim que
 * um merge engoliu a seção do wizard de reagendamento sem nenhum vermelho.
 * Este teste falha no lugar: toda chave que as telas usam precisa existir.
 */
it('resolves every appointment wizard translation key', function (string $locale): void {
    app()->setLocale($locale);

    $keys = [
        'panel-app::resources.appointments.cancel.modal_heading',
        'panel-app::resources.appointments.cancel.modal_description',
        'panel-app::resources.appointments.cancel.notice_keeps_credit',
        'panel-app::resources.appointments.cancel.notice_loses_credit',
        'panel-app::resources.appointments.cancel.confirmed.heading',
        'panel-app::resources.appointments.cancel.confirmed.credit_processing',
        'panel-app::resources.appointments.schedule.action_label',
        'panel-app::resources.appointments.schedule.next',
        'panel-app::resources.appointments.schedule.category.heading',
        'panel-app::resources.appointments.schedule.slot.heading',
        'panel-app::resources.appointments.schedule.review.heading',
        'panel-app::resources.appointments.schedule.review.notice',
        'panel-app::resources.appointments.schedule.confirmed.heading',
        'panel-app::resources.appointments.reschedule.action_label',
        'panel-app::resources.appointments.reschedule.next',
        'panel-app::resources.appointments.reschedule.cannot_reschedule',
        'panel-app::resources.appointments.reschedule.slot_unavailable',
        'panel-app::resources.appointments.reschedule.failed',
        'panel-app::resources.appointments.reschedule.calendar_sync_failed',
        'panel-app::resources.appointments.reschedule.intro.heading',
        'panel-app::resources.appointments.reschedule.intro.description',
        'panel-app::resources.appointments.reschedule.intro.keeps_current_slot',
        'panel-app::resources.appointments.reschedule.slot.heading',
        'panel-app::resources.appointments.reschedule.review.heading',
        'panel-app::resources.appointments.reschedule.review.submit',
        'panel-app::resources.appointments.reschedule.confirmed.heading',
        'panel-app::resources.appointments.reschedule.confirmed.finish',
    ];

    foreach ($keys as $key) {
        expect(trans()->has($key, $locale))->toBeTrue(sprintf('Chave de tradução ausente em %s: %s', $locale, $key));
    }
})->with(['pt_BR', 'en']);

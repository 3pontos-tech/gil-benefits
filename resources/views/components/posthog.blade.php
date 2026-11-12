{{--
    posthog-js do site e dos painéis de cliente, só com chave configurada. Logado, a pessoa é o
    id do usuário no Flamma. Visitante vindo do convite do Flare (?flare_id=) e ainda anônimo
    vira a pessoa do Flare, e o cadastro depois junta as duas no servidor.
--}}
@php
    $posthogKey = config('services.posthog.key');
    $userId = auth()->id();
    $flareId = request()->query('flare_id');
    $flareId = is_string($flareId) && \Illuminate\Support\Str::isUuid($flareId) ? $flareId : null;
@endphp

@if (filled($posthogKey))
    <script>
        !function(t,e){var o,n,p,r;e.__SV||(window.posthog&&window.posthog.__loaded)||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSurveysLoaded onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey canRenderSurveyAsync identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing get_explicit_consent_status is_capturing clear_opt_in_out_capturing debug getPageViewId captureTraceFeedback captureTraceMetric".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);

        posthog.init(@js($posthogKey), {
            api_host: @js(config('services.posthog.host')),
            person_profiles: 'identified_only',
            loaded: function (posthog) {
                posthog.register({ source: 'flamma' });
                @if (filled($userId))
                    posthog.identify(@js((string) $userId));
                @elseif (filled($flareId))
                    if (posthog.get_property('$user_state') !== 'identified') {
                        posthog.identify(@js($flareId));
                    }
                @endif
            },
        });
    </script>
@endif

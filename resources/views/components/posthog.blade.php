{{--
    posthog-js do site e do painel do colaborador, só com chave configurada. Fica fora das telas
    de troca de senha e não acompanha a equipe interna. Logado, a pessoa é o id do usuário no
    Flamma. Visitante com o flare_id do convite (guardado pelo StoreSignupAttribution) vira a
    pessoa do Flare, e o cadastro depois junta as duas no servidor. Visitante que o navegador
    ainda guarda como outra pessoa (quem saiu da conta) volta a ser anônimo. No painel só vão
    pageview e identify: sem autocapture nem gravação de sessão. Parâmetros sensíveis das URLs
    (e-mail, token, assinatura e voucher) são mascarados antes do envio.
--}}
@use('App\Http\Middleware\StoreSignupAttribution')
@use('TresPontosTech\Permissions\Roles')

@php
    $posthogKey = config('services.posthog.key');
    $user = auth()->user();
    $isInternal = $user?->hasAnyRole([Roles::SuperAdmin, Roles::Admin, Roles::Consultant, Roles::Financial, Roles::CustomerSuccess]) ?? false;
    $isPasswordReset = request()->routeIs('filament.*.auth.password-reset.*');
    $isPanel = request()->routeIs('filament.*');
    $flareId = StoreSignupAttribution::current(request())['flare_id'] ?? null;
@endphp

@if (filled($posthogKey) && ! $isInternal && ! $isPasswordReset)
    <script>
        !function(t,e){var o,n,p,r;e.__SV||(window.posthog&&window.posthog.__loaded)||(window.posthog=e,e._i=[],e.init=function(i,s,a){function g(t,e){var o=e.split(".");2==o.length&&(t=t[o[0]],e=o[1]),t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}}(p=t.createElement("script")).type="text/javascript",p.crossOrigin="anonymous",p.async=!0,p.src=s.api_host.replace(".i.posthog.com","-assets.i.posthog.com")+"/static/array.js",(r=t.getElementsByTagName("script")[0]).parentNode.insertBefore(p,r);var u=e;for(void 0!==a?u=e[a]=[]:a="posthog",u.people=u.people||[],u.toString=function(t){var e="posthog";return"posthog"!==a&&(e+="."+a),t||(e+=" (stub)"),e},u.people.toString=function(){return u.toString(1)+".people (stub)"},o="init capture register register_once register_for_session unregister unregister_for_session getFeatureFlag getFeatureFlagPayload isFeatureEnabled reloadFeatureFlags updateEarlyAccessFeatureEnrollment getEarlyAccessFeatures on onFeatureFlags onSurveysLoaded onSessionId getSurveys getActiveMatchingSurveys renderSurvey canRenderSurvey canRenderSurveyAsync identify setPersonProperties group resetGroups setPersonPropertiesForFlags resetPersonPropertiesForFlags setGroupPropertiesForFlags resetGroupPropertiesForFlags reset get_distinct_id getGroups get_session_id get_session_replay_url alias set_config startSessionRecording stopSessionRecording sessionRecordingStarted captureException loadToolbar get_property getSessionProperty createPersonProfile opt_in_capturing opt_out_capturing has_opted_in_capturing has_opted_out_capturing get_explicit_consent_status is_capturing clear_opt_in_out_capturing debug getPageViewId captureTraceFeedback captureTraceMetric".split(" "),n=0;n<o.length;n++)g(u,o[n]);e._i.push([i,s,a])},e.__SV=1)}(document,window.posthog||[]);

        posthog.init(@js($posthogKey), {
            api_host: @js(config('services.posthog.host')),
            person_profiles: 'identified_only',
            @if ($isPanel)
                autocapture: false,
                disable_session_recording: true,
            @endif
            before_send: function (event) {
                var sensitive = ['email', 'token', 'signature', 'voucher'];
                var maskParams = function (url) {
                    return url.replace(/([?&#])([^=&#]*)=[^&#]*/g, function (param, separator, name) {
                        var decoded = name;
                        try {
                            decoded = decodeURIComponent(name.replace(/\+/g, ' '));
                        } catch (error) {}

                        return sensitive.indexOf(decoded.toLowerCase()) === -1 ? param : separator + name + '=***';
                    });
                };
                var mask = function (values) {
                    for (var key in values || {}) {
                        if (typeof values[key] === 'string') {
                            values[key] = maskParams(values[key]);
                        }
                    }
                };
                mask(event.properties);
                mask(event.properties && event.properties.$set);
                mask(event.properties && event.properties.$set_once);
                mask(event.$set);
                mask(event.$set_once);

                return event;
            },
            loaded: function (posthog) {
                @if ($user)
                    posthog.identify(@js((string) $user->getKey()));
                @else
                    if (posthog.get_property('$user_state') === 'identified' && posthog.get_distinct_id() !== @js($flareId)) {
                        posthog.reset();
                    }
                    @if (filled($flareId))
                        posthog.identify(@js($flareId));
                    @endif
                @endif
                posthog.register({ source: 'flamma' });
            },
        });
    </script>
@endif

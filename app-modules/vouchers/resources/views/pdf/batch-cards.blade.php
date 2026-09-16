@php
    $logoInk = base_path('resources/icons/flamma-ink.svg');
    $logoLight = base_path('resources/icons/flamma.svg');
    $fontDir = base_path('resources/fonts/Space-Grotesk');
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @font-face {
            font-family: "Space Grotesk";
            font-weight: 400;
            src: url("{{ $fontDir }}/SpaceGrotesk-Regular.ttf") format("truetype");
        }

        @font-face {
            font-family: "Space Grotesk";
            font-weight: 500;
            src: url("{{ $fontDir }}/SpaceGrotesk-Medium.ttf") format("truetype");
        }

        @font-face {
            font-family: "Space Grotesk";
            font-weight: 600;
            src: url("{{ $fontDir }}/SpaceGrotesk-SemiBold.ttf") format("truetype");
        }

        @font-face {
            font-family: "Space Grotesk";
            font-weight: 700;
            src: url("{{ $fontDir }}/SpaceGrotesk-Bold.ttf") format("truetype");
        }

        @page { margin: 0; }

        body {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
        }

        div { margin: 0; }

        a { text-decoration: none; color: inherit; }

        .mono { font-family: "DejaVu Sans Mono", monospace; }

        .page {
            width: {{ $mm(1080) }}mm;
            height: {{ $mm(1350) }}mm;
            position: relative;
            overflow: hidden;
        }

        .front { background-color: #FBF9F7; }
        .back { background-color: #1A1A1A; }

        .band {
            position: absolute;
            left: {{ $mm(88) }}mm;
            width: {{ $mm(904) }}mm;
        }

        .front-head { top: {{ $mm(96) }}mm; height: {{ $mm(70) }}mm; }

        .logo-ink {
            position: absolute;
            left: 0;
            top: 0;
            width: {{ $mm(272) }}mm;
            height: {{ $mm(56) }}mm;
        }

        .tagline {
            position: absolute;
            right: 0;
            top: {{ $mm(14) }}mm;
            font-size: {{ $pt(18) }}pt;
            font-weight: 600;
            letter-spacing: {{ $pt(3.6) }}pt;
            color: #FF0043;
        }

        .accent-bar {
            position: absolute;
            top: {{ $mm(196) }}mm;
            left: {{ $mm(88) }}mm;
            width: {{ $mm(660) }}mm;
            height: {{ $mm(3) }}mm;
            background-color: #FF0043;
        }

        .accent-bar-fade {
            position: absolute;
            top: {{ $mm(196) }}mm;
            left: {{ $mm(748) }}mm;
            width: {{ $mm(244) }}mm;
            height: {{ $mm(3) }}mm;
            background-color: #F36E39;
        }

        .front-pitch { top: {{ $mm(290) }}mm; }

        .headline {
            font-size: {{ $pt(84) }}pt;
            line-height: 1.06;
            font-weight: 700;
            color: #1A1A1A;
            letter-spacing: {{ $pt(-2.5) }}pt;
        }

        .kicker {
            font-size: {{ $pt(24) }}pt;
            font-weight: 700;
            letter-spacing: {{ $pt(3.3) }}pt;
            color: #F36E39;
            padding-top: {{ $mm(34) }}mm;
        }

        .blurb {
            font-size: {{ $pt(30) }}pt;
            line-height: 1.55;
            font-weight: 500;
            color: #6B6560;
            width: {{ $mm(700) }}mm;
            padding-top: {{ $mm(34) }}mm;
        }

        .front-foot { top: {{ $mm(963) }}mm; height: {{ $mm(299) }}mm; }

        .code-block {
            position: absolute;
            left: 0;
            top: {{ $mm(168) }}mm;
            width: {{ $mm(614) }}mm;
        }

        .foot-label {
            font-size: {{ $pt(17) }}pt;
            font-weight: 600;
            letter-spacing: {{ $pt(3.4) }}pt;
            color: #A39B94;
            padding-bottom: {{ $mm(16) }}mm;
        }

        .code-rule {
            border-left: {{ $mm(6) }}mm solid #FF0043;
            padding-left: {{ $mm(24) }}mm;
        }

        .code {
            font-size: {{ $pt(46) }}pt;
            font-weight: 700;
            color: #1A1A1A;
            letter-spacing: {{ $pt(1.4) }}pt;
        }

        .valid {
            font-size: {{ $pt(20) }}pt;
            font-weight: 600;
            color: #6B6560;
            padding: {{ $mm(16) }}mm 0 0 {{ $mm(30) }}mm;
        }

        .qr-block {
            position: absolute;
            right: 0;
            top: 0;
            width: {{ $mm(230) }}mm;
            text-align: center;
        }

        .qr-block img { width: {{ $mm(230) }}mm; height: {{ $mm(230) }}mm; }

        .qr-missing {
            width: {{ $mm(230) }}mm;
            height: {{ $mm(230) }}mm;
            border: {{ $mm(2) }}mm dashed #A39B94;
        }

        .qr-hint {
            width: {{ $mm(230) }}mm;
            font-size: {{ $pt(19) }}pt;
            line-height: 1.4;
            font-weight: 600;
            color: #6B6560;
            padding-top: {{ $mm(16) }}mm;
        }

        .back-top { top: {{ $mm(96) }}mm; }

        .eyebrow {
            font-size: {{ $pt(18) }}pt;
            font-weight: 600;
            letter-spacing: {{ $pt(3.6) }}pt;
            color: #F36E39;
        }

        .back-headline {
            font-size: {{ $pt(46) }}pt;
            line-height: 1.24;
            font-weight: 700;
            color: #FFFFFF;
            letter-spacing: {{ $pt(-0.9) }}pt;
            padding-top: {{ $mm(26) }}mm;
        }

        .back-blurb {
            font-size: {{ $pt(26) }}pt;
            line-height: 1.6;
            font-weight: 500;
            color: #B6B6B6;
            width: {{ $mm(820) }}mm;
            padding-top: {{ $mm(26) }}mm;
        }

        .back-steps {
            top: {{ $mm(500) }}mm;
            border-top: {{ $mm(2) }}mm solid #3F3F3F;
            padding-top: {{ $mm(44) }}mm;
        }

        .step {
            position: relative;
            padding: {{ $mm(26) }}mm 0 0 {{ $mm(60) }}mm;
        }

        .step-n {
            position: absolute;
            left: 0;
            top: {{ $mm(31) }}mm;
            font-size: {{ $pt(22) }}pt;
            font-weight: 700;
            color: #FF0043;
        }

        .step-t {
            width: {{ $mm(844) }}mm;
            font-size: {{ $pt(27) }}pt;
            line-height: 1.4;
            font-weight: 500;
            color: #E3E3E3;
        }

        .back-facts {
            top: {{ $mm(940) }}mm;
            height: {{ $mm(200) }}mm;
            border-top: {{ $mm(2) }}mm solid #3F3F3F;
        }

        .fact {
            position: absolute;
            width: {{ $mm(420) }}mm;
        }

        .fact-a { left: 0; top: {{ $mm(40) }}mm; }
        .fact-b { left: {{ $mm(452) }}mm; top: {{ $mm(40) }}mm; }
        .fact-c { left: 0; top: {{ $mm(134) }}mm; }
        .fact-d { left: {{ $mm(452) }}mm; top: {{ $mm(134) }}mm; }

        .fact-label {
            font-size: {{ $pt(15) }}pt;
            font-weight: 600;
            letter-spacing: {{ $pt(2.7) }}pt;
            color: #8D8D8D;
            padding-bottom: {{ $mm(10) }}mm;
        }

        .fact-value {
            font-size: {{ $pt(27) }}pt;
            font-weight: 700;
            color: #FFFFFF;
        }

        .back-foot { top: {{ $mm(1180) }}mm; height: {{ $mm(82) }}mm; }

        .support {
            position: absolute;
            left: 0;
            top: 0;
            width: {{ $mm(640) }}mm;
            font-size: {{ $pt(20) }}pt;
            line-height: 1.6;
            font-weight: 500;
            color: #8D8D8D;
        }

        .support strong { color: #DCDCDC; font-weight: 600; }

        .logo-light {
            position: absolute;
            right: 0;
            top: {{ $mm(30) }}mm;
            width: {{ $mm(185) }}mm;
            height: {{ $mm(38) }}mm;
        }
    </style>
</head>
<body>
@foreach ($cards as $card)
    <div class="page front">
        <div class="band front-head">
            <img class="logo-ink" src="{{ $logoInk }}" alt="Flamma">
            <div class="mono tagline">BENEFÍCIO EXCLUSIVO</div>
        </div>

        <div class="accent-bar"></div>
        <div class="accent-bar-fade"></div>

        <div class="band front-pitch">
            <div class="headline">Seu futuro financeiro acaba de ganhar uma nova perspectiva.</div>
            <div class="mono kicker">CONSULTORIA FINANCEIRA</div>
            <div class="blurb">Este voucher dá acesso a uma sessão com especialistas do mercado para orientar suas decisões financeiras.</div>
        </div>

        <div class="band front-foot">
            <div class="code-block">
                <div class="mono foot-label">CÓDIGO DO BENEFÍCIO</div>
                <div class="code-rule">
                    <div class="mono code"><a href="{{ $card->redemptionUrl() }}">{{ $card->code }}</a></div>
                </div>
                <div class="valid">Resgate até {{ $deadline }}</div>
            </div>
            <div class="qr-block">
                @if ($card->qrCode() !== null)
                    <a href="{{ $card->redemptionUrl() }}"><img src="{{ $card->qrCode()->getPath() }}" alt=""></a>
                @else
                    <div class="qr-missing"></div>
                @endif
                <div class="qr-hint">Aponte a câmera para ativar seu benefício</div>
            </div>
        </div>
    </div>

    <div style="page-break-after: always;"></div>

    <div class="page back">
        <div class="band back-top">
            <div class="mono eyebrow">SEU BENEFÍCIO</div>
            <div class="back-headline">Uma sessão de consultoria financeira com especialistas do mercado.</div>
            <div class="back-blurb">Use esta conversa para entender melhor sua vida financeira, tomar decisões mais conscientes e construir um planejamento estratégico para o seu futuro.</div>
        </div>

        <div class="band back-steps">
            <div class="mono eyebrow">COMO UTILIZAR</div>
            @foreach ($steps as $index => $step)
                <div class="step">
                    <div class="mono step-n">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                    <div class="step-t">{{ $step }}</div>
                </div>
            @endforeach
        </div>

        <div class="band back-facts">
            <div class="fact fact-a">
                <div class="mono fact-label">CÓDIGO</div>
                <div class="mono fact-value">{{ $card->code }}</div>
            </div>
            <div class="fact fact-b">
                <div class="mono fact-label">RESGATE ATÉ</div>
                <div class="mono fact-value">{{ $deadline }}</div>
            </div>
            <div class="fact fact-c">
                <div class="mono fact-label">OFERECIDO POR</div>
                <div class="fact-value">{{ $companyName }}</div>
            </div>
            <div class="fact fact-d">
                <div class="mono fact-label">BENEFÍCIO</div>
                <div class="fact-value">1 consultoria individual</div>
            </div>
        </div>

        <div class="band back-foot">
            <div class="support">Dúvidas: <strong>{{ $supportEmail }}</strong><br>{{ $site }}</div>
            <img class="logo-light" src="{{ $logoLight }}" alt="Flamma">
        </div>
    </div>

    @if (! $loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
</body>
</html>

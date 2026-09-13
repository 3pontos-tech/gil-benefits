@php
    $logo = base_path('resources/icons/flamma.svg');
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 10mm; }

        body {
            margin: 0;
            font-family: "DejaVu Sans", sans-serif;
            color: #1b1b1f;
        }

        table.sheet {
            width: 190mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        table.sheet > tr > td {
            width: 95mm;
            padding: 0 0 5mm 0;
            vertical-align: top;
        }

        table.card {
            width: 88mm;
            border-collapse: collapse;
            table-layout: fixed;
            border: 0.3mm dashed #b9bec6;
        }

        td.brand {
            height: 12mm;
            background: #FD0342;
            padding: 0 5mm;
            vertical-align: middle;
        }

        td.brand img {
            width: 29mm;
            height: 6mm;
        }

        td.brand-tag {
            height: 12mm;
            background: #FD0342;
            padding: 0 5mm 0 0;
            vertical-align: middle;
            text-align: right;
            color: #ffffff;
            font-size: 6pt;
            text-transform: uppercase;
        }

        td.body {
            height: 26mm;
            padding: 3.5mm 0 0 5mm;
            vertical-align: top;
        }

        td.qr {
            height: 26mm;
            padding: 3.5mm 5mm 0 0;
            vertical-align: top;
            text-align: right;
        }

        td.foot {
            height: 7mm;
            padding: 0 5mm 2.5mm 5mm;
            vertical-align: bottom;
            font-size: 6pt;
            color: #7c8792;
        }

        p { margin: 0; }

        p.eyebrow {
            font-size: 5.5pt;
            text-transform: uppercase;
            color: #8b949d;
            padding-bottom: 1mm;
        }

        p.partner {
            font-size: 9pt;
            font-weight: bold;
            padding-bottom: 2.5mm;
        }

        p.code {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 15pt;
            font-weight: bold;
            white-space: nowrap;
        }

        table.qr-box {
            width: 20mm;
            border-collapse: collapse;
            border: 0.3mm dashed #c6ccd3;
            table-layout: fixed;
        }

        table.qr-box td {
            height: 20mm;
            text-align: center;
            vertical-align: middle;
            font-size: 5.5pt;
            color: #a7afb7;
        }

        table.qr-box img {
            width: 19mm;
            height: 19mm;
        }
    </style>
</head>
<body>
@foreach ($pages as $page)
    <table class="sheet">
        <colgroup>
            <col style="width: 95mm">
            <col style="width: 95mm">
        </colgroup>
        @foreach ($page->chunk(2) as $row)
            <tr>
                @foreach ($row as $card)
                    <td>
                        <table class="card">
                            <colgroup>
                                <col style="width: 58mm">
                                <col style="width: 30mm">
                            </colgroup>
                            <tr>
                                <td class="brand"><img src="{{ $logo }}" alt="Flamma"></td>
                                <td class="brand-tag">Consultoria financeira</td>
                            </tr>
                            <tr>
                                <td class="body">
                                    <p class="eyebrow">Cortesia de</p>
                                    <p class="partner">{{ $companyName }}</p>
                                    <p class="eyebrow">Seu código</p>
                                    <p class="code">{{ $card->code }}</p>
                                </td>
                                <td class="qr">
                                    <table class="qr-box">
                                        <tr>
                                            <td>
                                                @if ($card->qrCode() !== null)
                                                    <img src="{{ $card->qrCode()->getPath() }}" alt="">
                                                @else
                                                    QR
                                                @endif
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td class="foot" colspan="2">
                                    Resgate no app Flamma, em “Resgatar voucher”.
                                </td>
                            </tr>
                        </table>
                    </td>
                @endforeach
                @if ($row->count() === 1)
                    <td></td>
                @endif
            </tr>
        @endforeach
    </table>
    @if (! $loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
</body>
</html>

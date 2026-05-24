<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>{{ $contract->contract_number ?? $contract->project_code }} - {{ $contract->customer->name }}</title>
    <style>
        @page {
            margin-top: 20mm;
            margin-bottom: 32mm;
            margin-left: 15mm;
            margin-right: 15mm;
        }

        body {
            font-family: 'cairo', sans-serif;
            direction: rtl;
            font-size: 11pt;
            color: #1a1a1a;
            line-height: 1.6;
        }

        h1, h2, h3, h4 {
            font-family: 'cairo', sans-serif;
            font-weight: bold;
            margin: 4mm 0;
        }

        h1 { font-size: 16pt; text-align: center; }
        h2 {
            font-size: 13pt;
            border-bottom: 2px solid #1a1a1a;
            padding-bottom: 2mm;
            margin-top: 6mm;
        }
        h3 { font-size: 12pt; }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 3mm 0;
            font-size: 10.5pt;
        }

        table th, table td {
            border: 1px solid #1a1a1a;
            padding: 5px 8px;
            text-align: right;
        }

        table th {
            background: #f0f0f0;
            font-weight: bold;
        }

        .parties-table td {
            vertical-align: top;
            width: 50%;
        }

        .signature-section {
            margin-top: 10mm;
            page-break-inside: avoid;
        }

        .signature-box {
            border-top: 2px solid #1a1a1a;
            padding-top: 4mm;
            text-align: center;
            width: 60mm;
        }

        .clause {
            margin: 3mm 0;
            text-align: justify;
        }

        .clause-number {
            font-weight: bold;
            margin-left: 5px;
        }

        .amount {
            font-weight: bold;
            font-family: monospace;
            direction: ltr;
            display: inline-block;
        }

        .page-break { page-break-after: always; }

        @if(($contract->status ?? '') === 'draft')
            .draft-watermark::before {
                content: "مسودة";
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%) rotate(-45deg);
                font-size: 120pt;
                color: rgba(192, 0, 0, 0.1);
                font-weight: bold;
                z-index: -1;
            }
        @endif
    </style>
</head>
<body class="@if(($contract->status ?? '') === 'draft') draft-watermark @endif">

    @include('contracts.partials._header')
    @include('contracts.partials._footer')

    <sethtmlpageheader name="contract_header" page="ALL" value="on" show-this-page="1" />
    <sethtmlpagefooter name="contract_footer" page="ALL" value="on" show-this-page="1" />

    @include('contracts.partials.cover')

    <div class="page-break"></div>

    @include('contracts.partials.parties')

    @include('contracts.partials.preamble')

    @include('contracts.partials.scope')

    @include('contracts.partials.items')

    @include('contracts.partials.financials')

    @include('contracts.partials.schedule')

    @include('contracts.partials.clauses')

    <div class="page-break"></div>

    @include('contracts.partials.signatures')

</body>
</html>

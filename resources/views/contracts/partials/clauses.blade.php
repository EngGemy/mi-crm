@if(isset($renderedClauses) && $renderedClauses->isNotEmpty())
    @php $clauseIdx = 4; @endphp
    @foreach ($renderedClauses as $row)
        @php $att = $row['attachment']; $clause = $row['clause']; @endphp
        <div style="margin: 6mm 0; page-break-inside: avoid;">
            <h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">
                البند {{ $clauseIdx++ }}: {{ $clause->title }}
            </h2>
            <div style="text-align: justify;">{!! $row['rendered_content'] !!}</div>

            @if (!empty($att->items) && !empty($clause->items_schema))
                <table style="width: 100%; border-collapse: collapse; margin: 5mm 0; font-size: 11pt;">
                    <thead>
                        <tr>
                            @foreach ($clause->items_schema as $col)
                                <th style="border: 1px solid #1a1a1a; padding: 6px 10px; text-align: center; background: #f0f0f0; font-weight: bold;">
                                    {{ $col['label'] ?? $col['key'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($att->items as $item)
                            <tr>
                                @foreach ($clause->items_schema as $col)
                                    <td style="border: 1px solid #1a1a1a; padding: 6px 10px;">
                                        @php $val = $item[$col['key']] ?? ''; @endphp
                                        @if (($col['type'] ?? '') === 'money')
                                            {{ number_format((float) $val, 2) }}
                                        @else
                                            {{ $val }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
@endif

@if ($contract->custom_terms)
    <div style="margin: 6mm 0; page-break-inside: avoid;">
        <h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">شروط إضافية</h2>
        <div style="text-align: justify;">{!! nl2br(e($contract->custom_terms)) !!}</div>
    </div>
@endif

<div style="margin: 6mm 0; page-break-inside: avoid;">
    <h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">الاختصاص القضائي</h2>
    <p style="margin: 2mm 0; text-align: justify;">
        في حال نشوء أي نزاع، يسعى الطرفان للتسوية الودية خلال ثلاثة أيام عمل. وفي حال التعذر،
        تكون <strong>@setting('legal.jurisdiction_ar')</strong> صاحبة الاختصاص بنظر النزاع.
        يخضع هذا العقد لأحكام القوانين المعمول بها في @setting('legal.governing_law_ar').
    </p>
</div>

<div style="margin: 6mm 0; page-break-inside: avoid;">
    <h2 style="font-size: 14pt; border-bottom: 2px solid #1a1a1a; padding-bottom: 3mm; margin-top: 10mm;">عدد النسخ</h2>
    <p style="margin: 2mm 0; text-align: justify;">
        حُرر هذا العقد من نسختين أصليتين، بيد كل طرف نسخة، ومُذيّلتان بتوقيع الطرفين،
        دون أي قشط أو شطب أو تحشير.
    </p>
</div>

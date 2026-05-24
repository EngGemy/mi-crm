<htmlpagefooter name="contract_footer">
    <table style="width:100%; border-top:1px solid #1a1a1a; font-size:9pt; margin-top:2mm; direction: rtl;">
        <tr>
            <td style="width:50%; text-align:center; padding-top:2mm; vertical-align:top;">
                <strong>الطرف الأول (البائع)</strong><br>
                التوقيع: ................................
            </td>
            <td style="width:50%; text-align:center; padding-top:2mm; vertical-align:top;">
                <strong>الطرف الثاني (المشتري)</strong><br>
                التوقيع: ................................
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align:center; color:#888; padding-top:1mm; font-size:8pt; direction: rtl;">
                {{ settings('contact.address_ar') }}
                @if(!empty(settings('contact.phones', [])[0] ?? ''))
                    &nbsp;|&nbsp; {{ settings('contact.phones', [])[0] }}
                @endif
                &nbsp;|&nbsp; صفحة {PAGENO} من {nbpg}
            </td>
        </tr>
    </table>
</htmlpagefooter>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quotation {{ $quotation->quotation_number }}</title>
</head>
<body style="margin:0;padding:0;background:#f7f9fb;font-family:Arial,Helvetica,sans-serif;color:#191c1e;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f9fb;padding:24px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" style="max-width:560px;background:#ffffff;border:1px solid #c5c6d0;border-radius:12px;overflow:hidden;">
                <tr>
                    <td style="background:#000b28;color:#ffffff;padding:20px 24px;">
                        <div style="font-size:18px;font-weight:700;">TEGH CABLES (INDIA) PVT. LTD.</div>
                        <div style="font-size:12px;opacity:0.75;margin-top:4px;">Sales Quotation</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px;">
                        <p style="margin:0 0 12px;font-size:14px;">
                            Dear {{ $company->contact_person_name ?: ($company->name ?? 'Customer') }},
                        </p>
                        <p style="margin:0 0 16px;font-size:14px;line-height:1.5;color:#44464e;">
                            Please find attached our sales quotation <strong>{{ $quotation->quotation_number }}</strong>
                            for <strong>₹ {{ number_format((float) $quotation->amount, 2) }}</strong>.
                        </p>
                        <p style="margin:0 0 16px;font-size:13px;line-height:1.5;color:#44464e;">
                            The PDF attachment includes full party details, product line items, terms &amp; conditions, and payment terms.
                        </p>
                        <p style="margin:0;font-size:13px;line-height:1.5;color:#44464e;">
                            For any queries, please reply to this email. We look forward to your confirmation.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:14px 24px;background:#f2f4f6;font-size:11px;color:#75777f;">
                        Tegh Cables (India) Pvt. Ltd. · enquiry@teghindia.com
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>

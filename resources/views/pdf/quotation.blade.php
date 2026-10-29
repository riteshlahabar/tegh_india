<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Sales Quotation {{ $quotation->quotation_number }}</title>
    <style>
        @page { margin: 28px 32px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 0;
        }
        .center { text-align: center; }
        .right { text-align: right; }
        .bold { font-weight: bold; }
        .header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header-table td { vertical-align: top; }
        .header-text { width: 78%; padding-right: 10px; text-align: left; }
        .header-logo { width: 22%; text-align: center; }
        .header-logo img { width: 90px; height: auto; display: block; margin: 0 auto; }
        .header-logo-name {
            font-size: 8px;
            font-weight: bold;
            line-height: 1.25;
            margin-top: 4px;
            text-align: center;
            text-transform: uppercase;
        }
        .title {
            font-size: 16px;
            font-weight: bold;
            margin: 0 0 6px;
            text-align: left;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            margin: 0;
            text-align: left;
        }
        .company-meta {
            font-size: 10px;
            text-align: left;
            line-height: 1.35;
            margin-top: 4px;
        }
        .gstin-top {
            font-size: 10px;
            margin-top: 6px;
            text-align: left;
        }
        table.layout { width: 100%; border-collapse: collapse; }
        table.layout td { vertical-align: top; }
        .box {
            border: 1px solid #222;
            padding: 8px 10px;
            min-height: 110px;
        }
        .label { font-size: 10px; font-weight: bold; margin-bottom: 4px; }
        .party-name { font-size: 12px; font-weight: bold; margin: 2px 0 4px; }
        .meta-table { width: 100%; border-collapse: collapse; font-size: 10px; }
        .meta-table td { padding: 2px 0; }
        .meta-table .k { width: 95px; font-weight: bold; }
        .meta-table .c { width: 10px; }
        .items {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            font-size: 10px;
        }
        .items th, .items td {
            border: 1px solid #222;
            padding: 5px 4px;
            vertical-align: top;
        }
        .items th {
            background: #f3f3f3;
            font-weight: bold;
            text-align: center;
        }
        .items .num { text-align: right; white-space: nowrap; }
        .items .cen { text-align: center; }
        .totals {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            font-size: 11px;
        }
        .totals td {
            border: 1px solid #222;
            padding: 6px 8px;
        }
        .footer-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }
        .footer-grid td {
            vertical-align: top;
            width: 50%;
        }
        .terms {
            border: 1px solid #222;
            padding: 8px 10px;
            min-height: 120px;
            white-space: pre-wrap;
            line-height: 1.4;
        }
        .sign {
            border: 1px solid #222;
            padding: 8px 10px;
            min-height: 120px;
            text-align: right;
        }
        .sign .for { font-size: 10px; margin-bottom: 48px; }
        .muted { color: #444; }
    </style>
</head>
<body>
@php
    $companyLetter = $letterhead;
    $party = $quotation->company;
    $items = $quotation->items;
    $totalQty = $quotation->total_qty ?? $items->sum('qty');

    $fmtQty = function ($n) {
        $n = (float) $n;
        if (abs($n - round($n)) < 0.00001) {
            return number_format($n, 0);
        }

        return rtrim(rtrim(number_format($n, 3, '.', ','), '0'), '.');
    };

    $fmtNum = function ($n, $maxDecimals = 4) {
        $n = round((float) $n, $maxDecimals);
        if (abs($n - round($n)) < 0.0000001) {
            return number_format($n, 0);
        }

        $fixed = rtrim(rtrim(number_format($n, $maxDecimals, '.', ''), '0'), '.');
        $parts = explode('.', $fixed);
        $intPart = number_format((float) $parts[0], 0);
        if (count($parts) === 1) {
            return $intPart;
        }

        return $intPart.'.'.$parts[1];
    };
@endphp

<table class="header-table">
    <tr>
        <td class="header-text">
            <div class="title">Sales Quotation</div>
            <div class="company-name">{{ $companyLetter['company_name'] }}</div>
            <div class="company-meta">
                {{ $companyLetter['address_line1'] }}<br>
                {{ $companyLetter['address_line2'] }}<br>
                Tel. : {{ $companyLetter['tel'] }} &nbsp;&nbsp; email : {{ $companyLetter['email'] }}<br>
                CIN NO. :- {{ $companyLetter['cin'] }}
            </div>
            <div class="gstin-top"><strong>GSTIN :</strong> {{ $companyLetter['gstin'] }}</div>
        </td>
        <td class="header-logo">
            @if(!empty($logoSrc))
                <img src="{{ $logoSrc }}" alt="{{ $companyLetter['company_name'] }}">
            @endif
            <div class="header-logo-name">{{ $companyLetter['company_name'] }}</div>
        </td>
    </tr>
</table>

<table class="layout" style="margin-top:12px;">
    <tr>
        <td style="width:52%; padding-right:8px;">
            <div class="box">
                <div class="label">Party Details :</div>
                <div class="party-name">{{ $party->name ?? '—' }}</div>
                <div class="muted" style="white-space:pre-wrap;">{{ $quotation->party_address ?: ($party->address ?? '') }}</div>
                <div style="margin-top:8px;">Mobile : {{ $quotation->party_mobile ?: ($party->mobile_number ?? '') }}</div>
                <div>GSTIN / UIN : {{ $quotation->party_gstin ?: ($party->gst_number ?? '') }}</div>
            </div>
        </td>
        <td style="width:48%; padding-left:8px;">
            <div class="box">
                <table class="meta-table">
                    <tr>
                        <td class="k">Quotation No.</td>
                        <td class="c">:</td>
                        <td>{{ $quotation->quotation_number }}</td>
                    </tr>
                    <tr>
                        <td class="k">Dated</td>
                        <td class="c">:</td>
                        <td>{{ optional($quotation->date)->format('M j, Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td class="k">Sales Person</td>
                        <td class="c">:</td>
                        <td>{{ $quotation->sales_person_name ?: '—' }}</td>
                    </tr>
                    <tr>
                        <td class="k">Mobile No.</td>
                        <td class="c">:</td>
                        <td>{{ $quotation->sales_person_mobile ?: '—' }}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width:28px;">S.N.</th>
            <th>Description of Goods</th>
            <th style="width:70px;">HSN/SAC<br>Code</th>
            <th style="width:45px;">Qty.</th>
            <th style="width:45px;">Unit</th>
            <th style="width:60px;">List Price</th>
            <th style="width:55px;">Discount%</th>
            <th style="width:55px;">Price</th>
            <th style="width:75px;">Amount(₹)</th>
        </tr>
    </thead>
    <tbody>
        @forelse($items as $i => $item)
        <tr>
            <td class="cen">{{ $i + 1 }}</td>
            <td>{{ $item->description }}</td>
            <td class="cen">{{ $item->hsn_code }}</td>
            <td class="num">{{ $fmtQty($item->qty) }}</td>
            <td class="cen">{{ $item->unit }}</td>
            <td class="num">{{ $fmtNum($item->list_price, 2) }}</td>
            <td class="num">{{ $fmtNum($item->discount_percent, 2) }}</td>
            <td class="num">{{ $fmtNum($item->price, 4) }}</td>
            <td class="num">₹ {{ number_format((float) $item->amount, 2) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="9" class="center muted">No line items</td>
        </tr>
        @endforelse
    </tbody>
</table>

<table class="totals">
    <tr>
        <td class="right bold" style="width:78%;">Total QTY</td>
        <td class="num bold">{{ $fmtQty($totalQty) }}</td>
    </tr>
    <tr>
        <td class="right bold">Grand Total</td>
        <td class="num bold">₹ {{ number_format((float) $quotation->amount, 2) }}</td>
    </tr>
</table>

<table class="footer-grid">
    <tr>
        <td style="padding-right:8px;">
            <div class="label">Terms &amp; Conditions</div>
            <div class="terms">{{ $quotation->terms_conditions ?: \App\Models\Quotation::defaultTerms() }}@if($quotation->product_make)

Make :- {{ $quotation->product_make }}@endif
</div>
        </td>
        <td style="padding-left:8px;">
            <div class="label" style="margin-bottom:6px;">Payment Terms : {{ $quotation->payment_terms ?: 'Against Proforma Invoice' }}</div>
            <div class="sign">
                <div class="for">for {{ $companyLetter['company_name'] }}</div>
                <div class="bold">Authorised Signatory</div>
            </div>
        </td>
    </tr>
</table>
</body>
</html>

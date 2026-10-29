<?php

return [
    'company_name' => env('QUOTE_COMPANY_NAME', 'TEGH CABLES (INDIA) PVT. LTD.'),
    'address_line1' => env('QUOTE_ADDRESS_1', '301/302, HAR KRISHAN BHAVAN, CHARAT SINGH COLONY, NR. SOLITAIRE'),
    'address_line2' => env('QUOTE_ADDRESS_2', 'BUSINESS PARK, ANDHERI (EAST) MUMBAI, 400093'),
    'tel' => env('QUOTE_TEL', '022-40627000(100)'),
    'email' => env('QUOTE_EMAIL', 'enquiry@teghindia.com'),
    'gstin' => env('QUOTE_GSTIN', '27AAJCT9585F1ZO'),
    'cin' => env('QUOTE_CIN', 'U46593MH2023PTC398441'),
    // Relative to public/ — used on the left of the quotation/invoice PDF header.
    'logo' => env('QUOTE_LOGO', 'images/quotation-logo.png'),
];

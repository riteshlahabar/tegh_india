<?php

namespace App\Services\Admin;

use App\Models\Company;
use Illuminate\Support\Facades\Schema;

class LeadPageService
{
    public function data(): array
    {
        $with = ['executive', 'creator'];
        if (Schema::hasTable('company_product')) {
            $with[] = 'products';
        }

        $companies = Company::with($with)->orderBy('name')->get();

        return [
            'companies' => $companies,
            'pipelineNew' => $companies->where('stage', 'New')->values(),
            'pipelineFollowup' => $companies->where('stage', 'Follow-up')->values(),
            'pipelineQuote' => $companies->where('stage', 'Quotation Sent')->values(),
            'pipelineNegotiation' => $companies->where('stage', 'Negotiation')->values(),
            'pipelineWon' => $companies->where('stage', 'Won')->values(),
            'pipelineLost' => $companies->where('stage', 'Lost')->values(),
        ];
    }
}

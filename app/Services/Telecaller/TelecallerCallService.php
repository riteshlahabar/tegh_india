<?php

namespace App\Services\Telecaller;

use App\Models\Call;
use App\Models\Company;
use App\Models\User;
use App\Support\IndiaTime;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class TelecallerCallService
{
    public const OUTCOMES = Call::OUTCOMES;

    public function log(User $user, array $data): Call
    {
        $company = Company::findOrFail($data['company_id']);
        $this->assertCanWork($user, $company);

        if ((int) $company->assigned_tc_id !== (int) $user->id) {
            $company->update(['assigned_tc_id' => $user->id]);
        }

        $call = Call::create([
            'company_id' => $company->id,
            'telecaller_id' => $user->id,
            'called_at' => Carbon::now('UTC'),
            'outcome' => $data['outcome'],
            'remarks' => $data['remarks'] ?? null,
            'next_followup' => IndiaTime::parse($data['next_followup'] ?? null),
        ]);

        $stage = Call::stageForOutcome($data['outcome']);
        if ($stage) {
            $company->update(['stage' => $stage]);
        }

        return $call;
    }

    public function updateStage(User $user, int $companyId, string $stage): Company
    {
        $company = Company::findOrFail($companyId);
        $this->assertCanWork($user, $company);

        if ((int) $company->assigned_tc_id !== (int) $user->id) {
            $company->update(['assigned_tc_id' => $user->id]);
        }

        $company->update(['stage' => $stage]);

        return $company->fresh();
    }

    public function claim(User $user, int $companyId): Company
    {
        $company = Company::findOrFail($companyId);
        $this->assertCanWork($user, $company);

        if ((int) $company->assigned_tc_id !== (int) $user->id) {
            $company->update(['assigned_tc_id' => $user->id]);
        }

        return $company->fresh();
    }

    private function assertCanWork(User $user, Company $company): void
    {
        if (! app(TelecallerPortalService::class)->canAccess($user, $company)) {
            throw ValidationException::withMessages([
                'company_id' => ['This lead is not in your calling queue.'],
            ]);
        }
    }
}

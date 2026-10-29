<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminWhatsAppDummyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'branch' => 'All',
        ]);
    }

    public function test_whatsapp_page_splits_pending_and_sent_quotations(): void
    {
        $company = Company::create([
            'name' => 'Acme Wires',
            'whatsapp_number' => '9876543210',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'Quotation Sent',
        ]);

        Quotation::create([
            'company_id' => $company->id,
            'quotation_number' => 'Q-2001/26-27',
            'amount' => 15000,
            'status' => 'Sent',
            'date' => now(),
        ]);

        Quotation::create([
            'company_id' => $company->id,
            'quotation_number' => 'Q-2002/26-27',
            'amount' => 8000,
            'status' => 'Sent',
            'date' => now(),
            'whatsapp_sent_at' => now(),
            'whatsapp_sent_to' => '9876543210',
            'whatsapp_message' => 'Already recorded',
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.whatsapp'))
            ->assertOk()
            ->assertSee('Have to send')
            ->assertSee('Recorded as sent')
            ->assertSee('Q-2001/26-27')
            ->assertSee('Q-2002/26-27')
            ->assertSee('WhatsApp API not connected')
            ->assertSee('Hello Acme Wires');
    }

    public function test_recording_a_send_does_not_call_whatsapp_and_moves_the_quote(): void
    {
        $company = Company::create([
            'name' => 'Beta Cables',
            'mobile_number' => '9123456780',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $quote = Quotation::create([
            'company_id' => $company->id,
            'quotation_number' => 'Q-3001/26-27',
            'amount' => 25000,
            'status' => 'Sent',
            'date' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.whatsapp.send', $quote), [
                'phone' => '9123456780',
                'quote_id' => $quote->id,
            ])
            ->assertRedirect(route('admin.whatsapp'));

        $quote->refresh();
        $this->assertNotNull($quote->whatsapp_sent_at);
        $this->assertSame('9123456780', $quote->whatsapp_sent_to);
        $this->assertStringContainsString('Q-3001/26-27', $quote->whatsapp_message);
        $this->assertSame('New', $company->fresh()->stage);
    }

    public function test_a_configured_instance_sends_the_quotation_on_whatsapp(): void
    {
        config([
            'services.greenapi.id_instance' => '71072759918',
            'services.greenapi.api_token' => 'test-token',
            'services.greenapi.api_url' => 'https://7107.api.greenapi.com',
            'services.greenapi.phone' => '919820392245',
        ]);

        Http::fake([
            'https://7107.api.greenapi.com/waInstance71072759918/sendMessage/test-token' => Http::response([
                'idMessage' => 'MSG1',
            ], 200),
        ]);

        $company = Company::create([
            'name' => 'Gamma Wires',
            'whatsapp_number' => '9876543210',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $quote = Quotation::create([
            'company_id' => $company->id,
            'quotation_number' => 'Q-4001/26-27',
            'amount' => 12000,
            'status' => 'Sent',
            'date' => now(),
        ]);

        $this->actingAs($this->admin())
            ->post(route('admin.whatsapp.send', $quote), [
                'phone' => '9876543210',
                'quote_id' => $quote->id,
            ])
            ->assertRedirect(route('admin.whatsapp'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://7107.api.greenapi.com/waInstance71072759918/sendMessage/test-token'
                && $request['chatId'] === '919876543210@c.us'
                && str_contains($request['message'], 'Q-4001/26-27');
        });

        $quote->refresh();
        $this->assertNotNull($quote->whatsapp_sent_at);
        $this->assertSame('9876543210', $quote->whatsapp_sent_to);
    }

    public function test_a_rejected_whatsapp_send_is_not_marked_sent(): void
    {
        config([
            'services.greenapi.id_instance' => '71072759918',
            'services.greenapi.api_token' => 'bad-token',
            'services.greenapi.api_url' => 'https://7107.api.greenapi.com',
        ]);

        Http::fake([
            'https://7107.api.greenapi.com/*' => Http::response(['message' => 'Unauthorized'], 401),
        ]);

        $company = Company::create([
            'name' => 'Delta Wires',
            'credit_limit' => 0,
            'outstanding_amount' => 0,
            'stage' => 'New',
        ]);

        $quote = Quotation::create([
            'company_id' => $company->id,
            'quotation_number' => 'Q-4002/26-27',
            'amount' => 5000,
            'status' => 'Sent',
            'date' => now(),
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.whatsapp'))
            ->post(route('admin.whatsapp.send', $quote), [
                'phone' => '9876543210',
            ])
            ->assertRedirect(route('admin.whatsapp'))
            ->assertSessionHas('error');

        $this->assertNull($quote->fresh()->whatsapp_sent_at);
    }
}

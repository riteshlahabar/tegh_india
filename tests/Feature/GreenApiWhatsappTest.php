<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Invoice;
use App\Models\Party;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\WhatsApp\GreenApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GreenApiWhatsappTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'greenapi.api_url' => 'https://7107.api.greenapi.com',
            'greenapi.media_url' => 'https://7107.api.greenapi.com',
            'greenapi.id_instance' => '710722759918',
            'greenapi.api_token' => 'test-token',
            'greenapi.phone' => '919820932245',
        ]);
    }

    public function test_message_is_sent_only_when_the_instance_is_authorized(): void
    {
        Http::fake([
            'https://7107.api.greenapi.com/waInstance710722759918/getStateInstance/test-token' => Http::response(['stateInstance' => 'authorized']),
            'https://7107.api.greenapi.com/waInstance710722759918/sendMessage/test-token' => Http::response(['idMessage' => 'MSG-1']),
        ]);

        $user = User::factory()->admin()->create();
        $invoice = $this->invoice('9876543210');

        $this->actingAs($user)->postJson('/admin/whatsapp', [
            'invoice_id' => $invoice->id,
            'template' => 'Payment Reminder',
            'message' => 'Hello, your quotation is ready.',
        ])->assertOk()->assertJsonPath('idMessage', 'MSG-1');

        $this->assertDatabaseHas('whatsapp_messages', [
            'invoice_id' => $invoice->id,
            'mobile' => '9876543210',
        ]);
        $this->assertNotNull(WhatsappMessage::first()->sent_at);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://7107.api.greenapi.com/waInstance710722759918/sendMessage/test-token'
                && $request['chatId'] === '919876543210@c.us'
                && $request['message'] === 'Hello, your quotation is ready.';
        });
    }

    public function test_message_is_not_logged_when_whatsapp_is_not_authorized(): void
    {
        Http::fake([
            'https://7107.api.greenapi.com/*' => Http::response(['stateInstance' => 'notAuthorized']),
        ]);

        $user = User::factory()->create();
        $invoice = $this->invoice('9876543210');

        $this->actingAs($user)->postJson('/admin/whatsapp', [
            'invoice_id' => $invoice->id,
            'message' => 'Please pay.',
        ])->assertStatus(422);

        $this->assertSame(0, WhatsappMessage::count());
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'sendMessage'));
    }

    public function test_saved_whatsapp_settings_override_env(): void
    {
        AppSetting::setValue('greenapi_api_url', 'https://7107.api.greenapi.com');
        AppSetting::setValue('greenapi_id_instance', '999');
        AppSetting::setValue('greenapi_api_token', 'saved-token');

        $url = app(GreenApiClient::class)->endpoint('getStateInstance');

        $this->assertSame(
            'https://7107.api.greenapi.com/waInstance999/getStateInstance/saved-token',
            $url
        );
    }

    public function test_admin_whatsapp_page_has_connection_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin/whatsapp')
            ->assertOk()
            ->assertSee('WhatsApp connection')
            ->assertSee('7107.api.greenapi.com')
            ->assertDontSee('test-token');
    }

    private function invoice(string $mobile): Invoice
    {
        $party = Party::create([
            'name' => 'Raj Formulations',
            'code' => 'ERP-3495',
            'busy_party_id' => '3495',
            'mobile' => $mobile,
        ]);

        return Invoice::create([
            'party_id' => $party->id,
            'invoice_no' => 'TCIPL/1',
            'busy_invoice_id' => 'VCH-1',
            'invoice_date' => now()->subMonth(),
            'due_date' => now()->subDay(),
            'invoice_amount' => 10000,
            'received_amount' => 0,
            'outstanding' => 10000,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\CompanySheetImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

class CompanySheetImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'branch' => 'All',
        ]);
    }

    private function staffForDemo(): void
    {
        User::factory()->create([
            'name' => 'Amit Patil',
            'role' => 'sales_executive',
            'branch' => 'Mumbai',
        ]);
        User::factory()->create([
            'name' => 'Sneha Kulkarni',
            'role' => 'sales_executive',
            'branch' => 'Gujarat',
        ]);
        User::factory()->create([
            'name' => 'Priya Shah',
            'role' => 'telecaller',
            'branch' => 'All',
        ]);
    }

    private function demoUpload(): UploadedFile
    {
        $path = sys_get_temp_dir().'/'.uniqid('customer-demo-', true).'.xlsx';
        app(CompanySheetImporter::class)->writeDemoToPath($path);

        return new UploadedFile(
            $path,
            CompanySheetImporter::DEMO_FILENAME,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }

    public function test_customers_page_offers_bulk_upload_and_demo_download(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.customers'));

        $response->assertOk();
        $response->assertSee('Upload Sheet', false);
        $response->assertSee('Demo sheet', false);
        $response->assertSee('Download demo sheet (5 sample customers)', false);
        $response->assertSee(route('admin.companies.import.demo'), false);
        $response->assertSee(route('admin.companies.import'), false);
    }

    public function test_admin_can_download_the_demo_customer_sheet(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->get(route('admin.companies.import.demo'));

        $response->assertOk();
        $response->assertDownload(CompanySheetImporter::DEMO_FILENAME);

        $path = sys_get_temp_dir().'/'.uniqid('downloaded-demo-', true).'.xlsx';
        file_put_contents($path, $response->streamedContent());

        $rows = IOFactory::load($path)->getActiveSheet()->toArray(null, true, true, false);

        $this->assertSame(CompanySheetImporter::DEMO_HEADERS, $rows[0]);
        $this->assertCount(1 + count(app(CompanySheetImporter::class)->demoRows()), $rows);
        $this->assertSame('Shree Krishna Electricals', $rows[1][0]);
    }

    public function test_guest_cannot_download_the_demo_sheet(): void
    {
        $this->get(route('admin.companies.import.demo'))->assertRedirect(route('login'));
    }

    public function test_importing_the_demo_sheet_adds_sample_customers_and_assigns_staff(): void
    {
        $admin = $this->admin();
        $this->staffForDemo();

        $response = $this->actingAs($admin)->from(route('admin.customers'))->post(
            route('admin.companies.import'),
            ['sheet' => $this->demoUpload()]
        );

        $response->assertRedirect(route('admin.customers'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('5 added', session('success'));

        $this->assertSame(5, Company::count());

        $krishna = Company::where('name', 'Shree Krishna Electricals')->first();
        $this->assertNotNull($krishna);
        $this->assertSame('Rajesh Sharma', $krishna->contact_person_name);
        $this->assertSame('9876543210', $krishna->mobile_number);
        $this->assertSame('Mumbai', $krishna->city);
        $this->assertSame('Kalyan', $krishna->sub_city);
        $this->assertSame('27AAAAA1111A1Z5', $krishna->gst_number);
        $this->assertSame('New', $krishna->stage);
        $this->assertSame($admin->id, $krishna->created_by);
        $this->assertSame('Amit Patil', $krishna->executive?->name);
        $this->assertSame('Priya Shah', $krishna->telecaller?->name);

        $patel = Company::where('name', 'Patel Cable Traders')->first();
        $this->assertSame('Gujarat', $patel?->city);
        $this->assertSame('Ahmedabad', $patel?->sub_city);
        $this->assertSame('Sneha Kulkarni', $patel?->executive?->name);

        $deccan = Company::where('name', 'Deccan Wire House')->first();
        $this->assertSame('Pune', $deccan?->city);
        $this->assertSame('Talegaon', $deccan?->sub_city);
        $this->assertNull($deccan?->assigned_tc_id);
    }

    public function test_import_creates_default_staff_logins_for_new_names_and_assigns_them(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->from(route('admin.customers'))->post(
            route('admin.companies.import'),
            ['sheet' => $this->demoUpload()]
        )->assertSessionHas('success');

        $this->assertStringContainsString('3 new staff login', session('success'));
        $this->assertStringContainsString(CompanySheetImporter::DEFAULT_STAFF_PASSWORD, session('success'));

        $this->assertSame(3, User::query()->whereIn('role', ['sales_executive', 'telecaller'])->count());

        $sales = User::where('name', 'Amit Patil')->where('role', 'sales_executive')->first();
        $this->assertNotNull($sales);
        $this->assertSame('amit.patil@'.CompanySheetImporter::DEFAULT_STAFF_EMAIL_DOMAIN, $sales->email);
        $this->assertSame('Mumbai', $sales->branch);
        $this->assertTrue(Hash::check(CompanySheetImporter::DEFAULT_STAFF_PASSWORD, $sales->password));

        $telecaller = User::where('name', 'Priya Shah')->where('role', 'telecaller')->first();
        $this->assertNotNull($telecaller);
        $this->assertSame('priya.shah@'.CompanySheetImporter::DEFAULT_STAFF_EMAIL_DOMAIN, $telecaller->email);

        $krishna = Company::where('name', 'Shree Krishna Electricals')->first();
        $this->assertSame($sales->id, $krishna?->assigned_exec_id);
        $this->assertSame($telecaller->id, $krishna?->assigned_tc_id);

        $this->actingAs($admin)->post(route('admin.companies.import'), [
            'sheet' => $this->demoUpload(),
        ]);

        $this->assertSame(3, User::query()->whereIn('role', ['sales_executive', 'telecaller'])->count());
    }

    public function test_reimporting_the_demo_sheet_updates_instead_of_duplicating(): void
    {
        $admin = $this->admin();
        $this->staffForDemo();

        $this->actingAs($admin)->post(route('admin.companies.import'), [
            'sheet' => $this->demoUpload(),
        ])->assertSessionHas('success');

        $this->actingAs($admin)->from(route('admin.customers'))->post(route('admin.companies.import'), [
            'sheet' => $this->demoUpload(),
        ])->assertSessionHas('success');

        $this->assertSame(5, Company::count());
        $this->assertStringContainsString('5 updated', session('success'));
    }

    public function test_import_splits_concatenated_mobiles_instead_of_failing(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)->from(route('admin.customers'))->post(
            route('admin.companies.import'),
            ['sheet' => $this->sheetUpload(
                ['Company Name', 'Mobile Number', 'City'],
                [
                    [
                        'RIDDHI SIDDHI ELECTRICALS',
                        '902899189798213086689821866683',
                        'Mumbai',
                    ],
                    [
                        'Balaji Wires',
                        '9876543210 / 9123456780',
                        'Pune',
                    ],
                ]
            )]
        );

        $response->assertRedirect(route('admin.customers'));
        $response->assertSessionHas('success');
        $this->assertStringContainsString('2 added', session('success'));

        $riddhi = Company::where('name', 'RIDDHI SIDDHI ELECTRICALS')->first();
        $this->assertNotNull($riddhi);
        $this->assertSame('9028991897', $riddhi->mobile_number);
        $this->assertSame('9821308668', $riddhi->alternate_mobile);
        $this->assertTrue(strlen((string) $riddhi->mobile_number) <= 20);

        $balaji = Company::where('name', 'Balaji Wires')->first();
        $this->assertSame('9876543210', $balaji?->mobile_number);
        $this->assertSame('9123456780', $balaji?->alternate_mobile);
    }

    private function sheetUpload(array $headers, array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray($headers, null, 'A1');
        $spreadsheet->getActiveSheet()->fromArray($rows, null, 'A2');

        $path = sys_get_temp_dir().'/'.uniqid('customer-sheet-', true).'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);

        return new UploadedFile(
            $path,
            'customers.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}

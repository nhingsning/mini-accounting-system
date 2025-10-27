<?php

namespace Tests\Feature;

use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_create_and_update_persist_addresses_and_branch_details(): void
    {
        $headOffice = Customer::create([
            'name' => 'Acme Co., Ltd.',
            'name_private' => 'Acme Internal',
            'tax_id' => '0105555999999',
            'tel' => '02-123-4567',
            'fax' => '02-765-4321',
            'payment_terms' => '30 Days',
            'address_show' => '123 Main Rd., Bangkok',
            'address_private' => '123 Internal Address, Bangkok',
            'office_type' => 'head',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $headOffice->id,
            'name' => 'Acme Co., Ltd.',
            'address_show' => '123 Main Rd., Bangkok',
            'office_type' => 'head',
        ]);

        $branch = Customer::create([
            'name' => 'Acme Co., Ltd. (Branch)',
            'name_private' => 'Acme Branch Internal',
            'tax_id' => '0105555999999',
            'tel' => '02-555-0000',
            'payment_terms' => 'Credit',
            'address_show' => '456 Branch Ave., Chiang Mai',
            'address_private' => '456 Branch Private Ave., Chiang Mai',
            'office_type' => 'branch',
            'branch_name' => 'Chiang Mai Branch',
            'branch_code' => 'B001',
            'head_office_id' => $headOffice->id,
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $branch->id,
            'branch_name' => 'Chiang Mai Branch',
            'branch_code' => 'B001',
            'head_office_id' => $headOffice->id,
        ]);

        $branch->update([
            'address_show' => '789 Updated Branch Rd., Chiang Mai',
            'address_private' => '789 Updated Branch Private Rd., Chiang Mai',
            'branch_name' => 'Chiang Mai Branch (Updated)',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $branch->id,
            'address_show' => '789 Updated Branch Rd., Chiang Mai',
            'address_private' => '789 Updated Branch Private Rd., Chiang Mai',
            'branch_name' => 'Chiang Mai Branch (Updated)',
        ]);
    }
}

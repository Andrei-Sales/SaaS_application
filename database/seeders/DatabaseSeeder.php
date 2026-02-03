<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create demo companies with users and data
        $this->createDemoCompany();
        $this->createTestCompany();
    }

    /**
     * Create a demo company with owner and sample data.
     */
    private function createDemoCompany(): void
    {
        $company = Company::create([
            'name' => 'Demo Company Inc.',
            'email' => 'contact@democompany.com',
            'address' => '123 Business St, Suite 100, San Francisco, CA 94102',
            'phone' => '+1 (555) 123-4567',
            'tax_id' => 'TAX-123456789',
        ]);

        // Create Pro subscription
        Subscription::create([
            'company_id' => $company->id,
            'plan' => 'pro',
            'status' => 'active',
            'stripe_subscription_id' => 'sub_demo_' . uniqid(),
        ]);

        // Create owner user
        $owner = User::create([
            'name' => 'John Doe',
            'email' => 'owner@democompany.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'company_id' => $company->id,
            'role' => 'owner',
        ]);

        // Create member user
        User::create([
            'name' => 'Jane Smith',
            'email' => 'member@democompany.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'company_id' => $company->id,
            'role' => 'member',
        ]);

        // Create sample invoices
        Invoice::create([
            'company_id' => $company->id,
            'invoice_number' => 'INV-20260001',
            'client_name' => 'Acme Corporation',
            'client_email' => 'billing@acme.com',
            'client_address' => '456 Client Ave, New York, NY 10001',
            'amount' => 2500.00,
            'status' => 'paid',
            'due_date' => now()->subDays(5),
            'paid_at' => now()->subDays(2),
            'notes' => 'Payment received. Thank you for your business!',
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'invoice_number' => 'INV-20260002',
            'client_name' => 'TechStart LLC',
            'client_email' => 'accounts@techstart.com',
            'client_address' => '789 Startup Blvd, Austin, TX 78701',
            'amount' => 1800.50,
            'status' => 'sent',
            'due_date' => now()->addDays(15),
            'notes' => 'Web development services for Q1 2026',
        ]);

        Invoice::create([
            'company_id' => $company->id,
            'invoice_number' => 'INV-20260003',
            'client_name' => 'Global Enterprises',
            'client_email' => 'finance@globalenterprises.com',
            'amount' => 5000.00,
            'status' => 'draft',
            'due_date' => now()->addDays(30),
            'notes' => 'Consulting services - pending client approval',
        ]);

        // Create a few more invoices for demonstration
        Invoice::factory()->count(7)->create(['company_id' => $company->id]);
    }

    /**
     * Create a test company with free plan.
     */
    private function createTestCompany(): void
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'info@testcompany.com',
            'address' => '100 Test Street, Test City, TC 12345',
            'phone' => '+1 (555) 999-0000',
        ]);

        // Create Free subscription
        Subscription::create([
            'company_id' => $company->id,
            'plan' => 'free',
            'status' => 'trial',
            'trial_ends_at' => now()->addDays(14),
        ]);

        // Create test owner
        User::create([
            'name' => 'Test Owner',
            'email' => 'test@testcompany.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'company_id' => $company->id,
            'role' => 'owner',
        ]);

        // Create a few invoices
        Invoice::factory()->count(5)->create(['company_id' => $company->id]);
    }
}


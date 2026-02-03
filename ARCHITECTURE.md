# Architecture Documentation

## System Overview

This document provides detailed technical documentation of the Multi-Tenant Invoice & Subscription Management SaaS application architecture.

## Table of Contents

1. [Architecture Principles](#architecture-principles)
2. [System Layers](#system-layers)
3. [Multi-Tenancy Deep Dive](#multi-tenancy-deep-dive)
4. [Data Flow](#data-flow)
5. [Security Model](#security-model)
6. [Performance Optimization](#performance-optimization)
7. [Scalability Considerations](#scalability-considerations)

## Architecture Principles

### SOLID Principles Applied

1. **Single Responsibility Principle**
   - Controllers handle HTTP only
   - Services contain business logic
   - Models handle data and relationships
   - Policies handle authorization

2. **Open/Closed Principle**
   - Events/Listeners allow extensibility without modification
   - Services can be extended via inheritance
   - Global scopes can be disabled when needed

3. **Liskov Substitution Principle**
   - Service interfaces allow swapping implementations
   - Multiple mail drivers supported
   - Queue drivers are interchangeable

4. **Interface Segregation Principle**
   - Form Requests separate validation concerns
   - Policies separate authorization by resource
   - Services focused on single domains

5. **Dependency Inversion Principle**
   - Controllers depend on Service abstractions
   - Laravel's Service Container manages dependencies
   - Testable via dependency injection

### Design Patterns Used

- **Service Layer Pattern**: Business logic encapsulation
- **Repository Pattern** (implicit via Eloquent): Data access abstraction
- **Factory Pattern**: Test data generation
- **Observer Pattern**: Model events and listeners
- **Strategy Pattern**: Payment gateways (mock/Stripe)
- **Decorator Pattern**: Global query scopes

## System Layers

### 1. Presentation Layer

**Location**: `resources/views/`, `app/Http/Controllers/`

**Responsibilities**:
- User interface rendering (Blade templates)
- HTTP request/response handling
- Input validation (via Form Requests)
- View data preparation

**Key Files**:
- `InvoiceController.php`: Invoice UI endpoints
- `SubscriptionController.php`: Subscription management UI
- `layouts/app.blade.php`: Master layout
- `invoices/*.blade.php`: Invoice views

### 2. Application Layer

**Location**: `app/Services/`, `app/Http/Requests/`

**Responsibilities**:
- Business logic orchestration
- Transaction management
- Cross-cutting concerns
- Validation rules

**Key Services**:

#### InvoiceService
```php
- getPaginatedInvoices(): Retrieve filtered invoices
- createInvoice(): Create with auto number generation
- updateInvoice(): Update with validation
- deleteInvoice(): Soft delete
- markAsSent(): Change status + dispatch event
- markAsPaid(): Change status + dispatch event
- generateInvoiceNumber(): Auto-increment logic
- getInvoiceStats(): Dashboard statistics
- searchInvoices(): Multi-criteria search
```

#### SubscriptionService
```php
- createSubscription(): Initialize company subscription
- changePlan(): Upgrade/downgrade logic
- cancelSubscription(): Graceful cancellation
- resumeSubscription(): Reactivate canceled
- canAccessFeature(): Feature flag checks
- getInvoiceLimit(): Plan-based limits
- hasReachedInvoiceLimit(): Limit enforcement
```

#### PdfService
```php
- generateInvoicePdf(): Create PDF file
- getInvoicePdfForDownload(): Stream for download
- streamInvoicePdf(): Inline display
```

### 3. Domain Layer

**Location**: `app/Models/`, `app/Events/`, `app/Policies/`

**Responsibilities**:
- Domain entities and relationships
- Business rules enforcement
- Domain events
- Authorization rules

**Core Models**:

#### Company
```php
Relationships:
- hasMany(User)
- hasOne(Subscription)
- hasMany(Invoice)

Methods:
- hasActiveSubscription()
- isOnPlan()
- owner()
```

#### User
```php
Relationships:
- belongsTo(Company)

Methods:
- isOwner()
- isMember()
- canAccessInvoice()
```

#### Invoice
```php
Relationships:
- belongsTo(Company)

Scopes:
- forCompany()
- draft()
- sent()
- paid()

Methods:
- isDraft(), isSent(), isPaid()
- markAsSent(), markAsPaid()
- isOverdue()
```

#### Subscription
```php
Relationships:
- belongsTo(Company)

Methods:
- isActive()
- onTrial()
- canceled()
- expired()
- onPlan()
```

### 4. Infrastructure Layer

**Location**: `app/Jobs/`, `app/Mail/`, `app/Listeners/`

**Responsibilities**:
- External service integration
- Background processing
- Email sending
- Event handling

**Components**:
- `SendInvoiceEmail`: Queued email job
- `InvoiceMail`: Email template and attachments
- Event listeners for logging and notifications

## Multi-Tenancy Deep Dive

### Architecture Decision: Shared Database, Row-Level Tenancy

**Rationale**:
- ✅ Simple to implement and maintain
- ✅ Cost-effective for small-medium tenant counts
- ✅ Easy backup and maintenance
- ✅ Standard Laravel patterns
- ❌ Not suitable for 10,000+ tenants (use database-per-tenant)
- ❌ Requires careful testing for data leaks

### Implementation Layers

#### Layer 1: Database Schema
```sql
-- company_id in every tenant-specific table
CREATE TABLE companies (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255),
    ...
);

CREATE TABLE users (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);

CREATE TABLE invoices (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    FOREIGN KEY (company_id) REFERENCES companies(id)
);
```

#### Layer 2: Global Query Scope

**TenantScope.php**:
```php
public function apply(Builder $builder, Model $model): void
{
    if (auth()->check() && auth()->user()->company_id) {
        $builder->where(
            $model->getTable() . '.company_id', 
            auth()->user()->company_id
        );
    }
}
```

**Effect**: Every Invoice query is automatically filtered:
```php
// Developer writes:
$invoices = Invoice::all();

// Actually executes:
SELECT * FROM invoices WHERE company_id = ?
```

#### Layer 3: Model Trait

**BelongsToTenant.php**:
```php
protected static function bootBelongsToTenant(): void
{
    // Apply global scope
    static::addGlobalScope(new TenantScope);
    
    // Auto-set company_id on creation
    static::creating(function ($model) {
        if (auth()->check() && !$model->company_id) {
            $model->company_id = auth()->user()->company_id;
        }
    });
}
```

**Usage**:
```php
class Invoice extends Model
{
    use BelongsToTenant;  // Adds automatic tenancy
}
```

#### Layer 4: Middleware

**EnsureTenantAccess.php**:
```php
public function handle(Request $request, Closure $next)
{
    if (auth()->check()) {
        if (!auth()->user()->company_id) {
            abort(403, 'You must belong to a company');
        }
        if (!auth()->user()->company) {
            abort(403, 'Your company account is invalid');
        }
    }
    return $next($request);
}
```

#### Layer 5: Authorization Policies

```php
// InvoicePolicy.php
public function view(User $user, Invoice $invoice): bool
{
    return $user->company_id === $invoice->company_id;
}
```

### Data Isolation Testing

**Test Strategy**:
1. Create multiple companies
2. Create users for each company
3. Create data for each company
4. Verify users can only see their data

**Example Test**:
```php
public function test_users_can_only_see_own_invoices()
{
    $companyA = Company::factory()->create();
    $userA = User::factory()->create(['company_id' => $companyA->id]);
    $invoiceA = Invoice::factory()->create(['company_id' => $companyA->id]);
    
    $companyB = Company::factory()->create();
    $userB = User::factory()->create(['company_id' => $companyB->id]);
    $invoiceB = Invoice::factory()->create(['company_id' => $companyB->id]);
    
    $this->actingAs($userA);
    $invoices = Invoice::all();
    
    $this->assertCount(1, $invoices);
    $this->assertEquals($invoiceA->id, $invoices->first()->id);
}
```

## Data Flow

### Invoice Creation Flow

```
User Request
    ↓
InvoiceController::store()
    ↓ (validates via StoreInvoiceRequest)
    ↓ (authorizes via Policy)
    ↓
InvoiceService::createInvoice()
    ↓ (starts DB transaction)
    ↓ (generates invoice number)
    ↓
Invoice::create() [Model]
    ↓ (BelongsToTenant auto-sets company_id)
    ↓ (Saves to database)
    ↓
Event: InvoiceCreated dispatched
    ↓
Listener: SendInvoiceCreatedNotification
    ↓ (logs to file)
    ↓
Service: clearInvoiceCache()
    ↓
Response: Redirect to invoice.show
```

### Invoice Email Flow

```
User clicks "Send Email"
    ↓
InvoiceController::sendEmail()
    ↓ (authorizes)
    ↓
PdfService::generateInvoicePdf()
    ↓ (creates PDF file)
    ↓
SendInvoiceEmail::dispatch() [Job]
    ↓ (queued to database)
    ↓
Queue Worker processes job
    ↓
InvoiceMail::send()
    ↓ (attaches PDF)
    ↓
Mail sent via configured driver
```

## Security Model

### Authentication
- Email/password authentication
- Email verification required
- Session-based authentication
- CSRF protection on all forms

### Authorization Levels

1. **Unauthenticated**: Public pages only
2. **Authenticated**: Dashboard access
3. **Verified**: Full application access
4. **Tenant**: Company-specific data access
5. **Owner Role**: Company management + user management
6. **Member Role**: Invoice management only

### Policy Rules

#### InvoicePolicy
```php
viewAny:  Authenticated + has company
view:     Company ownership match
create:   Authenticated + has company
update:   Company ownership + not paid
delete:   Owner role + company ownership + not paid
send:     Company ownership
markAsPaid: Company ownership + not paid
```

#### CompanyPolicy
```php
view:     Own company only
update:   Owner role only
delete:   Owner role only
manageUsers: Owner role only
manageSubscription: Owner role only
```

### Data Protection

1. **Mass Assignment Protection**: `$fillable` arrays on all models
2. **SQL Injection**: Eloquent ORM parameterized queries
3. **XSS Protection**: Blade `{{ }}` escaping
4. **CSRF Protection**: `@csrf` tokens on forms
5. **Tenant Isolation**: Multiple layers as documented

## Performance Optimization

### Implemented Optimizations

1. **Eager Loading**
   ```php
   Invoice::with('company')->get();  // Prevents N+1
   ```

2. **Query Caching**
   ```php
   Cache::remember("invoice_stats_{$companyId}", 300, function() {
       return Invoice::statistics();
   });
   ```

3. **Pagination**
   ```php
   Invoice::paginate(15);  // Limit query results
   ```

4. **Selective Column Loading**
   ```php
   Invoice::select('id', 'invoice_number', 'amount')->get();
   ```

5. **Database Indexes**
   - Primary keys (automatic)
   - Foreign keys (company_id, user_id)
   - Unique constraints (invoice_number, email)

### Recommended Production Optimizations

1. **Redis Caching**
   ```env
   CACHE_DRIVER=redis
   SESSION_DRIVER=redis
   ```

2. **Queue Worker**
   ```bash
   php artisan queue:work --tries=3 --timeout=60
   ```

3. **OpCode Caching**
   - Enable OPcache in php.ini

4. **CDN for Assets**
   - Serve CSS/JS from CDN

5. **Database Query Optimization**
   - Add indexes for frequent queries
   - Use EXPLAIN to analyze slow queries

## Scalability Considerations

### Current Architecture Limits

- **Tenants**: ~1,000 companies comfortably
- **Concurrent Users**: 50-100 with standard VPS
- **Invoices**: Millions (with proper indexing)

### Scaling Strategies

#### Horizontal Scaling (Users)

1. **Load Balancer** → Multiple web servers
2. **Session Store**: Redis (shared across servers)
3. **Queue Workers**: Dedicated job processing servers

#### Vertical Scaling (Data)

1. **Database Replication**: Read replicas for queries
2. **Sharding**: Split tenants across databases
3. **Archive Old Data**: Move paid invoices to archive table

#### When to Migrate Architecture

**Database-per-Tenant**: If >5,000 tenants  
**Microservices**: If >50,000 users  
**Event Sourcing**: If complex audit requirements  

### Monitoring Recommendations

1. **Application Performance**: Laravel Telescope, New Relic
2. **Database**: Slow query log, Query analyzer
3. **Queue**: Failed jobs monitoring
4. **Errors**: Sentry, Bugsnag
5. **Uptime**: StatusCake, Pingdom

## Conclusion

This architecture provides:
- ✅ Clean separation of concerns
- ✅ Testable code
- ✅ Secure multi-tenancy
- ✅ Scalable to medium size
- ✅ Maintainable codebase
- ✅ Production-ready quality

For questions or improvements, refer to Laravel's official documentation and community best practices.

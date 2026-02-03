<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'invoice_number',
        'client_name',
        'client_email',
        'client_address',
        'amount',
        'status',
        'due_date',
        'paid_at',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_at' => 'date',
    ];

    /**
     * Get the company that owns the invoice.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope a query to only include invoices for a specific company.
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to only include draft invoices.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope a query to only include sent invoices.
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope a query to only include paid invoices.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }

    /**
     * Check if invoice is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if invoice is sent.
     */
    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): bool
    {
        return $this->update(['status' => 'sent']);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): bool
    {
        return $this->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        return !$this->isPaid() && $this->due_date->isPast();
    }
}

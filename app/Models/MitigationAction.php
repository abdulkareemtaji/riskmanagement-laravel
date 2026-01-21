<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MitigationAction extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'risk_id',
        'title',
        'description',
        'status',
        'assigned_to',
        'due_date',
        'completed_date',
        'priority',
        'cost_estimate',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'due_date' => 'date',
        'completed_date' => 'date',
        'cost_estimate' => 'decimal:2',
        'priority' => 'integer',
    ];

    /**
     * Action statuses
     */
    const STATUSES = [
        'planned' => 'Planned',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ];

    /**
     * Priority levels
     */
    const PRIORITIES = [
        1 => 'Critical',
        2 => 'High',
        3 => 'Medium',
        4 => 'Low',
        5 => 'Very Low',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::updating(function ($action) {
            if ($action->status === 'completed' && !$action->completed_date) {
                $action->completed_date = now()->toDateString();
            }
        });
    }

    /**
     * Check if action is overdue.
     */
    public function isOverdue()
    {
        return $this->status !== 'completed' && 
               $this->status !== 'cancelled' && 
               $this->due_date < now()->toDateString();
    }

    /**
     * Get the priority label.
     */
    public function getPriorityLabelAttribute()
    {
        return self::PRIORITIES[$this->priority] ?? 'Unknown';
    }

    /**
     * Get the risk this action belongs to.
     */
    public function risk()
    {
        return $this->belongsTo(Risk::class);
    }

    /**
     * Get the user assigned to this action.
     */
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by assigned user.
     */
    public function scopeByAssignedUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * Scope for overdue actions.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
                    ->where('due_date', '<', now()->toDateString());
    }

    /**
     * Scope for actions due soon (within next 7 days).
     */
    public function scopeDueSoon($query, $days = 7)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled'])
                    ->whereBetween('due_date', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}

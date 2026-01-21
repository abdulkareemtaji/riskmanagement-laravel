<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Risk extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'category',
        'likelihood',
        'impact',
        'risk_score',
        'status',
        'owner_id',
        'department',
        'identified_date',
        'target_closure_date',
        'actual_closure_date',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'identified_date' => 'date',
        'target_closure_date' => 'date',
        'actual_closure_date' => 'date',
        'risk_score' => 'decimal:1',
        'likelihood' => 'integer',
        'impact' => 'integer',
    ];

    /**
     * Risk categories
     */
    const CATEGORIES = [
        'operational' => 'Operational',
        'financial' => 'Financial',
        'compliance' => 'Compliance',
        'strategic' => 'Strategic',
        'reputational' => 'Reputational',
    ];

    /**
     * Risk statuses
     */
    const STATUSES = [
        'identified' => 'Identified',
        'assessed' => 'Assessed',
        'mitigating' => 'Mitigating',
        'closed' => 'Closed',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($risk) {
            $risk->calculateRiskScore();
        });
    }

    /**
     * Calculate and set the risk score.
     */
    public function calculateRiskScore()
    {
        $this->risk_score = $this->likelihood * $this->impact;
    }

    /**
     * Get the risk level based on score.
     */
    public function getRiskLevelAttribute()
    {
        if ($this->risk_score >= 15) {
            return 'high';
        } elseif ($this->risk_score >= 8) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Check if risk is high priority.
     */
    public function isHighRisk()
    {
        return $this->risk_score >= 15;
    }

    /**
     * Get the owner of the risk.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the mitigation actions for the risk.
     */
    public function mitigationActions()
    {
        return $this->hasMany(MitigationAction::class);
    }

    /**
     * Get the assessments for the risk.
     */
    public function assessments()
    {
        return $this->hasMany(RiskAssessment::class);
    }

    /**
     * Get the latest assessment.
     */
    public function latestAssessment()
    {
        return $this->hasOne(RiskAssessment::class)->latestOfMany('assessment_date');
    }

    /**
     * Scope for filtering by category.
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for filtering by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by risk level.
     */
    public function scopeByRiskLevel($query, $level)
    {
        switch ($level) {
            case 'high':
                return $query->where('risk_score', '>=', 15);
            case 'medium':
                return $query->whereBetween('risk_score', [8, 14]);
            case 'low':
                return $query->where('risk_score', '<', 8);
            default:
                return $query;
        }
    }

    /**
     * Scope for filtering by owner.
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
}

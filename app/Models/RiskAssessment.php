<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiskAssessment extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'risk_id',
        'assessor_id',
        'likelihood_before',
        'impact_before',
        'risk_score_before',
        'likelihood_after',
        'impact_after',
        'risk_score_after',
        'assessment_notes',
        'assessment_date',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'assessment_date' => 'date',
        'risk_score_before' => 'decimal:1',
        'risk_score_after' => 'decimal:1',
        'likelihood_before' => 'integer',
        'impact_before' => 'integer',
        'likelihood_after' => 'integer',
        'impact_after' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($assessment) {
            $assessment->calculateRiskScores();
        });
    }

    /**
     * Calculate and set the risk scores.
     */
    public function calculateRiskScores()
    {
        if ($this->likelihood_before && $this->impact_before) {
            $this->risk_score_before = $this->likelihood_before * $this->impact_before;
        }
        
        $this->risk_score_after = $this->likelihood_after * $this->impact_after;
    }

    /**
     * Get the improvement in risk score.
     */
    public function getRiskImprovementAttribute()
    {
        if (!$this->risk_score_before) {
            return null;
        }
        
        return $this->risk_score_before - $this->risk_score_after;
    }

    /**
     * Get the improvement percentage.
     */
    public function getImprovementPercentageAttribute()
    {
        if (!$this->risk_score_before || $this->risk_score_before == 0) {
            return null;
        }
        
        return round((($this->risk_score_before - $this->risk_score_after) / $this->risk_score_before) * 100, 2);
    }

    /**
     * Get the risk this assessment belongs to.
     */
    public function risk()
    {
        return $this->belongsTo(Risk::class);
    }

    /**
     * Get the assessor (user) who performed this assessment.
     */
    public function assessor()
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    /**
     * Scope for filtering by assessor.
     */
    public function scopeByAssessor($query, $assessorId)
    {
        return $query->where('assessor_id', $assessorId);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('assessment_date', [$startDate, $endDate]);
    }
}

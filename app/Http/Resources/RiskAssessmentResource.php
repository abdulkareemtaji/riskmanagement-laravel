<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RiskAssessmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'risk_id' => $this->risk_id,
            'risk' => new RiskResource($this->whenLoaded('risk')),
            'assessor_id' => $this->assessor_id,
            'assessor' => new UserResource($this->whenLoaded('assessor')),
            'likelihood_before' => $this->likelihood_before,
            'impact_before' => $this->impact_before,
            'risk_score_before' => $this->risk_score_before ? (float) $this->risk_score_before : null,
            'likelihood_after' => $this->likelihood_after,
            'impact_after' => $this->impact_after,
            'risk_score_after' => (float) $this->risk_score_after,
            'risk_improvement' => $this->risk_improvement,
            'improvement_percentage' => $this->improvement_percentage,
            'assessment_notes' => $this->assessment_notes,
            'assessment_date' => $this->assessment_date ? $this->assessment_date->toDateString() : null,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}

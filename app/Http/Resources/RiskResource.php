<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class RiskResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'category_label' => \App\Models\Risk::CATEGORIES[$this->category] ?? $this->category,
            'likelihood' => $this->likelihood,
            'impact' => $this->impact,
            'risk_score' => (float) $this->risk_score,
            'risk_level' => $this->risk_level,
            'status' => $this->status,
            'status_label' => \App\Models\Risk::STATUSES[$this->status] ?? $this->status,
            'owner_id' => $this->owner_id,
            'owner' => new UserResource($this->whenLoaded('owner')),
            'department' => $this->department,
            'identified_date' => $this->identified_date ? $this->identified_date->toDateString() : null,
            'target_closure_date' => $this->target_closure_date ? $this->target_closure_date->toDateString() : null,
            'actual_closure_date' => $this->actual_closure_date ? $this->actual_closure_date->toDateString() : null,
            'notes' => $this->notes,
            'is_high_risk' => $this->isHighRisk(),
            'mitigation_actions' => MitigationActionResource::collection($this->whenLoaded('mitigationActions')),
            'latest_assessment' => new RiskAssessmentResource($this->whenLoaded('latestAssessment')),
            'assessments' => RiskAssessmentResource::collection($this->whenLoaded('assessments')),
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MitigationActionResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_label' => \App\Models\MitigationAction::STATUSES[$this->status] ?? $this->status,
            'assigned_to' => $this->assigned_to,
            'assigned_user' => new UserResource($this->whenLoaded('assignedUser')),
            'due_date' => $this->due_date ? $this->due_date->toDateString() : null,
            'completed_date' => $this->completed_date ? $this->completed_date->toDateString() : null,
            'priority' => $this->priority,
            'priority_label' => $this->priority_label,
            'cost_estimate' => $this->cost_estimate ? (float) $this->cost_estimate : null,
            'notes' => $this->notes,
            'is_overdue' => $this->isOverdue(),
            'days_until_due' => $this->due_date ? now()->diffInDays($this->due_date, false) : null,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}

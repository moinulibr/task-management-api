<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\UserResource;

class TaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
        'id' => $this->id,
        'title' => $this->title,
        'description' => $this->description,
        'due_date' => $this->due_date,
        'status' => $this->status,
        'priority' => $this->priority,
        'created_by' => $this->user ? [
            'id' => $this->user->id,
            'name' => $this->user->name,
        ] : null,
        'assigned_users' => $this->assignees
            ? UserResource::collection($this->assignees)
            : [],   
        'created_at' => $this->created_at,
        'updated_at' => $this->updated_at,
    ];
    }
}
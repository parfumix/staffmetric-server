<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource {
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'tracking' => $this->tracking,
            'value_type' => $this->value_type,
            'value' => $this->value,
            'color' => $this->options['color'],
            'due_date' => $this->due_date,
        ];
    }
}

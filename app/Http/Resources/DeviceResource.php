<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DeviceResource extends JsonResource {
    
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request) {
        return [
            'id' => $this->id,
            'uuid' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'is_online' => $this->isOnline(),
            'last_update_at' => $this->last_update_at,
        ];
    }
}

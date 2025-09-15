<?php

namespace App\Http\Resources\wbscte;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'activity_id'                    =>  $this->a_id,
            'activity_ip'                  =>  $this->a_ip,
            'activity_desc'                  =>  $this->a_task,
            'activity_date'                =>  $this->a_date
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class EligibilityBoardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           =>  $this->id,
            'state_name'         =>  $this->state_name,
            'state_code'         =>  $this->state_code,
            'board_name' => strtoupper($this->board_name)
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TradeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'trade_id'                    =>  $this->t_id,
            'trade_code'                  =>  $this->t_code,
            'trade_name'                  =>  $this->t_name,
            'institute_active'                =>  $this->is_active
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'block_id'           =>  $this->id,
            'block_name'         =>  $this->name,
            'subdivision_name'            =>  $this->subdivision->name,
            'subdivision_id'              =>  $this->subdivision_id,
            'schcd'                 =>  $this->schcd,


            //'block_municipality'  =>  BlockMunicipalityResource::collection($this->whenLoaded('district')),
        ];
    }
}

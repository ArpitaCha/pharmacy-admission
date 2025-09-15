<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DistrictResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'district_id'           =>  $this->district_id_pk,
            'district_name'         =>  $this->district_name,
            'state_id'            =>  $this->state_id_fk,

            //'block_municipality'  =>  BlockMunicipalityResource::collection($this->whenLoaded('district')),
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubdivisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'subdivision_id'           =>  $this->id,
            'subdivision_name'         =>  $this->name,
            'district_name'            =>  $this->district->district_name,
            'district_id'              =>  $this->district_id_pk,
            'schcd'                 =>  $this->schcd,


            //'block_municipality'  =>  BlockMunicipalityResource::collection($this->whenLoaded('district')),
        ];
    }
}

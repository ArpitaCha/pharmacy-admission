<?php

namespace App\Http\Resources\wbscte;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //dd($request->segment(3));
        //return parent::toArray($request);
        return [
            'ref_code'  =>   $this->when(
                $request->segment(3) != 'examiner-list',
                fn () => $this->u_ref
            ),
            'user_name' =>  $this->when(
                $request->segment(3) != 'examiner-list',
                fn () => $this->u_username
            ),
            'examiner_id' =>  $this->when(
                $request->segment(3) == 'examiner-list',
                fn () => $this->u_id
            ),
            'full_name' =>  Str::upper($this->u_fullname),
            'role_name' =>   $this->when(
                $request->segment(3) != 'examiner-list',
                fn () => $this->role->role_name
            ),
            'phone'     =>   $this->when(
                $request->segment(3) != 'examiner-list',
                fn () => $this->u_phone
            ),
            'email'     =>   $this->when(
                $request->segment(3) != 'examiner-list',
                fn () => $this->u_email
            ),
            //'properties' => PropertyResource::collection($this->whenLoaded('properties')),
        ];
    }
}

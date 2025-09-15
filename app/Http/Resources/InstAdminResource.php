<?php

namespace App\Http\Resources\wbscte;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InstAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->u_id,
            'inst_code' => $this->u_inst_code,
            'inst_name' => $this->u_inst_name,
            'user_name' => $this->u_username
        ];
    }
}

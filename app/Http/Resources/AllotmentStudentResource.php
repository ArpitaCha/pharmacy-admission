<?php

namespace App\Http\Resources\wbscte;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AllotmentStudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'student_id' => $this->s_id,
            'full_name' => $this->s_candidate_name,
            'trade_name' => optional($this->trade)->t_name,
            'inst_name' => optional($this->institute)->i_name,
            'inst_type' => optional($this->institute)->i_type,
            'gender' => $this->s_gender,
			'religion' => $this->s_religion,
            'caste' => $this->s_caste,
            'phone' => $this->s_phone,
            'alloted_category' => casteValue(Str::upper($this->s_alloted_category)),
            'alloted_round' => $this->s_alloted_round,
            'general_rank' => $this->s_gen_rank

        ];
    }
}

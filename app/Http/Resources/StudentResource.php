<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'appl_form_num'                    =>  $this->s_appl_form_num,
            's_candidate_name'                =>  $this->s_candidate_name,
            's_phone_no'                  =>  $this->s_phone,
            's_father_name'                =>  $this->s_father_name,
            's_mother_name'                =>  $this->s_mother_name,
            'inst_code'                =>  $this->s_inst_code,
            's_caste'                =>  $this->s_caste,
            'trade_code'                =>  $this->s_trade_code,
            's_caste'                =>  $this->s_caste,
            's_caste'                =>  $this->s_caste,
            's_gender'                =>  $this->s_gender,
            's_religion'                =>  $this->s_religion,
            'is_applied' => $this->s_admited_status >= 1,
            'is_paid' => $this->is_payment >= 1,
            'session_year' => $this->session_year,
            's_dob' => $this->s_dob,
            'student_profile_pic' => $this->s_photo ? URL::to("storage/{$this->s_photo}") : null,
            'student_signature' => $this->s_sign ? URL::to("storage/{$this->s_sign}") : null,
        ];
    }
}

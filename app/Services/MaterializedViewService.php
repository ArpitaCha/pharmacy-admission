<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MaterializedViewService
{
    public static function refreshRegisterStudentMV()
    {
        DB::statement('REFRESH MATERIALIZED VIEW jexpo_register_student_mv');
    }
}

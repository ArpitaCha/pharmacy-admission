<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeadVerifierStudentAssign extends Model
{
    protected $table        =   'head_verifier_student_assign';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
    public function student()
    {
        return $this->hasOne('App\Models\Student', 's_appl_form_num', 'student_form_num')->withDefault(function () {
            return new Student();
        });
    }
}

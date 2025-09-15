<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class VerifierStudentAssign extends Model
{
    protected $table        =   'verifier_student_assign';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $guarded = [];
}

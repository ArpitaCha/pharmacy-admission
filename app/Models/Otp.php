<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    use HasFactory;

    protected $table        =   'otp_tbl';
    protected $primaryKey   =   'id';
    public $timestamps      =   false;

    protected $fillable = [
        'username',
        'otp',
        'otp_created_on',
        'otp_count',
    ];
}

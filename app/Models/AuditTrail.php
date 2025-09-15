<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditTrail extends Model
{
    use HasFactory;

    protected $table        =   'audit_trail_tbl';
    protected $primaryKey   =   'audittrail_id_pk';
    public $timestamps      =   false;

    protected $fillable = [
        'audittrail_id_pk',
        'audittrail_user_id',
        'audittrail_ip',
        'audittrail_task',
        'audittrail_date'
    ];
}

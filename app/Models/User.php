<?php

namespace App\Models\wbscte;

use DB;

use Session;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Model
{
    protected $table        =   'jexpo_register_student';
    protected $primaryKey   =   's_id';
    public $timestamps      =   false;

    protected $guarded = [];

    protected function sCandidateName(): Attribute {
        return Attribute::make(
            get: fn (string $value) => Str::upper($value),
        );
    }

    public function rank() {
        return $this->hasOne('App\Models\wbscte\Rank', "r_index_num", "s_index_num")->withDefault(function () {
            return new Rank();
        });
    }

    public function trade() {
        return $this->hasOne('App\Models\wbscte\Trade', "t_code", "s_trade_code")->where('is_active', 1)->withDefault(function () {
            return new Trade();
        });
    }

    public function institute() {
        return $this->hasOne('App\Models\wbscte\Institute', "i_code", "s_inst_code")->where('is_active', 1)->withDefault(function () {
            return new Institute();
        });
    }

    public function homeDistrict() {
        return $this->belongsTo(District::class, 's_home_district', 'd_id');
    }

    public function schoolDistrict() {
        return $this->belongsTo(District::class, 's_schooling_district', 'd_id');
    }

    public function state() {
        return $this->belongsTo(State::class, 's_state_id', 'state_id_pk');
    }

    public function choices() {
        return $this->hasMany(StudentChoice::class, "ch_stu_id", "s_id");
    }
}

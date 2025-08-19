<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InegiState extends Model
{
    protected $fillable = [
        'cvegeo','cve_ent','nomgeo','nom_abrev',
        'pob_total','pob_femenina','pob_masculina','total_viviendas_habitadas',
        'raw',
    ];

    protected $casts = [
        'raw' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'cve_ent';
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationActivity extends Model
{
    protected $table = 'organization_activities';

    public $incrementing = true;

    protected $fillable = [
        'organization_id',
        'activity_id'
    ];
}

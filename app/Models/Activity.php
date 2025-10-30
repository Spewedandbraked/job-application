<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    /** @use HasFactory<\Database\Factories\ActivityFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'parent_id',
        'level'
    ];

    public function parent()
    {
        return $this->belongsTo(Activity::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Activity::class, 'parent_id');
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class, 'organization_activities');
    }

    // Получение всех потомков (включая вложенные)
    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    // Получение всех организаций по дереву деятельности
    public function getOrganizationsInTree()
    {
        $activityIds = $this->getDescendantIds();
        $activityIds[] = $this->id;

        return Organization::whereHas('activities', function ($query) use ($activityIds) {
            $query->whereIn('activities.id', $activityIds);
        })->get();
    }

    // Получение ID всех потомков
    private function getDescendantIds()
    {
        $ids = [];
        foreach ($this->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $child->getDescendantIds());
        }
        return $ids;
    }
}

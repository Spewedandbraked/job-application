<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Building;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizationController extends Controller
{
    public function byBuilding($buildingId): JsonResponse
    {
        $building = Building::find($buildingId);

        if (!$building) {
            return response()->json([
                'error' => 'Building not found'
            ], 404);
        }

        $organizations = Organization::with(['phones', 'activities', 'building'])
            ->where('building_id', $buildingId)
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'phones' => $org->phones->pluck('phone_number'),
                    'building' => [
                        'id' => $org->building->id,
                        'address' => $org->building->address,
                        'coordinates' => [
                            'lat' => $org->building->latitude,
                            'lng' => $org->building->longitude
                        ]
                    ],
                    'activities' => $org->activities->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'name' => $activity->name,
                            'level' => $activity->level
                        ];
                    })
                ];
            });

        return response()->json([
            'building' => [
                'id' => $building->id,
                'address' => $building->address
            ],
            'organizations' => $organizations,
            'count' => $organizations->count()
        ]);
    }

    public function byActivity($activityId): JsonResponse
    {
        $activity = Activity::find($activityId);

        if (!$activity) {
            return response()->json([
                'error' => 'Activity not found'
            ], 404);
        }

        $organizations = Organization::with(['phones', 'activities', 'building'])
            ->whereHas('activities', function ($query) use ($activityId) {
                $query->where('activities.id', $activityId);
            })
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'phones' => $org->phones->pluck('phone_number'),
                    'building' => [
                        'id' => $org->building->id,
                        'address' => $org->building->address
                    ],
                    'activities' => $org->activities->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'name' => $activity->name
                        ];
                    })
                ];
            });

        return response()->json([
            'activity' => [
                'id' => $activity->id,
                'name' => $activity->name
            ],
            'organizations' => $organizations,
            'count' => $organizations->count()
        ]);
    }

    public function nearby(Request $request): JsonResponse
    {
        Validator::make($request->all(), [
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'radius' => 'nullable|numeric',
            'bbox' => 'nullable|string|regex:/^-?\d+\.?\d*,-?\d+\.?\d*,-?\d+\.?\d*,-?\d+\.?\d*$/'
        ], [
            'bbox.regex' => 'The bbox parameter must contain exactly 4 floating-point numbers separated by commas without spaces. Example: 55.9,37.5,56.0,37.6'
        ])->validate();

        $lat = $request->lat;
        $lng = $request->lng;
        $radius = $request->radius;
        $bbox = $request->bbox;

        $query = Organization::with(['phones', 'activities', 'building']);

        // Поиск в радиусе (приблизительная формула)
        if ($radius) {
            $query->whereHas('building', function ($q) use ($lat, $lng, $radius) {
                $latRange = $radius / 111.0;
                $lngRange = $radius / (111.0 * cos(deg2rad($lat)));

                $q->whereBetween('latitude', [$lat - $latRange, $lat + $latRange])
                    ->whereBetween('longitude', [$lng - $lngRange, $lng + $lngRange])
                    ->whereRaw(
                        '6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))) <= ?',
                        [$lat, $lng, $lat, $radius]
                    );
            });
        }
        // Поиск в прямоугольной области 
        elseif ($bbox) {
            $coords = explode(',', $bbox);
            if (count($coords) === 4) {
                [$minLat, $minLng, $maxLat, $maxLng] = array_map('floatval', $coords);

                $query->whereHas('building', function ($q) use ($minLat, $maxLat, $minLng, $maxLng) {
                    $q->whereBetween('latitude', [$minLat, $maxLat])
                        ->whereBetween('longitude', [$minLng, $maxLng]);
                });
            }
        } else {
            return response()->json([
                'error' => 'Either radius or bbox parameter is required'
            ], 400);
        }

        $organizations = $query->get()->map(function ($org) {
            return [
                'id' => $org->id,
                'name' => $org->name,
                'phones' => $org->phones->pluck('phone_number'),
                'building' => [
                    'id' => $org->building->id,
                    'address' => $org->building->address,
                    'coordinates' => [
                        'lat' => $org->building->latitude,
                        'lng' => $org->building->longitude
                    ]
                ],
                'activities' => $org->activities->map(function ($activity) {
                    return [
                        'id' => $activity->id,
                        'name' => $activity->name
                    ];
                })
            ];
        });

        return response()->json([
            'center' => ['lat' => $lat, 'lng' => $lng],
            'radius' => $radius,
            'bbox' => $bbox,
            'organizations' => $organizations,
            'count' => $organizations->count()
        ]);
    }

    public function show($id): JsonResponse
    {
        $organization = Organization::with(['phones', 'activities', 'building'])->find($id);

        if (!$organization) {
            return response()->json([
                'error' => 'Organization not found'
            ], 404);
        }

        return response()->json([
            'id' => $organization->id,
            'name' => $organization->name,
            'phones' => $organization->phones->pluck('phone_number'),
            'building' => [
                'id' => $organization->building->id,
                'address' => $organization->building->address,
                'coordinates' => [
                    'lat' => $organization->building->latitude,
                    'lng' => $organization->building->longitude
                ]
            ],
            'activities' => $organization->activities->map(function ($activity) {
                return [
                    'id' => $activity->id,
                    'name' => $activity->name,
                    'level' => $activity->level,
                    'parent_id' => $activity->parent_id
                ];
            })
        ]);
    }

    public function searchByActivity(Request $request): JsonResponse
    {
        $request->validate([
            'activity_id' => 'required|exists:activities,id'
        ]);

        $activity = Activity::with('descendants')->find($request->activity_id);
        
        $activityIds = $this->getActivityWithDescendantsIds($activity);

        $organizations = Organization::with(['phones', 'activities', 'building'])
            ->whereHas('activities', function ($query) use ($activityIds) {
                $query->whereIn('activities.id', $activityIds);
            })
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'phones' => $org->phones->pluck('phone_number'),
                    'building' => [
                        'id' => $org->building->id,
                        'address' => $org->building->address
                    ],
                    'activities' => $org->activities->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'name' => $activity->name,
                            'level' => $activity->level
                        ];
                    })
                ];
            });

        return response()->json([
            'search_activity' => [
                'id' => $activity->id,
                'name' => $activity->name
            ],
            'included_activity_ids' => $activityIds,
            'organizations' => $organizations,
            'count' => $organizations->count()
        ]);
    }

    public function searchByName(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|min:2'
        ]);

        $organizations = Organization::with(['phones', 'activities', 'building'])
            ->where('name', 'like', '%' . $request->name . '%')
            ->get()
            ->map(function ($org) {
                return [
                    'id' => $org->id,
                    'name' => $org->name,
                    'phones' => $org->phones->pluck('phone_number'),
                    'building' => [
                        'id' => $org->building->id,
                        'address' => $org->building->address
                    ],
                    'activities' => $org->activities->map(function ($activity) {
                        return [
                            'id' => $activity->id,
                            'name' => $activity->name
                        ];
                    })
                ];
            });

        return response()->json([
            'search_query' => $request->query,
            'organizations' => $organizations,
            'count' => $organizations->count()
        ]);
    }


    private function getActivityWithDescendantsIds(Activity $activity): array
    {
        $ids = [$activity->id];

        foreach ($activity->descendants as $descendant) {
            $ids[] = $descendant->id;
            if ($descendant->descendants) {
                foreach ($descendant->descendants as $child) {
                    $ids[] = $child->id;
                }
            }
        }

        return array_unique($ids);
    }
}

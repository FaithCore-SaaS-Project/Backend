<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $casts = [
        'features' => 'array',
        'is_popular' => 'boolean',
    ];

    /**
     * Check if the plan supports a specific feature using dot-notation.
     * e.g., $plan->hasFeature('members.export_csv')
     */
    public function hasFeature(string $featureKey): bool
    {
        $features = $this->features;
        if (is_string($features)) {
            $features = json_decode($features, true);
        }

        if (!is_array($features)) {
            return false;
        }

        $keys = explode('.', $featureKey);
        $current = $features;

        foreach ($keys as $key) {
            if (!is_array($current) || !isset($current[$key])) {
                return false;
            }
            $current = $current[$key];
        }

        return (bool) $current;
    }
}

<?php

namespace App\Http\Resources\V1\AppGroups;

class UnsetCurrentAppGroups
{
    /**
     * @param \Illuminate\Http\Request $request
     */
    public function process(\Illuminate\Http\Request $request)
    {
        $user = $request->user();

        $user->app_groups()->update([
            'is_default' => false
        ]);
    }
}

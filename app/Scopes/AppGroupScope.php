<?php

namespace App\Scopes;

use App\AppGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AppGroupScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = request()->user();

        if ($user !== null) {
            $app_group = isset($user->currentAppGroup()->id) ? $user->currentAppGroup()
                : $user->defaultAppGroup();

            if (isset($app_group->id)) {
                return $builder->where('app_group_id', '=', $app_group->id);
            }
        }
    }
}

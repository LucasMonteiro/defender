<?php

namespace Artesaos\Defender\Traits;

trait HasPermissionsTrait
{
    /**
     * Get the a permission using the permission name.
     *
     * @param string $permissionName
     *
     * @return bool
     */
    public function hasPermission($permissionName)
    {
        $permission = $this->permissions->first(function($key, $value) use ($permissionName){
            return ($value->name == $permissionName);
        });

        if(!empty($permission))
        {
            $active = (is_null($permission->pivot->expires) or $permission->pivot->expires->isFuture());

            if($active)
            {
                return (bool) $permission->pivot->value;
            }
        }

        return false;
    }

    /**
     * Attach the given permission.
     *
     * @param array|\Artesaos\Defender\Permission $permission
     * @param array            $options
     */
    public function attachPermission($permission, array $options = [])
    {

        if(!is_array($permission))
        {
            if($this->hasPermission($permission->name))
            {
                return;
            }
        } 

        $this->permissions()->attach($permission, [
            'value'   => array_get($options, 'value', true),
            'expires' => array_get($options, 'expires', null),
        ]);

    }

    /**
     * Detach the given permission from the model.
     *
     * @param $permission
     *
     * @return int
     */
    public function detachPermission($permission)
    {
        return $this->permissions()->detach($permission);
    }

    /**
     * Alias to the detachPermission method.
     *
     * @param $permission
     *
     * @return int
     */
    public function revokePermission($permission)
    {
        return $this->detachPermission($permission);
    }

    /**
     * Sync the given permissions.
     *
     * @param array $permissions
     *
     * @return array
     */
    public function syncPermissions(array $permissions)
    {
        return $this->permissions()->sync($permissions);
    }

    /**
     * Revoke all user permissions.
     *
     * @return int
     */
    public function revokePermissions()
    {
        return $this->permissions()->detach();
    }

    /**
     * Revoke expired user permissions.
     *
     * @return int|null
     */
    public function revokeExpiredPermissions()
    {
        $expiredPermissions = $this->permissions()->wherePivot('expires', '<', Carbon::now())->get();

        if ($expiredPermissions->count() > 0) {
            return $this->permissions()->detach($expiredPermissions->modelKeys());
        }

        return;
    }

    /**
     * Extend an existing temporary permission.
     *
     * @param $permission
     * @param array $options
     *
     * @return bool|null
     */
    public function extendPermission($permission, array $options)
    {
        foreach ($this->permissions as $_permission) {
            if ($_permission->name === $permission) {
                return $this->permissions()->updateExistingPivot($_permission->id,
                    array_only($options, ['value', 'expires']
                ));
            }
        }

        return;
    }
}

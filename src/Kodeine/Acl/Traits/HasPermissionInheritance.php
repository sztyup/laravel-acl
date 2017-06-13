<?php namespace Kodeine\Acl\Traits;


use Illuminate\Support\Arr;

trait HasPermissionInheritance
{
    protected $cacheInherit;

    /*
    |----------------------------------------------------------------------
    | Permission Inheritance Trait Methods
    |----------------------------------------------------------------------
    |
    */

    public function getPermissionsInherited()
    {
        $rights = [];
        $permissions = $this->permissions;

        foreach ($permissions as $row) {

            // permissions without inherit ids
            if ( is_null($row->inherit_id) || ! $row->inherit_id ) {
                continue;
            }

            // process inherit_id recursively
            $inherited = array_merge($this->getRecursiveInherit($row->inherit_id, $row->slug));
            $merge = $permissions->where('name', $row->name);
            $merge = method_exists($merge, 'pluck') ? $merge->pluck('slug', 'name') : $merge->lists('slug', 'name');

            // fix for l5.1 and backward compatibility.
            // lists() method should return as an array.
            $merge = $this->collectionAsArray($merge);

            // replace and merge permissions
            $rights = array_replace_recursive($rights, $inherited, $merge);
        }

        return $rights;
    }

    /**
     * @param $inherit_id
     * @param $permissions
     * @return array
     */
    protected function getRecursiveInherit($inherit_id, $permissions)
    {
        // get from cache or sql.
        $inherit = $this->getInherit($inherit_id);

        $inherits = [$inherit];

        if ( $inherit->exists ) {

            // follow along into deeper inherited permissions recursively
            while($inherit && $inherit->inherit_id > 0 && ! is_null($inherit->inherit_id)) {

                // get inherit permission from cache or sql.
                $inherit = $this->getInherit($inherit->inherit_id);
                $inherits = Arr::prepend($inherits, $inherit);
            };

            $inherits = Arr::pluck($inherits, "name");
            $inherits = array_fill_keys($inherits, ["" => true]);

            return array_merge([$inherit->name => $permissions], $inherits);
        }

        return $permissions;
    }

    /**
     * @param $inherit_id
     * @return bool|mixed
     */
    protected function getInherit($inherit_id)
    {
        if ( $cache = $this->hasCache($inherit_id) ) {
            return $cache;
        }

        $model = config('acl.permission', 'Kodeine\Acl\Models\Eloquent\Permission');
        $query = (new $model)->where('id', $inherit_id)->first();

        return is_object($query) ? $this->setCache($query) : false;
    }

    /**
     * @param $inherit_id
     * @return bool|mixed
     */
    protected function hasCache($inherit_id)
    {
        if ( isset($this->cacheInherit[$inherit_id]) ) {
            return $this->cacheInherit[$inherit_id];
        }

        return false;
    }

    /**
     * @param $inherit
     * @return mixed
     */
    protected function setCache($inherit)
    {
        return $this->cacheInherit[$inherit->getKey()] = $inherit;
    }

    /**
     * Parses permission id from object or array.
     *
     * @param object|array|int $permission
     * @return mixed
     */
    /*protected function parsePermissionId($permission)
    {
        if ( is_string($permission) || is_numeric($permission) ) {

            $model = config('acl.permission', 'Kodeine\Acl\Models\Eloquent\Permission');
            $key = is_numeric($permission) ? 'id' : 'name';
            $alias = (new $model)->where($key, $permission)->first();

            if ( ! is_object($alias) || ! $alias->exists ) {
                throw new \InvalidArgumentException('Specified permission ' . $key . ' does not exists.');
            }

            $permission = $alias->getKey();
        }

        $model = '\Illuminate\Database\Eloquent\Model';
        if ( is_object($permission) && $permission instanceof $model ) {
            $permission = $permission->getKey();
        }

        return (int) $permission;
    }*/

}
<?php namespace Kodeine\Acl\Models\Eloquent;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    /**
     * The attributes that are fillable via mass assignment.
     *
     * @var array
     */
    protected $fillable = ['name', 'description', 'inherit_id'];

    protected $appends = ['slug'];

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * Permissions can belong to many roles.
     *
     * @return Model
     */
    public function roles()
    {
        $model = config('acl.role', 'Kodeine\Acl\Models\Eloquent\Role');

        return $this->belongsToMany($model)->withTimestamps();
    }

    /**
     * Permissions can belong to many users.
     *
     * @return Model
     */
    public function users()
    {
        return $this->belongsToMany(config('auth.providers.users.model', config('auth.model')))->withTimestamps();
    }

    /**
     * @param $value
     * @return array
     */
    public function getSlugAttribute($value)
    {
        return ["" => true];
    }

    /**
     * @param $value
     */
    public function setSlugAttribute($value)
    {

    }

}

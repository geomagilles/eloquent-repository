<?php
/**
 * This file is part of the EloquentRepository package.
 *
 * (c) Gilles Barbier <geomagilles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Geomagilles\EloquentRepository;
 
use IteratorAggregate;

abstract class EloquentRepository implements EloquentRepositoryInterface
{
/**
     * Per default, all attributes are blocked from mass assignement
     *
     * @var array
     */
    protected $guarded = array('*');

    /**
     * The eloquent model
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The matching array between application keys and database keys
     *
     * @var array
     */
    protected static $matching = array();

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    //
    // HELPERS
    //

    public function getTable()
    {
        return $this->model->getTable();
    }

    public function make(array $with = array())
    {
        return $this->model->with($with);
    }

    /**
     * Matches application keys with database keys
     * @param string $key (eg. 'projectId')
     * @return string (eg. 'project_id')
     */
    protected function match($data)
    {
        if (is_array($data) || ($data instanceof Traversable)) {
            $new = array();
            foreach ($data as $key => $value) {
                $new[$this->match($key)] = $value;
            }
            return $new;
        } else {
            if (in_array($data, array_keys(static::$matching))) {
                return static::$matching[$data];
            } else {
                return Str::snake($data, '_');
            }
        }
    }

    public function wrap($model)
    {
        if (is_array($model) || ($model instanceof IteratorAggregate)) {
            $instances = array();
            foreach ($model as $key => $instance) {
                $instances[$key] = $this->wrap($instance);
            }
            return $instances;
        } else {
            return is_null($model) ? null : new static($model);
        }
    }
    
    //
    // INSTANCE METHOD
    //

    public function save()
    {
        $this->model->save();
    }

    public function delete()
    {
        return $this->model->delete();
    }

    public function update($data = array())
    {
        $this->model->update($this->match($data));
    }

    public function getId()
    {
        return $this->model->id;
    }

    /**
     * Return model's attribute
     * @param $method (eg. 'getId')
     * @return mixed
     */
    protected function get($method)
    {
        $key = lcfirst(substr($method, 3));
        return $this->model->__get($this->match($key));
    }

    /**
     * Set model's attribute
     * @param $method (eg 'setId')
     * @param $d
     * @throws \Exception if unknown key
     * @return mixed
     */
    protected function set($method, $d)
    {
        $key = lcfirst(substr($method, 3));
        return $this->model->__set($this->match($key), $d);
    }

    /**
     * Generic implementation of getter/setter
     * @param $method (eg 'setId')
     * @param $arguments
     * @throws \Exception
     * @return mixed
     */
    public function __call($method, $arguments = array())
    {
        if (preg_match('#^get[A-Z]+#', $method)) {
            // getter method
            if (count($arguments)>0) {
                throw new \Exception("getter method ($method) can NOT have any parameter");
            }
            return $this->get($method);
        } elseif (preg_match('#^set[A-Z]+#', $method)) {
            // setter method
            if (count($arguments)>1) {
                throw new \Exception("setter method ($method) can NOT have more than 1 parameter");
            }
            $key = lcfirst(substr($method, 3));
            return $this->set($method, count($arguments)==0 ? null : $arguments[0]);
        } else {
            throw new \Exception("unknown method: $method");
        }
    }

    //
    // STATIC METHODS
    //

    public function setObserver($observer)
    {
        $model = $this->model;
        $apply = function ($observer, $observerEvent, $modelEvent) use ($model) {
            $model->$modelEvent(
                function ($m) use ($observer, $observerEvent) {
                    return $observer->$observerEvent(static::wrap($m));
                });
        };
        if (method_exists($observer, 'creating')) {
            $apply($observer, 'creating', 'creating');
        }
        if (method_exists($observer, 'created')) {
            $apply($observer, 'created', 'created');
        }
        if (method_exists($observer, 'updating')) {
            $apply($observer, 'updating', 'updating');
        }
        if (method_exists($observer, 'updated')) {
            $apply($observer, 'updated', 'updated');
        }
        if (method_exists($observer, 'deleting')) {
            $apply($observer, 'deleting', 'deleting');
        }
        if (method_exists($observer, 'deleted')) {
            $apply($observer, 'deleted', 'deleted');
        }
        if (method_exists($observer, 'saving')) {
            $apply($observer, 'saving', 'saving');
        }
        if (method_exists($observer, 'saved')) {
            $apply($observer, 'saved', 'saved');
        }
        if (method_exists($observer, 'restoring')) {
            $apply($observer, 'restoring', 'restoring');
        }

        return $this;
    }

    public function create(array $data = array())
    {
        return self::wrap($this->make()->create($this->match($data)));
    }

    public function getAll(array $with = array())
    {
        return self::wrap($this->make($with)->get());
    }

    public function getById($id, array $with = array())
    {
        return self::wrap($this->make($with)->find($id));
    }

    public function deleteById($id)
    {
        $this->make()->destroy($id);
    }

    public function getFirstBy($key, $value, array $with = array())
    {
        $key = $this->match($key);
    
        return self::wrap($this->make($with)->where($key, '=', $value)->first());
    }

    public function getManyBy($key, $value, array $with = array())
    {
        $key = $this->match($key);
         
        return self::wrap($this->make($with)->where($key, '=', $value)->get());
    }

    public function deleteFirstBy($key, $value)
    {
        $key = $this->match($key);
    
        return self::wrap($this->make()->where($key, '=', $value)->take(1)->delete());
    }

    public function deleteManyBy($key, $value)
    {
        $key = $this->match($key);
    
        return self::wrap($this->make()->where($key, '=', $value)->delete());
    }

    public function getByPage($page = 1, $limit = 10, array $with = array())
    {
        $result             = new StdClass;
        $result->page       = $page;
        $result->limit      = $limit;
        $result->totalItems = 0;
        $result->items      = array();
    
        $query = $this->make($with);
    
        $items = $query->skip($limit * ($page - 1))
                       ->take($limit)
                       ->get();
    
        $result->totalItems = $this->model->count();
        $result->items      = self::wrap($items);
    
        return $result;
    }
}
<?php
/**
 * This file is part of the Eloquent-Repository package.
 *
 * (c) Gilles Barbier <geomagilles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Geomagilles\EloquentRepository;
 
use IteratorAggregate;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

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

    public function getModel()
    {
        return $this->model;
    }

    public function make(array $with = array())
    {
        return $this->model->with($with);
    }

    public function toArray()
    {
        return $this->reverseMatch($this->model->toArray());
    }

    /**
     * Matches application keys with database keys and jsonify arrays attribute
     * @param string $key (eg. 'projectId')
     * @return string (eg. 'project_id')
     */
    protected function match($data)
    {
        if (is_array($data) || ($data instanceof Traversable)) {
            $new = array();
            foreach ($data as $key => $value) {
                $new[$this->match($key)] = is_array($value) ? json_encode($value) : $value;
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

    /**
     * Matches database keys with application keys
     * @param string $key (eg. 'project_id')
     * @return string (eg. 'projectId')
     */
    protected function reverseMatch($data)
    {
        if (is_array($data) || ($data instanceof Traversable)) {
            $result = array();
            foreach ($data as $key => $value) {
                $result[$this->reverseMatch($key)] = $value;
            }
            return $result;
        } else {
            if ($key = array_search($data, static::$matching)) {
                return $key;
            } else {
                return Str::camel($data);
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
        return $this;
    }

    public function delete()
    {
        $this->model->delete();
    }

    public function update($data = array())
    {
        $this->model->update($this->match($data));
        return $this;
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
        $key = $this->match(lcfirst(substr($method, 3)));
        return $this->model->__get($key);
    }

    /**
     * Set model's attribute
     * @param $method (eg 'setId')
     * @param $d
     * @return self
     */
    protected function set($method, $d)
    {
        $key = $this->match(lcfirst(substr($method, 3)));
        $this->model->__set($key, $d);
        return $this;
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
                $className = get_class($this);
                throw new \Exception("getter {$className}::{$method}) can NOT have any parameter");
            }
            return $this->get($method);
        } elseif (preg_match('#^set[A-Z]+#', $method)) {
            // setter method
            if (count($arguments)>1) {
                $className = get_class($this);
                throw new \Exception("setter {$className}::{$method} can NOT have more than 1 parameter");
            }
            $key = lcfirst(substr($method, 3));
            return $this->set($method, count($arguments)==0 ? null : $arguments[0]);
        } else {
            $className = get_class($this);
            throw new \BadMethodCallException("Call to undefined method {$className}::{$method}");
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
        return self::wrap($this->model->create($this->match($data)));
    }

    public function getAll(array $with = array())
    {
        return self::wrap($this->make($with)->get());
    }

    public function getById($id, array $with = array())
    {
        return $this->getFirstBy('id', $id, $with);
    }

    public function deleteById($id)
    {
        return $this->deleteFirstBy('id', $id);
    }

    public function getFirstBy($key, $value, array $with = array())
    {
        $key = $this->match($key);
    
        return self::wrap($this->make($with)->where($key, '=', $value)->first());
    }

    public function deleteFirstBy($key, $value)
    {
        $key = $this->match($key);
    
        $this->make()->where($key, '=', $value)->take(1)->delete();
    }
    
    public function getManyBy($key, $value, array $with = array())
    {
        $key = $this->match($key);
         
        return self::wrap($this->make($with)->where($key, '=', $value)->get());
    }

    public function deleteManyBy($key, $value)
    {
        $key = $this->match($key);
    
        $this->make()->where($key, '=', $value)->delete();
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

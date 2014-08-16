<?php
/**
 * This file is part of the EloquentRepository framework.
 *
 * (c) Gilles Barbier <geomagilles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Geomagilles\EloquentRepository;

use IteratorAggregate;
use Geomagilles\EloquentRepository\MultiTenantContextInterface;

abstract class GenericMultiTenantRepository extends BaseRepository implements EloquentRepositoryInterface
{
    /**
     * The multi tenant context
     *
     * @var Geomagilles\EloquentRepository\MultiTenantContextInterface
     */
    protected $multiTenantContext;

    public function wrap($data)
    {
        if (is_array($data) || ($data instanceof IteratorAggregate)) {
            $instances = array();
            foreach ($data as $key => $instance) {
                $instances[$key] = $this->wrap($instance);
            }
            return $instances;
        } else {
            return is_null($data) ? null : new static($data, $this->multiTenantContext);
        }
    }

    public function create(array $data = array())
    {
        $entity = parent::create($data);
        // multi-tenant management
        if ($this->multiTenantContext->hasTenant()) {
            $column = $this->multiTenantContext->getTenantKey();
            if (! isset($data[$column])) {
                $entity->set('set'.$column, $this->multiTenantContext->getTenantId());
                $entity->save();
            }
        }
        
        return $entity;
    }

    /**
     * Make a new instance of the entity to query on
     *
     * @param array $with
     */
    public function make(array $with = array())
    {
        $model = parent::make($with);
        if ($this->multiTenantContext->hasTenant()) {
            $column = $this->match($this->multiTenantContext->getTenantKey());
            return $model->where($column, '=', $this->multiTenantContext->getTenantId());
        }

        return $model;
    }
}

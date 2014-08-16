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

abstract class EloquentRepository extends BaseRepository implements EloquentRepositoryInterface
{
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
}

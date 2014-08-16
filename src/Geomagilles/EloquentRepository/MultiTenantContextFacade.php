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

use Illuminate\Support\Facades\Facade;

class MultiTenantContextFacade extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Geomagilles\EloquentRepository\MultiTenantContextInterface';
    }
}

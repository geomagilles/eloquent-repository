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
 
interface MultiTenantContextInterface
{
    /**
     * Set the tenant Id.
     *
     * @param mixed $tenantId
     */
    public function setTenantId($tenantId);
 
     /**
     * Get the tenant Id.
     *
     * @return mixed
     */
    public function getTenantId();

    /**
     * Set the tenant key.
     *
     * @param string
     */
    public function setTenantKey($tenantKey);

    /**
     * Get the tenant key.
     *
     * @return string
     */
    public function getTenantKey();

    /**
     * Check to see if the context has been set.
     *
     * @return boolean
     */
    public function hasTenant();
}

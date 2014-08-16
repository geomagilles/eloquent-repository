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

class MultiTenantContext implements MultiTenantContextInterface
{

    /**
     * The id of current tenant.
     *
     * @var mixed
     */
    protected $tenantId;

    /**
     * The key of current tenant.
     *
     * @var string
     */
    protected $tenantKey;


    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    public function getTenantId()
    {
        return $this->tenantId;
    }

    public function setTenantKey($tenantKey)
    {
        $this->tenantKey = $tenantKey;
    }

    public function getTenantKey()
    {
        return $this->tenantKey;
    }

    public function hasTenant()
    {
        return (bool) ($this->tenantId);
    }
}

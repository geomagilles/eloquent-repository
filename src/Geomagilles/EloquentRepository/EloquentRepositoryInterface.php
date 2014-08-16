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

/**
 * Interface ModelRepositoryInterface
 */
interface EloquentRepositoryInterface
{
    /**
     * Gets subjascent eloquent model
     * @param array $with
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function getModel(array $with = array());

    /**
     * Gets table name 
     * @return string
     */
    public function getTable();

    //
    // INSTANCE METHOD
    //

    /**
     * Update current entity
     * 
     * @param array $data
     * @return void
     */
    public function update($data = []);

    /**
     * Delete current entity
     * 
     * @return void
     */
    public function delete();

    /**
     * Save current entity
     * 
     * @return void
     */
    public function save();

    /**
     * Return entity's id
     * @return mixed
     */
    public function getId();

    //
    // CLASS METHOD
    //

    /**
     * Wraps one or more models into a EloquentRepositoryInterface 
     * @param $data
     * @return Geomagilles\EloquentRepository\EloquentRepositoryInterface|[]
     */
    public function wrap($data);

    /**
     * Apply an observer this repository 
     * @param $observer
     * @return self
     */
    public function setObserver($observer);

    /**
     * Create a new entity
     * 
     * @param array $data
     * @return ModelRepositoryInterface
     */
    public function create(array $data = array());

    /**
     * Return all entities
     *
     * @return EloquentRepositoryInterface[]
     */
    public function getAll(array $with = array());

    /**
     * Find an entity by Id
     *
     * @param $id
     * @param array $with
     * @return EloquentRepositoryInterface|null
     */
    public function getById($id, array $with = array());

    /**
     * Get Results by Page
     *
     * @param int $page
     * @param int $limit
     * @param array $with
     * @return StdClass Object with $items and $totalItems for pagination
     */
    public function getByPage($page = 1, $limit = 10, array $with = array());

    /**
     * Delete an entity by id
     * 
     * @param $id
     */
    public function deleteById($id);

    /**
     * Find a single entity by key value
     *
     * @param string $key
     * @param string $value
     * @param array $with
     * @return Geomagilles\EloquentRepository\EloquentRepositoryInterface|null
     */
    public function getFirstBy($key, $value, array $with = array());

    /**
     * Find many entities by key value
     *
     * @param string $key
     * @param string $value
     * @param array $with
     * @return Geomagilles\EloquentRepository\EloquentRepositoryInterface[]|null
     */
    public function getManyBy($key, $value, array $with = array());

    /**
     * Delete a single entity by key value
     *
     * @param string $key
     * @param string $value
     */
    public function deleteFirstBy($key, $value);

    /**
     * Delete many entities by key value
     *
     * @param string $key
     * @param string $value
     */
    public function deleteManyBy($key, $value);
}

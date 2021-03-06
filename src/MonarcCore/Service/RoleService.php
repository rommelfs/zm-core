<?php
/**
 * @link      https://github.com/CASES-LU for the canonical source repository
 * @copyright Copyright (c) Cases is a registered trademark of SECURITYMADEIN.LU
 * @license   MyCases is licensed under the GNU Affero GPL v3 - See license.txt for more information
 */

namespace MonarcCore\Service;

/**
 * Role Service
 *
 * Class RoleService
 * @package MonarcCore\Service
 */
class RoleService extends AbstractService
{
    protected $config;

    /**
     * Get Filtered Count
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param null $filterAnd
     * @return int
     */
    public function getFilteredCount($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $config = $this->config;
        $roles = $config['roles'];

        return count($roles);
    }

    /**
     * Get List
     *
     * @param int $page
     * @param int $limit
     * @param null $order
     * @param null $filter
     * @param null $filterAnd
     * @return array
     */
    public function getList($page = 1, $limit = 25, $order = null, $filter = null, $filterAnd = null)
    {
        $roleCollection = [];

        $config = $this->config;
        $roles = $config['roles'];
        foreach ($roles as $role => $permissions) {
            $roleCollection[] = ['name' => $role];
        }

        return $roleCollection;
    }
}
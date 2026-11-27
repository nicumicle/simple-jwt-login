<?php

namespace SimpleJWTLogin\Repositories;

trait PaginationTrait
{
    /**
     * SQL OFFSET for a 1-based page.
     *
     * @param mixed $page
     * @param int $perPage
     * @return int
     */
    protected function pageOffset($page, $perPage)
    {
        $pageIndex = max(1, (int) $page) - 1;
        $perPage = max(1, (int) $perPage);

        // A huge page would overflow to a float, which wpdb's "%d" turns into a negative OFFSET (SQL error).
        if ($pageIndex > PHP_INT_MAX / $perPage) {
            return PHP_INT_MAX;
        }

        return $pageIndex * $perPage;
    }
}

<?php

/**
 * PitonCMS (https://github.com/PitonCMS)
 *
 * @link      https://github.com/PitonCMS/Piton
 * @copyright Copyright 2015-2026 Wolfgang Moritz
 * @license   https://github.com/PitonCMS/Piton/blob/master/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Piton\Pagination;

/**
 * Renders Page Number Links
 * @version 1.0.0
 */
class Pagination
{
    // Import main pagination code
    use PaginationTrait;

    /**
     * Print Pagination
     *
     * Render pagination links HTML
     * @return void
     */
    public function __invoke(): void
    {
        $this->buildPageLinks();
        $this->render();
    }
}

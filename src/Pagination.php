<?php

/**
 * PitonCMS (https://github.com/PitonCMS)
 *
 * @link      https://github.com/PitonCMS/Piton
 * @copyright Copyright 2015-2026 Wolfgang Moritz
 * @license   AGPL-3.0-or-later with Theme Exception. See LICENSE file for details.
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

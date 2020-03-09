<?php

/**
 * PitonCMS (https://github.com/PitonCMS)
 *
 * @link      https://github.com/PitonCMS/Piton
 * @copyright Copyright (c) 2015 - 2020 Wolfgang Moritz
 * @license   https://github.com/PitonCMS/Piton/blob/master/LICENSE (MIT License)
 */

declare(strict_types=1);

namespace Piton\Pagination;

use Exception;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Pagination Trait
 * @version 0.1.1
 */
trait PaginationTrait
{
    protected $domain;
    protected $pageUrl;
    protected $queryStringPageNumberParam;
    protected $currentPageLinkNumber;
    protected $numberOfPageLinks;
    protected $resultsPerPage;
    protected $numberOfAdjacentLinks;
    protected $totalResultsFound;
    protected $cache = [];
    protected $values = [];
    protected $paginationWrapperClass;

    /**
     * Constructor
     *
     * @param  array $config Configuration options array
     * @return void
     */
    public function __construct(array $config = null)
    {
        $this->setConfig($config ?? []);
        $this->setCurrentPageNumber();
    }

    /**
     * Set Page Path
     *
     * Submit URL with domain and path, plus any additional query string parameters as array
     * @param  string $pagePath    Path to resource, can optionally include http(s) full qualified domain, or just path
     * @param  array  $queryParams Array of query strings and values
     * @return void
     */
    public function setPagePath(string $pagePath, array $queryParams = null): void
    {
        // If the provided path is http(s), just set the link and ignore the $domain property
        if ('http' === mb_strtolower(mb_substr($pagePath, 0, 4))) {
            $this->pageUrl = $pagePath . '?';
        } else {
            $this->pageUrl = rtrim($this->domain, '/') . '/' . ltrim($pagePath, '/') . '?';
        }

        // Add any other query paramters
        if ($queryParams) {
            $this->pageUrl .= http_build_query($queryParams) . '&';
        }

        // Add pagination param at end
        $this->pageUrl .= $this->queryStringPageNumberParam . '=';
    }

    /**
     * Set Current Page Number
     *
     * Derives the current page number request
     * @param  void
     * @return void
     */
    public function setCurrentPageNumber(): void
    {
        if (isset($_GET[$this->queryStringPageNumberParam])) {
            $this->currentPageLinkNumber = (int) htmlspecialchars($_GET[$this->queryStringPageNumberParam]);
        } else {
            $this->currentPageLinkNumber = 1;
        }
    }

    /**
     * Get Current Page Number
     *
     * Gets the current page number for display in templates
     * @param void
     * @return int Page number
     */
    public function getCurrentPageNumber(): int
    {
        return $this->currentPageLinkNumber;
    }

    /**
     * Get Offset
     *
     * Returns the query offset for the current page number
     * @param  void
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->currentPageLinkNumber - 1) * $this->resultsPerPage;
    }

    /**
     * Get Limit
     *
     * Gets the query limit rows per page configuration setting
     * @param  void
     * @return int
     */
    public function getLimit(): int
    {
        return $this->resultsPerPage;
    }

    /**
     * Set Total Results Found
     *
     * Set the total results from the query
     * @param  int $totalResultsFound number of rows found
     * @return void
     */
    public function setTotalResultsFound(int $totalResultsFound): void
    {
        $this->totalResultsFound = $totalResultsFound;
    }

    /**
     * Build Pagination Links
     *
     * Build pagination links array and assigns to $this->values
     * @param  void
     * @return void
     */
    private function buildPageLinks(): void
    {
        // If buildPageLinks has already been called, just return
        if (isset($this->values['links'])) {
            return;
        }

        // Make sure we have required variables
        if (!$this->totalResultsFound) {
            throw new Exception('PitonPagination: Total rows in results not set in setTotalResultsFound()');
        }

        // Calculate the total number of pages in the result set
        $this->numberOfPageLinks = (int) ceil($this->totalResultsFound / $this->resultsPerPage);

        // Calcuate starting and ending page in the central set of links
        $startPage = ($this->currentPageLinkNumber - $this->numberOfAdjacentLinks > 0) ? $this->currentPageLinkNumber - $this->numberOfAdjacentLinks : 1;
        $endPage = ($this->currentPageLinkNumber + $this->numberOfAdjacentLinks <= $this->numberOfPageLinks) ? $this->currentPageLinkNumber + $this->numberOfAdjacentLinks : $this->numberOfPageLinks;

        //  Start with Previous link
        if ($this->currentPageLinkNumber === 1) {
            $this->values['links'][] = ['href' => $this->pageUrl . 1, 'pageNumber' => ''];
        } else {
            $this->values['links'][] = ['href' => $this->pageUrl . ($this->currentPageLinkNumber - 1), 'pageNumber' => ''];
        }

        // Always include the page one link
        if ($startPage > 1) {
            $this->values['links'][] = ['href' => $this->pageUrl . 1, 'pageNumber' => 1];
        }

        // Do we need to add ellipsis after '1' and before the link series?
        if ($startPage >= 3) {
            $this->values['links'][] = ['href' => '', 'pageNumber' => 'ellipsis'];
        }

        // Build link series
        for ($i = $startPage; $i <= $endPage; ++$i) {
            $this->values['links'][] = ['href' => $this->pageUrl . $i, 'pageNumber' => $i];
        }

        // Do we need to add ellipsis after the link series?
        if ($endPage <= $this->numberOfPageLinks - 2) {
            $this->values['links'][] = ['href' => '', 'pageNumber' => 'ellipsis'];
        }

        // Always include last page link
        if ($endPage < $this->numberOfPageLinks) {
            $this->values['links'][] = ['href' => $this->pageUrl . $this->numberOfPageLinks, 'pageNumber' => $this->numberOfPageLinks];
        }

        // And finally, the Next link
        if ($endPage === $this->numberOfPageLinks) {
            $this->values['links'][] = ['href' => $this->pageUrl . $this->numberOfPageLinks, 'pageNumber' => ''];
        } else {
            $this->values['links'][] = ['href' => $this->pageUrl . ($this->currentPageLinkNumber + 1), 'pageNumber' => ''];
        }
    }

    /**
     * Render Pagination HTML
     *
     * @param Twig\Environment|null $env Only provided by TwigPagination
     * @return string
     */
    protected function render(Environment $env = null)
    {
        // If there are no rows, or if there is only one page of results then do not display the pagination
        if ($this->totalResultsFound === 0 || $this->resultsPerPage >= $this->totalResultsFound) {
            return;
        }

        if ($env) {
            // Called from TwigPagination class
            $values['pagination']['links'] = $this->values['links'];
            $values['pagination']['currentPageLinkNumber'] = $this->currentPageLinkNumber;
            $values['pagination']['numberOfPageLinks'] = $this->numberOfPageLinks;
            $values['pagination']['pageUrl'] = $this->pageUrl;
            $values['pagination']['paginationWrapperClass'] = $this->paginationWrapperClass;

            // Add custom Twig pagination template and display
            $loader = $env->getLoader();
            $loader->setPaths(dirname(__FILE__) . '/templates/', 'pitonPagination');
            $env->display('@pitonPagination/twigPageLinks.html', $values);
        } else {
            // Called from Pagination class
            $counter = 0;
            $numberOfLinks = count($this->values['links']) - 1;
            require dirname(__FILE__) . '/templates/pageLinks.php';
        }
    }

    /**
     * Set Pagination Configuration
     *
     * @param  array|null $config Configuration array of options
     * @return void
     */
    public function setConfig(?array $config): void
    {
        $this->domain = $config['domain'] ?? '';
        $this->queryStringPageNumberParam = $config['queryStringPageNumberParam'] ?? 'page';
        $this->resultsPerPage = (int) ($config['resultsPerPage'] ?? 10);
        $this->numberOfAdjacentLinks = (int) ($config['numberOfAdjacentLinks'] ?? 2);
        $this->paginationWrapperClass = $config['paginationWrapperClass'] ?? 'piton-pagination';
    }
}

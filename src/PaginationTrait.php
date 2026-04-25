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

use Twig\Environment;

/**
 * Pagination Trait
 */
trait PaginationTrait
{
    protected string $domain = '';
    protected string $pageUrl = '';
    protected string $queryStringPageNumberParam = 'page';
    protected int $currentPageLinkNumber;
    protected int $numberOfPageLinks;
    protected int $resultsPerPage = 10;
    protected int $numberOfAdjacentLinks = 1;
    protected int $totalResultsFound = 0;
    protected array $values = [];
    protected string $paginationWrapperClass = 'pagination';
    protected string $linkBlockClass = 'page-link';
    protected string $anchorClass = 'page-anchor';
    protected string $previousText = 'Prev';
    protected string $nextText = 'Next';
    protected string $ellipsisText = '...';
    protected string $templateDirectory;
    protected string $templateFilename;

    /**
     * Constructor
     *
     * @param ?array $config Configuration options array
     */
    public function __construct(?array $config = null)
    {
        $this->setConfig($config);
        $this->setCurrentPageNumber();
        $this->setPagePath();
    }

    /**
     * Set Page Path
     *
     * Sets the base URL and carries forward any existing query params (other than
     * the page number param) into all pagination links. Accepts three forms:
     *   - null              : uses the current request path and $_GET params
     *   - full http(s) URL  : parses the supplied URL for base and query params
     *   - root-relative path: prepends the configured $domain
     * @param  ?string $pagePath Full http(s) URL, root-relative path, or null for current request
     * @return void
     */
    public function setPagePath(?string $pagePath = null): void
    {
        // Resolve pagePath depending on what was provided
        if ($pagePath === null) {
            // Default: derive base path from the current request; carry forward $_GET params
            $baseUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $params = $_GET ?? [];
        } elseif ('http' === mb_strtolower(mb_substr($pagePath, 0, 4))) {
            // Full URL supplied: parse it and extract any embedded query params
            $parsed = parse_url($pagePath);
            $baseUrl = ($parsed['scheme'] ?? 'https') . '://'
                           . ($parsed['host'] ?? '')
                           . ($parsed['path'] ?? '');
            parse_str($parsed['query'] ?? '', $params);
        } else {
            // Root-relative path: strip any accidental query string then prepend domain
            $baseUrl = rtrim($this->domain, '/') . '/'
                           . ltrim(parse_url($pagePath, PHP_URL_PATH), '/');
            $params = $_GET ?? [];
        }

        // Strip the page number param; buildHref() will append it per-link
        unset($params[$this->queryStringPageNumberParam]);

        // Build a display-friendly URL (without page param) for template use
        $queryString = http_build_query($params);
        $this->pageUrl = $baseUrl . ($queryString ? '?' . $queryString : '');
    }

    /**
     * Build Href
     *
     * Returns the complete URL with an appended pagination param and value
     * @param  int $page Target page number
     * @return string Full URL for that page
     */
    private function buildHref(int $page): string
    {
        $glue = str_contains($this->pageUrl, '?') ? '&' : '?';

        return $this->pageUrl . $glue . $this->queryStringPageNumberParam . '=' . $page;
    }

    /**
     * Set Current Page Number
     *
     * Derives the current page number from the request. Defaults to 1.
     * @return void
     */
    public function setCurrentPageNumber(): void
    {
        $this->currentPageLinkNumber = max(1, (int) trim($_GET[$this->queryStringPageNumberParam] ?? '1'));
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
     * @param void
     * @return int
     */
    public function getOffset(): int
    {
        return ($this->currentPageLinkNumber - 1) * $this->resultsPerPage;
    }

    /**
     * Get Limit
     *
     * Gets the query limit (rows per page) configuration setting
     * @param void
     * @return int
     */
    public function getLimit(): int
    {
        return $this->resultsPerPage;
    }

    /**
     * Set Total Results Found
     *
     * Set the total number of rows returned by the query
     * @param  int $totalResultsFound
     * @return void
     */
    public function setTotalResultsFound(int $totalResultsFound): void
    {
        $this->totalResultsFound = $totalResultsFound;
    }

    /**
     * Build Pagination Links
     *
     * Builds the pagination links array and assigns it to $this->values['links'].
     *
     * Link structure: always shows page 1, the last page, a central window of
     * numberOfAdjacentLinks pages either side of the current page, and prev/next
     * controls. Ellipsis placeholders appear only when a gap of two or more pages
     * exists between the fixed endpoints and the central window. Single-page gaps
     * are absorbed into the window rather than hidden behind an ellipsis.
     * @param void
     * @return void
     */
    private function buildPageLinks(): void
    {
        // Guard: only build once
        if (isset($this->values['links'])) {
            return;
        }

        $this->numberOfPageLinks = (int) ceil($this->totalResultsFound / $this->resultsPerPage);

        // Compute the central window of links around the current page
        $startPage = max(1, $this->currentPageLinkNumber - $this->numberOfAdjacentLinks);
        $endPage = min($this->numberOfPageLinks, $this->currentPageLinkNumber + $this->numberOfAdjacentLinks);

        // Absorb single-page gaps so we never show ellipsis to hide just one page.
        // If the gap between page 1 and the window start is exactly one page (page 2),
        // extend the window back to include it rather than using an ellipsis.
        if ($startPage === 3) {
            $startPage = 2;
        }

        // Likewise, if the gap between the window end and the last page is exactly
        // one page, extend the window forward to include it.
        if ($endPage === $this->numberOfPageLinks - 2) {
            $endPage = $this->numberOfPageLinks - 1;
        }

        // Previous control (stays on page 1 when already at the start)
        $this->values['links'][] = [
            'href' => $this->buildHref(max(1, $this->currentPageLinkNumber - 1)),
            'pageNumber' => '',
        ];

        // Always show page 1 when the window does not start there
        if ($startPage > 1) {
            $this->values['links'][] = ['href' => $this->buildHref(1), 'pageNumber' => 1];
        }

        // Leading ellipsis: only when two or more pages are hidden before the window
        // (startPage === 3 is impossible here due to the absorption above, so this
        // fires only when startPage >= 4)
        if ($startPage > 2) {
            $this->values['links'][] = ['href' => '', 'pageNumber' => 'ellipsis'];
        }

        // Central link series
        for ($i = $startPage; $i <= $endPage; ++$i) {
            $this->values['links'][] = ['href' => $this->buildHref($i), 'pageNumber' => $i];
        }

        // Trailing ellipsis: only when two or more pages are hidden after the window
        // (endPage === numberOfPageLinks - 2 is impossible here due to absorption)
        if ($endPage < $this->numberOfPageLinks - 1) {
            $this->values['links'][] = ['href' => '', 'pageNumber' => 'ellipsis'];
        }

        // Always show the last page when the window does not reach it
        if ($endPage < $this->numberOfPageLinks) {
            $this->values['links'][] = [
                'href' => $this->buildHref($this->numberOfPageLinks),
                'pageNumber' => $this->numberOfPageLinks,
            ];
        }

        // Next control (stays on the last page when already at the end)
        $this->values['links'][] = [
            'href' => $this->buildHref(min($this->numberOfPageLinks, $this->currentPageLinkNumber + 1)),
            'pageNumber' => '',
        ];
    }

    /**
     * Render Pagination HTML
     *
     * @param  Environment $env Twig environment (provided by TwigPagination)
     * @return void
     */
    protected function render(Environment $env): void
    {
        // Do not render if there is only one page (or no results)
        if ($this->totalResultsFound <= $this->resultsPerPage) {
            return;
        }

        // Ensure links are built (idempotent if already called by the consumer)
        $this->buildPageLinks();

        $values['pagination']['links'] = $this->values['links'];
        $values['pagination']['currentPageLinkNumber'] = $this->currentPageLinkNumber;
        $values['pagination']['numberOfPageLinks'] = $this->numberOfPageLinks;
        $values['pagination']['pageUrl'] = $this->pageUrl;
        $values['pagination']['paginationWrapperClass'] = $this->paginationWrapperClass;
        $values['pagination']['anchorClass'] = $this->anchorClass;
        $values['pagination']['previousText'] = $this->previousText;
        $values['pagination']['nextText'] = $this->nextText;
        $values['pagination']['ellipsisText'] = $this->ellipsisText;
        $values['pagination']['linkBlockClass'] = $this->linkBlockClass;

        $loader = $env->getLoader();
        $loader->setPaths($this->templateDirectory, 'pitonPagination');
        $env->display('@pitonPagination/' . $this->templateFilename, $values);
    }

    /**
     * Set Pagination Configuration
     *
     * @param ?array $config Configuration array of options
     * @return void
     */
    public function setConfig(?array $config): void
    {
        // Optional fully qualified domain
        if (isset($config['domain'])) {
            $this->domain = $config['domain'];
        }

        // Query string param name for the page number
        if (isset($config['queryStringPageNumberParam'])) {
            $this->queryStringPageNumberParam = $config['queryStringPageNumberParam'];
        }

        // Number of results to display per page
        if (isset($config['resultsPerPage']) && is_numeric($config['resultsPerPage'])) {
            $this->resultsPerPage = (int) $config['resultsPerPage'];
        }

        // Number of adjacent links either side of the current page in the central window
        if (isset($config['numberOfAdjacentLinks']) && is_numeric($config['numberOfAdjacentLinks'])) {
            $this->numberOfAdjacentLinks = (int) $config['numberOfAdjacentLinks'];
        }

        // Total number of results found by the query
        if (isset($config['totalResultsFound'])) {
            $this->setTotalResultsFound($config['totalResultsFound']);
        }

        // Class applied to the pagination container element
        if (isset($config['paginationWrapperClass'])) {
            $this->paginationWrapperClass = $config['paginationWrapperClass'];
        }

        // Class applied to each page link block element
        if (isset($config['linkBlockClass'])) {
            $this->linkBlockClass = $config['linkBlockClass'];
        }

        // Class applied to each anchor element
        if (isset($config['anchorClass'])) {
            $this->anchorClass = $config['anchorClass'];
        }

        // Text or symbol for the Previous control
        if (isset($config['previousText'])) {
            $this->previousText = $config['previousText'];
        }

        // Text or symbol for the Next control
        if (isset($config['nextText'])) {
            $this->nextText = $config['nextText'];
        }

        // Text or symbol for ellipsis placeholders
        if (isset($config['ellipsisText'])) {
            $this->ellipsisText = $config['ellipsisText'];
        }

        // Path to the directory containing Twig pagination templates (with trailing slash)
        if (isset($config['templateDirectory'])) {
            $this->templateDirectory = $config['templateDirectory'];
        } else {
            $this->templateDirectory = __DIR__ . '/templates/';
        }

        // Template filename including extension
        if (isset($config['templateFilename'])) {
            $this->templateFilename = $config['templateFilename'];
        } else {
            $this->templateFilename = 'twigPageLinks.html';
        }
    }
}

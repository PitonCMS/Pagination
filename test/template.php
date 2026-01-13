<?php

/**
 * PitonCMS (https://github.com/PitonCMS)
 *
 * @link      https://github.com/PitonCMS/Piton
 * @copyright Copyright 2015 Wolfgang Moritz
 * @license   AGPL-3.0-or-later with Theme Exception. See LICENSE file for details.
 */

declare(strict_types=1);

/**
 * Test Render Template
 */

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PitonPagination Test</title>
</head>
<body >
    <h1>PitonPagination Test</h1>
    <h3>Page <?php echo isset($currentPage) ? $currentPage : 'None';  ?></h3>

    <ul>
    <?php foreach ($currentPageSet as $key => $value): ?>
        <li>Page Key: <?php echo $key; ?></li>
    <?php endforeach; ?>
    </ul>

    <div><?php echo isset($pagination) ? $pagination() : 'No Pagination Found';  ?></div>
</body>
</html>
<?php

/**
 * PitonCMS (https://github.com/PitonCMS)
 *
 * @link      https://github.com/PitonCMS/Piton
 * @copyright Copyright (c) 2015 - 2020 Wolfgang Moritz
 * @license   https://github.com/PitonCMS/Piton/blob/master/LICENSE (MIT License)
 */

declare(strict_types=1);

/**
 * PHP HTML Pagination Template
 */
?>

  <div class="<?php echo $this->paginationWrapperClass; ?>">
    <?php foreach ($this->values['links'] as $link): ?>
      <?php if ($counter === 0): /* First pagination position */ ?>
        <div class="control <?php if ($this->currentPageLinkNumber === 1): ?>disabled<?php endif; ?>">
          <a href="<?php echo $link['href']; ?>" aria-label="Previous" title="Previous">&laquo;</a>
        </div>
      <?php endif; ?>

      <?php if ($link['pageNumber'] === 'ellipsis'): /* Print ellipsis as gap filler */ ?>
        <div class="ellipsis">&hellip;</div>
      <?php endif; ?>

      <?php if ($counter !== 0 && $counter !== $numberOfLinks && $link['pageNumber'] !== 'ellipsis'): /* For all other working links */ ?>
        <div <?php if ($this->currentPageLinkNumber === $link['pageNumber']): ?>class="active"<?php endif; ?>>
          <a href="<?php echo $link['href']; ?>"><?php echo $link['pageNumber']; ?></a>
        </div>
      <?php endif; ?>

      <?php if ($counter === $numberOfLinks): /* Last pagination position */ ?>
        <div class="control <?php if ($this->currentPageLinkNumber === $this->numberOfPageLinks): ?>disabled<?php endif; ?>">
          <a href="<?php echo $link['href']; ?>" aria-label="Next" title="Next" >&raquo;</a>
        </div>
      <?php endif; ?>
      <?php $counter++; ?>
    <?php endforeach; ?>
  </div>

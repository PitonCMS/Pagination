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
        <div <?php if ($this->currentPageLinkNumber === 1): /* Disable first link when  on first page */ ?>class="disabled"<?php endif; ?>>
          <?php if ($this->currentPageLinkNumber === 1): ?>
            <span aria-hidden="true">&laquo;</span>
          <?php else: /* Start with the "Previous" link on first loop when currently not on pageNumber 1 */ ?>
            <a href="<?php echo $link['href']; ?>" aria-label="Previous" title="Previous"><span aria-hidden="true">&laquo;</span></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($link['pageNumber'] === 'ellipsis'): /* Print ellipsis as gap filler */ ?>
        <div class="disabled">&hellip;</div>
      <?php endif; ?>

      <?php if ($counter !== 0 && $counter !== $numberOfLinks && $link['pageNumber'] !== 'ellipsis'): /* For all other working links */ ?>
        <div <?php if ($this->currentPageLinkNumber === $link['pageNumber']): ?>class="active"<?php endif; ?>>
          <?php if ($this->currentPageLinkNumber === $link['pageNumber']): /* No need to make the current page a link */ ?>
            <a><?php echo $link['pageNumber']; ?></a>
          <?php else: ?>
            <a href="<?php echo $link['href']; ?>"><?php echo $link['pageNumber']; ?></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <?php if ($counter === $numberOfLinks): /* Last pagination position */ ?>
        <div <?php if ($this->currentPageLinkNumber === $this->numberOfPageLinks): /* Disable last link if on the last page */ ?>class="disabled"<?php endif; ?>>
          <?php if ($this->currentPageLinkNumber === $this->numberOfPageLinks):  ?>
            <span aria-hidden="true">&raquo;</span>
          <?php else: /* End with the "Next" pageNumber */ ?>
            <a href="<?php echo $link['href']; ?>" aria-label="Next" title="Next" ><span aria-hidden="true">&raquo;</span></a>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      <?php $counter++; ?>
    <?php endforeach; ?>
  </div>

<?php
namespace TYPO3\CMS\Backend\Preview;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\View\PageLayoutView;

/**
 * Interface PreviewRendererResolverInterface
 *
 * Contract for classes capable of resolving PreviewRenderInterface
 * implementations based on table and record.
 */
interface PreviewRendererResolverInterface
{
    /**
     * @param string $table
     * @param array $row
     * @return PreviewRendererInterface
     */
    public function resolveRendererFor($table, array $row);
}

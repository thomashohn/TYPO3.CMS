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
 * Interface PreviewRendererInterface
 *
 * Conctract for classes capable of rendering previews of a given record
 * from a table. Responsible for rendering preview heeader, preview content
 * and wrapping of those two values.
 *
 * Responsibilities are segmented into three methods, one for each responsibility,
 * which is done in order to allow overriding classes to change those parts
 * individually without having to replace other parts. Rather than relying on
 * implementations to be friendly and divide code into smaller pieces and
 * give them (at least) protected visibility, the key methods are instead required
 * on the interface directly.
 *
 * Callers are then responsible for calling each method and combining/wrapping
 * the output appropriately.
 */
interface PreviewRendererInterface
{
    /**
     * Dedicated method for rendering preview header HTML for
     * the page module only. Receives the record (always a
     * tt_content record) and caller instance of PageLayoutView.
     *
     * @param array $record
     * @return string
     */
    public function renderPageModulePreviewHeader(array $record, PageLayoutView $pageLayoutView);

    /**
     * Dedicated method for rendering preview body HTML for
     * the page module only. Receives the record (always a
     * tt_content record) and caller instance of PageLayoutView.
     *
     * @param array $record
     * @return string
     */
    public function renderPageModulePreviewContent(array $record, PageLayoutView $pageLayoutView);

    /**
     * Dedicated method for wrapping a preview header and body
     * HTML (received concatenated as $preview). Receives $record
     * which can be used to determine appropriate wrapping and
     * caller instance of PageLayoutView if any utility methods on
     * this (mutable) instance is required by the implementation.
     *
     * @param string $preview
     * @param array $record
     * @return string
     */
    public function wrapPageModulePreview($preview, array $record, PageLayoutView $pageLayoutView);
}

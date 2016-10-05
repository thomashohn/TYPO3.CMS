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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\FlexFormService;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class StandardContentPreviewRenderer
 *
 * Legacy preview rendering refactored from PageLayoutView.
 * Provided as default preview rendering mechanism via
 * StandardPreviewRendererResolver which detects the renderer
 * based on TCA configuration.
 *
 * Can be replaced and/or subclassed by custom implementations
 * by changing this TCA configuration.
 *
 * See also PreviewRendererInterface documentation.
 */
class StandardContentPreviewRenderer implements PreviewRendererInterface
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * StandardContentPreviewRenderer constructor.
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Legacy method: renders a preview header for the page module and consults
     * any configured hooks
     *
     * @param array $record
     * @param PageLayoutView $pageLayoutView
     * @return string
     * @throws \UnexpectedValueException
     */
    public function renderPageModulePreviewHeader(array $record, PageLayoutView $pageLayoutView)
    {
        // Make header:
        $outHeader = '';
        if ($record['header']) {
            $infoArr = [];
            $pageLayoutView->getProcessedValue('tt_content', 'header_position,header_layout,header_link', $record, $infoArr);
            $hiddenHeaderNote = '';
            // If header layout is set to 'hidden', display an accordant note:
            if ($record['header_layout'] == 100) {
                $hiddenHeaderNote = ' <em>[' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.hidden')) . ']</em>';
            }
            $outHeader = $record['date']
                ? htmlspecialchars($pageLayoutView->itemLabels['date'] . ' ' . BackendUtility::date($record['date'])) . '<br />'
                : '';
            $outHeader .= '<strong>' . $pageLayoutView->linkEditContent($pageLayoutView->renderText($record['header']), $record)
                . $hiddenHeaderNote . '</strong><br />';
        }

        return $outHeader;
    }

    /**
     * @param array $record
     * @param PageLayoutView $pageLayoutView
     * @return string
     */
    public function renderPageModulePreviewContent(array $record, PageLayoutView $pageLayoutView)
    {

        // Check if a Fluid-based preview template was defined for this CType
        // and render it via Fluid. Possible option:
        // mod.web_layout.tt_content.preview.media = EXT:site_mysite/Resources/Private/Templates/Preview/Media.html
        $infoArr = [];
        $pageLayoutView->getProcessedValue('tt_content', 'header_position,header_layout,header_link', $record, $infoArr);
        $tsConfig = BackendUtility::getModTSconfig($record['pid'], 'mod.web_layout.tt_content.preview');
        if (!empty($tsConfig['properties'][$record['CType']])) {
            $fluidTemplateFile = $tsConfig['properties'][$record['CType']];
            $fluidTemplateFile = GeneralUtility::getFileAbsFileName($fluidTemplateFile);
            if ($fluidTemplateFile) {
                try {
                    /** @var StandaloneView $view */
                    $view = GeneralUtility::makeInstance(StandaloneView::class);
                    $view->setTemplatePathAndFilename($fluidTemplateFile);
                    $view->assignMultiple($record);
                    if (!empty($record['pi_flexform'])) {
                        /** @var FlexFormService $flexFormService */
                        $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
                        $view->assign('pi_flexform_transformed', $flexFormService->convertFlexFormContentToArray($record['pi_flexform']));
                    }
                    return $view->render();
                } catch (\Exception $e) {
                    // Catch any exception to avoid breaking the view
                }
            }
        }

        // Draw preview of the item depending on its CType
        switch ($record['CType']) {
            case 'header':
                if ($record['subheader']) {
                    $out .= $pageLayoutView->linkEditContent($pageLayoutView->renderText($record['subheader']), $record) . '<br />';
                }
                break;
            case 'bullets':
            case 'table':
                if ($record['bodytext']) {
                    $out .= $pageLayoutView->linkEditContent($pageLayoutView->renderText($record['bodytext']), $record) . '<br />';
                }
                break;
            case 'uploads':
                if ($record['media']) {
                    $out .= $pageLayoutView->linkEditContent($pageLayoutView->getThumbCodeUnlinked($record, 'tt_content', 'media'), $record) . '<br />';
                }
                break;
            case 'menu':
                $contentType = $pageLayoutView->CType_labels[$record['CType']];
                $out .= $pageLayoutView->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $record) . '<br />';
                // Add Menu Type
                $menuTypeLabel = $this->getLanguageService()->sL(
                    BackendUtility::getLabelFromItemListMerged($record['pid'], 'tt_content', 'menu_type', $record['menu_type'])
                );
                $menuTypeLabel = $menuTypeLabel ?: 'invalid menu type';
                $out .= $pageLayoutView->linkEditContent($menuTypeLabel, $record);
                if ($record['menu_type'] !== '2' && ($record['pages'] || $record['selected_categories'])) {
                    // Show pages if menu type is not "Sitemap"
                    $out .= ':' . $pageLayoutView->linkEditContent($this->generateListForCTypeMenu($record), $record) . '<br />';
                }
                break;
            case 'shortcut':
                if (!empty($record['records'])) {
                    $shortcutContent = [];
                    $recordList = explode(',', $record['records']);
                    foreach ($recordList as $recordIdentifier) {
                        $split = BackendUtility::splitTable_Uid($recordIdentifier);
                        $tableName = empty($split[0]) ? 'tt_content' : $split[0];
                        $shortcutRecord = BackendUtility::getRecord($tableName, $split[1]);
                        if (is_array($shortcutRecord)) {
                            $icon = $this->iconFactory->getIconForRecord($tableName, $shortcutRecord, Icon::SIZE_SMALL)->render();
                            $icon = BackendUtility::wrapClickMenuOnIcon(
                                $icon,
                                $tableName,
                                $shortcutRecord['uid'],
                                1,
                                '',
                                '+copy,info,edit,view'
                            );
                            $shortcutContent[] = $icon
                                . htmlspecialchars(BackendUtility::getRecordTitle($tableName, $shortcutRecord));
                        }
                    }
                    $out .= implode('<br />', $shortcutContent) . '<br />';
                }
                break;
            case 'list':
                $hookArr = [];
                $hookOut = '';
                if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$record['list_type']])) {
                    $hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info'][$record['list_type']];
                } elseif (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'])) {
                    $hookArr = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['list_type_Info']['_DEFAULT'];
                }
                if (!empty($hookArr)) {
                    $_params = ['pObj' => &$pageLayoutView, 'record' => $record, 'infoArr' => $infoArr];
                    foreach ($hookArr as $_funcRef) {
                        $hookOut .= GeneralUtility::callUserFunction($_funcRef, $_params, $pageLayoutView);
                    }
                }
                if ((string)$hookOut !== '') {
                    $out .= $hookOut;
                } elseif (!empty($record['list_type'])) {
                    $label = BackendUtility::getLabelFromItemListMerged($record['pid'], 'tt_content', 'list_type', $record['list_type']);
                    if (!empty($label)) {
                        $out .= $pageLayoutView->linkEditContent('<strong>' . htmlspecialchars($this->getLanguageService()->sL($label)) . '</strong>', $record) . '<br />';
                    } else {
                        $message = sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'), $record['list_type']);
                        $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                    }
                } elseif (!empty($record['select_key'])) {
                    $out .= htmlspecialchars($this->getLanguageService()->sL(BackendUtility::getItemLabel('tt_content', 'select_key')))
                        . ' ' . htmlspecialchars($record['select_key']) . '<br />';
                } else {
                    $out .= '<strong>' . $this->getLanguageService()->getLL('noPluginSelected') . '</strong>';
                }
                $out .= htmlspecialchars($this->getLanguageService()->sL(
                        BackendUtility::getLabelFromItemlist('tt_content', 'pages', $record['pages'])
                    )) . '<br />';
                break;
            default:
                $contentType = $pageLayoutView->CType_labels[$record['CType']];

                if (isset($contentType)) {
                    $out .= $pageLayoutView->linkEditContent('<strong>' . htmlspecialchars($contentType) . '</strong>', $record) . '<br />';
                    if ($record['bodytext']) {
                        $out .= $pageLayoutView->linkEditContent($pageLayoutView->renderText($record['bodytext']), $record) . '<br />';
                    }
                    if ($record['image']) {
                        $out .= $pageLayoutView->linkEditContent($pageLayoutView->getThumbCodeUnlinked($record, 'tt_content', 'image'), $record) . '<br />';
                    }
                } else {
                    $message = sprintf(
                        $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.noMatchingValue'),
                        $record['CType']
                    );
                    $out .= '<span class="label label-warning">' . htmlspecialchars($message) . '</span>';
                }
        }

        return $out;
    }

    /**
     * @param string $preview
     * @param array $record
     * @param PageLayoutView $pageLayoutView
     * @return string
     */
    public function wrapPageModulePreview($preview, array $record, PageLayoutView $pageLayoutView)
    {
        // Wrap span-tags:
        $out = '
			<span class="exampleContent">' . $preview . '</span>';
        // Return values:
        if ($pageLayoutView->isDisabled('tt_content', $record)) {
            return '<span class="text-muted">' . $out . '</span>';
        } else {
            return $out;
        }
    }

    /**
     * Generates a list of selected pages or categories for the CType menu
     *
     * @param array $record row from pages
     * @return string
     */
    protected function generateListForCTypeMenu(array $record)
    {
        $table = 'pages';
        $field = 'pages';
        // get categories instead of pages
        if (strpos($record['menu_type'], 'categorized_') !== false) {
            $table = 'sys_category';
            $field = 'selected_categories';
        }
        if (trim($record[$field]) === '') {
            return '';
        }
        $content = '';
        $uidList = explode(',', $record[$field]);
        foreach ($uidList as $uid) {
            $uid = (int)$uid;
            $pageRecord = BackendUtility::getRecord($table, $uid, 'title');
            $content .= '<br>' . $pageRecord['title'] . ' (' . $uid . ')';
        }
        return $content;
    }

    /**
     * Returns the language service
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}

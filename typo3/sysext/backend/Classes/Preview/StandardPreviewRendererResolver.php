<?php
namespace TYPO3\CMS\Backend\Preview;

/**
 * Class StandardPreviewRendererResolver
 *
 * Default implementation of PreviewRendererResolverInterface.
 * Scans TCA configuration to detect:
 *
 * - TCA.$table.types.$typeFromTypeField.previewRenderer
 * - TCA.$table.ctrl.previewRenderer
 *
 * Depending on which one is defined and checking the first, type-specific
 * variant first.
 */
class StandardPreviewRendererResolver implements PreviewRendererResolverInterface
{
    /**
     * @param string $table
     * @param array $row
     * @return PreviewRendererInterface
     */
    public function resolveRendererFor($table, array $row)
    {
        $tca = $GLOBALS['TCA'][$table];
        $typeConfiguration = empty($tca['types'][$tca['type']]) ? : $tca['types'][$tca['type']];
        $previewRendererClassName = null;
        if (empty($tca['type']) || !empty($typeConfiguration['previewRenderer'])) {
            // Table either has no type field or no custom preview renderer was defined for the type.
            // Use table's standard renderer if any is defined.
            $previewRendererClassName = $tca['ctrl']['previewRenderer'];
        } elseif (!empty($tca['types'][$tca['type']]['previewRenderer'])) {
            $previewRendererClassName = $tca['types'][$tca['type']]['previewRenderer'];
        }
        if (!empty($previewRendererClassName)) {
            if (!is_a($previewRendererClassName, PreviewRendererInterface::class, true)) {
                throw new \UnexpectedValueException(
                    sprintf(
                        'Class %s must implement %s',
                        $previewRendererClassName,
                        PreviewRendererInterface::class
                    )
                );
            }
            return new $previewRendererClassName;
        }
        throw new \RuntimeException(sprintf('No Preview renderer registered for table %s', $table));
    }

}

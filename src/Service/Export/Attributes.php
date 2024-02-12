<?php
namespace Service\Export;

/**
 * Class Attributes
 */
class Attributes
{
    /**
     * @var string[]
     */
    public static $styles = [
        '-block-var',
        '-block-edit',
        '-block-region',
        '-block-section',
        '-block-component',
        '-block-repeat',
        '-block-remove',
        '-block-bold',
        '-block-italic',
        '-block-link',
        '-block-text',
        '-block-background',
        '-block-minchar',
        '-block-maxchar',
        '-style-var',
        '-group-var',
    ];

    /**
     * @var string[]
     */
    public static $classes = [
        'block-edit',
        'block-wrapper',
        'block-region',
        'block-section',
        'block-component',
        'block-repeat',
        'block-remove',
        'block-bold',
        'block-no-bold',
        'block-italic',
        'block-no-italic',
        'block-link',
        'block-no-link',
        'block-text',
        'block-resize',
        'block-image',
        'block-no-superscript',
        'block-no-subscript',
        'block-no-text',
        'block-background',
        'block-bgcolor',
        'block-section-keep',
        'block-section-empty',
        'block-section-spacer',
        'block-section-empty-editing',
        'block-section-placeholder',
    ];

    /**
     * @var string[]
     */
    public static $variables = [
        'block-minchar-',
        'block-maxchar-',
    ];

    /**
     * @var string[]
     */
    public static $datas = [
        'data-id',
        'data-style',
        'data-block',
        'data-group',
        'data-repeat-id',
        'data-title',
        'data-be-id',
        'data-be-keep',
        'data-be-img-width',
        'data-be-img-height',
        'data-be-style-orig',
        'data-be-style-index',
        'data-be-style-default',
        'data-be-variation-index',
        'data-be-droppable',
        'data-be-hosted',
        'data-be-attr-snapshot',
        'data-be-custom-src',
        'data-be-img-id',
        'data-be-section-id',
        'data-be-component-id',
        'data-be-anchor',
        'orig-style',
        'original',
        'original-bg',
        'original-bg-link',
    ];
}

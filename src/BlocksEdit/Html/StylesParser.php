<?php
namespace BlocksEdit\Html;

use Sabberworm\CSS\CSSList\Document;
use Sabberworm\CSS\Parser;
use Sabberworm\CSS\Parsing\SourceException;
use Sabberworm\CSS\RuleSet\DeclarationBlock;
use simplehtmldom_1_5\simple_html_dom;

/**
 * Class StylesParser
 */
class StylesParser
{
    /**
     * @param simple_html_dom $dom
     *
     * @return bool
     * @throws SourceException
     */
    public function inlineStylesheetBEStyles(simple_html_dom $dom): bool
    {
        $toInline = [];
        $styles = $dom->find('style');
        foreach($styles as $style) {
            /** @var Document $doc */
            /** @var DeclarationBlock $ruleSet */
            $parser = new Parser($style->innertext());
            $doc    = $parser->parse();

            foreach($doc->getAllRuleSets() as $ruleSet) {
                foreach($ruleSet->getRules() as $rule) {
                    if (stripos($rule->getRule(), '-block-') === 0) {
                        $selectors = [];
                        /** @phpstan-ignore-next-line */
                        foreach($ruleSet->getSelectors() as $selector) {
                            $selectors[] = $selector->getSelector();
                        }
                        $toInline[] = [
                            'selectors' => $selectors,
                            'property'  => $rule->getRule(),
                            'value'     => $rule->getValue()
                        ];
                    }
                }
            }
        }

        foreach($toInline as $rules) {
            foreach($rules['selectors'] as $selector) {
                $elements = $dom->find($selector);
                foreach($elements as $element) {
                    if (in_array($rules['property'], ['-block-section', '-block-component', '-block-edit', '-block-region'])) {
                        $property  = substr($rules['property'], 1);
                        $className = $element->getAttribute('class');
                        if (!$className) {
                            $className = $property;
                        } else {
                            $className = "$className $property";
                        }
                        $element->setAttribute('class', $className);
                    } else {
                        $style = $element->getAttribute('style');
                        if (!$style) {
                            $style = "$rules[property]: $rules[value];";
                        } else {
                            $style = "$style; $rules[property]: $rules[value];";
                        }
                        $element->setAttribute('style', $style);
                    }
                }
            }
        }

        return true;
    }
}

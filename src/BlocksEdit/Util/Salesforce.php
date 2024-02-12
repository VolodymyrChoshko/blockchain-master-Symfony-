<?php
namespace BlocksEdit\Util;

/**
 * Class Salesforce
 */
class Salesforce
{
    /**
     * @param string $html
     *
     * @return string
     */
    public function hideAmpScript($html)
    {
        // return $html;
        $html = preg_replace(
            '/<!--\s*block-hide\s*-->(.*?)<!--\s*end-block-hide\s*-->/ms',
            '<!-- block-hide\\1end-block-hide -->',
            $html
        );

        return $html;
    }

    /**
     * @param string $html
     *
     * @return string
     */
    public function restoreAmpScript($html)
    {
        $html = preg_replace('/<!--\s*block-hide\s*-->(.*?)<!--\s*end-block-hide\s*-->/ms', '\\1', $html);

        return $html;
    }
}

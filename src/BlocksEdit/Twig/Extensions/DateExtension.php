<?php
namespace BlocksEdit\Twig\Extensions;

use BlocksEdit\Twig\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class DateExtension
 */
class DateExtension extends AbstractExtension
{
    /**
     * @return array|TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('strtotime', 'strtotime'),
            new TwigFilter('toShortMonth', [$this, 'toShortMonth']),
            new TwigFilter('toLongMonth', [$this, 'toLongMonth']),
            new TwigFilter('ordinal', [$this, 'ordinal'])
        ];
    }

    /**
     * @param int $n
     *
     * @return string
     */
    public function toShortMonth(int $n): string
    {
        switch ($n) {
            case 1:
                return 'Jan';
            case 2:
                return 'Feb';
            case 3:
                return 'Mar';
            case 4:
                return 'Apr';
            case 5:
                return 'May';
            case 6:
                return 'Jun';
            case 7:
                return 'Jul';
            case 8:
                return 'Aug';
            case 9:
                return 'Sep';
            case 10:
                return 'Oct';
            case 11:
                return 'Nov';
            case 12:
                return 'Dec';
            default:
                return '';
        }
    }

    /**
     * @param int $n
     *
     * @return string
     */
    public function toLongMonth(int $n): string
    {
        switch ($n) {
            case 1:
                return 'January';
            case 2:
                return 'February';
            case 3:
                return 'March';
            case 4:
                return 'April';
            case 5:
                return 'May';
            case 6:
                return 'June';
            case 7:
                return 'July';
            case 8:
                return 'August';
            case 9:
                return 'September';
            case 10:
                return 'October';
            case 11:
                return 'November';
            case 12:
                return 'December';
            default:
                return '';
        }
    }

    /**
     * @param int $num
     *
     * @return string
     */
    public function ordinal(int $num): string
    {
        $ends = array('th','st','nd','rd','th','th','th','th','th','th');
        if ((($num % 100) >= 11) && (($num % 100) <= 13)) {
            return $num . 'th';
        } else {
            return $num . $ends[$num % 10];
        }
    }
}

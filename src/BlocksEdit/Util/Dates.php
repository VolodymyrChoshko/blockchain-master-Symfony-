<?php
namespace BlocksEdit\Util;

/**
 * Class Dates
 */
class Dates
{
    /**
     * @param string $timezone
     *
     * @return bool
     */
    public static function isValidTimezone(string $timezone): bool
    {
        $timezones = array_values(self::getListOfTimezones());

        return in_array($timezone, $timezones);
    }

    /**
     * @return string[]
     */
    public static function getListOfTimezones(): array
    {
        return [
            '(GMT-11:00) Midway Island'                   => 'Pacific/Midway',
            '(GMT-11:00) Samoa'                           => 'Pacific/Samoa',
            '(GMT-10:00) Hawaii'                          => 'Pacific/Honolulu',
            '(GMT-09:00) Alaska'                          => 'America/Anchorage',
            '(GMT-08:00) Pacific Time (US & Canada)'      => 'America/Los_Angeles',
            '(GMT-08:00) Tijuana'                         => 'America/Tijuana',
            '(GMT-07:00) Arizona'                         => 'America/Phoenix',
            '(GMT-07:00) Chihuahua'                       => 'America/Chihuahua',
            '(GMT-07:00) Mazatlan'                        => 'America/Mazatlan',
            '(GMT-07:00) Mountain Time (US & Canada)'     => 'America/Denver',
            '(GMT-06:00) Central America'                 => 'America/Managua',
            '(GMT-06:00) Central Time (US & Canada)'      => 'America/Chicago',
            '(GMT-06:00) Mexico City'                     => 'America/Mexico_City',
            '(GMT-06:00) Monterrey'                       => 'America/Monterrey',
            '(GMT-06:00) Saskatchewan'                    => 'Canada/Saskatchewan',
            '(GMT-05:00) Bogota'                          => 'America/Bogota',
            '(GMT-05:00) Eastern Time (US & Canada)'      => 'America/New_York',
            '(GMT-05:00) Indiana (East)'                  => 'US/East-Indiana',
            '(GMT-05:00) Lima'                            => 'America/Lima',
            '(GMT-04:00) Atlantic Time (Canada)'          => 'Canada/Atlantic',
            '(GMT-04:30) Caracas'                         => 'America/Caracas',
            '(GMT-04:00) La Paz'                          => 'America/La_Paz',
            '(GMT-04:00) Santiago'                        => 'America/Santiago',
            '(GMT-03:30) Newfoundland'                    => 'Canada/Newfoundland',
            '(GMT-03:00) Brasilia'                        => 'America/Sao_Paulo',
            '(GMT-03:00) Buenos Aires'                    => 'America/Argentina/Buenos_Aires',
            '(GMT-03:00) Greenland'                       => 'America/Godthab',
            '(GMT-02:00) Mid-Atlantic'                    => 'America/Noronha',
            '(GMT-01:00) Azores'                          => 'Atlantic/Azores',
            '(GMT-01:00) Cape Verde Is.'                  => 'Atlantic/Cape_Verde',
            '(GMT+00:00) Casablanca'                      => 'Africa/Casablanca',
            '(GMT+00:00) Greenwich Mean Time : Dublin'    => 'Etc/Greenwich',
            '(GMT+00:00) Lisbon'                          => 'Europe/Lisbon',
            '(GMT+00:00) London'                          => 'Europe/London',
            '(GMT+00:00) Monrovia'                        => 'Africa/Monrovia',
            '(GMT+00:00) UTC'                             => 'UTC',
            '(GMT+01:00) Amsterdam'                       => 'Europe/Amsterdam',
            '(GMT+01:00) Belgrade'                        => 'Europe/Belgrade',
            '(GMT+01:00) Berlin'                          => 'Europe/Berlin',
            '(GMT+01:00) Bratislava'                      => 'Europe/Bratislava',
            '(GMT+01:00) Brussels'                        => 'Europe/Brussels',
            '(GMT+01:00) Budapest'                        => 'Europe/Budapest',
            '(GMT+01:00) Copenhagen'                      => 'Europe/Copenhagen',
            '(GMT+01:00) Ljubljana'                       => 'Europe/Ljubljana',
            '(GMT+01:00) Madrid'                          => 'Europe/Madrid',
            '(GMT+01:00) Paris'                           => 'Europe/Paris',
            '(GMT+01:00) Prague'                          => 'Europe/Prague',
            '(GMT+01:00) Rome'                            => 'Europe/Rome',
            '(GMT+01:00) Sarajevo'                        => 'Europe/Sarajevo',
            '(GMT+01:00) Skopje'                          => 'Europe/Skopje',
            '(GMT+01:00) Stockholm'                       => 'Europe/Stockholm',
            '(GMT+01:00) Vienna'                          => 'Europe/Vienna',
            '(GMT+01:00) Warsaw'                          => 'Europe/Warsaw',
            '(GMT+01:00) West Central Africa'             => 'Africa/Lagos',
            '(GMT+01:00) Zagreb'                          => 'Europe/Zagreb',
            '(GMT+02:00) Athens'                          => 'Europe/Athens',
            '(GMT+02:00) Bucharest'                       => 'Europe/Bucharest',
            '(GMT+02:00) Cairo'                           => 'Africa/Cairo',
            '(GMT+02:00) Harare'                          => 'Africa/Harare',
            '(GMT+02:00) Helsinki'                        => 'Europe/Helsinki',
            '(GMT+02:00) Istanbul'                        => 'Europe/Istanbul',
            '(GMT+02:00) Jerusalem'                       => 'Asia/Jerusalem',
            '(GMT+02:00) Pretoria'                        => 'Africa/Johannesburg',
            '(GMT+02:00) Riga'                            => 'Europe/Riga',
            '(GMT+02:00) Sofia'                           => 'Europe/Sofia',
            '(GMT+02:00) Tallinn'                         => 'Europe/Tallinn',
            '(GMT+02:00) Vilnius'                         => 'Europe/Vilnius',
            '(GMT+03:00) Baghdad'                         => 'Asia/Baghdad',
            '(GMT+03:00) Kuwait'                          => 'Asia/Kuwait',
            '(GMT+03:00) Minsk'                           => 'Europe/Minsk',
            '(GMT+03:00) Nairobi'                         => 'Africa/Nairobi',
            '(GMT+03:00) Riyadh'                          => 'Asia/Riyadh',
            '(GMT+03:00) Volgograd'                       => 'Europe/Volgograd',
            '(GMT+03:30) Tehran'                          => 'Asia/Tehran',
            '(GMT+04:00) Abu Dhabi'                       => 'Asia/Muscat',
            '(GMT+04:00) Baku'                            => 'Asia/Baku',
            '(GMT+04:00) Moscow'                          => 'Europe/Moscow',
            '(GMT+04:00) Tbilisi'                         => 'Asia/Tbilisi',
            '(GMT+04:00) Yerevan'                         => 'Asia/Yerevan',
            '(GMT+04:30) Kabul'                           => 'Asia/Kabul',
            '(GMT+05:00) Karachi'                         => 'Asia/Karachi',
            '(GMT+05:00) Tashkent'                        => 'Asia/Tashkent',
            '(GMT+05:30) Kolkata'                         => 'Asia/Kolkata',
            '(GMT+05:30) Mumbai'                          => 'Asia/Calcutta',
            '(GMT+05:45) Kathmandu'                       => 'Asia/Katmandu',
            '(GMT+06:00) Almaty'                          => 'Asia/Almaty',
            '(GMT+06:00) Dhaka'                           => 'Asia/Dhaka',
            '(GMT+06:00) Ekaterinburg'                    => 'Asia/Yekaterinburg',
            '(GMT+06:30) Rangoon'                         => 'Asia/Rangoon',
            '(GMT+07:00) Bangkok'                         => 'Asia/Bangkok',
            '(GMT+07:00) Jakarta'                         => 'Asia/Jakarta',
            '(GMT+07:00) Novosibirsk'                     => 'Asia/Novosibirsk',
            '(GMT+08:00) Chongqing'                       => 'Asia/Chongqing',
            '(GMT+08:00) Hong Kong'                       => 'Asia/Hong_Kong',
            '(GMT+08:00) Krasnoyarsk'                     => 'Asia/Krasnoyarsk',
            '(GMT+08:00) Kuala Lumpur'                    => 'Asia/Kuala_Lumpur',
            '(GMT+08:00) Perth'                           => 'Australia/Perth',
            '(GMT+08:00) Singapore'                       => 'Asia/Singapore',
            '(GMT+08:00) Taipei'                          => 'Asia/Taipei',
            '(GMT+08:00) Ulaan Bataar'                    => 'Asia/Ulan_Bator',
            '(GMT+08:00) Urumqi'                          => 'Asia/Urumqi',
            '(GMT+09:00) Irkutsk'                         => 'Asia/Irkutsk',
            '(GMT+09:00) Seoul'                           => 'Asia/Seoul',
            '(GMT+09:00) Tokyo'                           => 'Asia/Tokyo',
            '(GMT+09:30) Adelaide'                        => 'Australia/Adelaide',
            '(GMT+09:30) Darwin'                          => 'Australia/Darwin',
            '(GMT+10:00) Brisbane'                        => 'Australia/Brisbane',
            '(GMT+10:00) Canberra'                        => 'Australia/Canberra',
            '(GMT+10:00) Guam'                            => 'Pacific/Guam',
            '(GMT+10:00) Hobart'                          => 'Australia/Hobart',
            '(GMT+10:00) Melbourne'                       => 'Australia/Melbourne',
            '(GMT+10:00) Port Moresby'                    => 'Pacific/Port_Moresby',
            '(GMT+10:00) Sydney'                          => 'Australia/Sydney',
            '(GMT+10:00) Yakutsk'                         => 'Asia/Yakutsk',
            '(GMT+11:00) Vladivostok'                     => 'Asia/Vladivostok',
            '(GMT+12:00) Auckland'                        => 'Pacific/Auckland',
            '(GMT+12:00) Fiji'                            => 'Pacific/Fiji',
            '(GMT+12:00) International Date Line West'    => 'Pacific/Kwajalein',
            '(GMT+12:00) Kamchatka'                       => 'Asia/Kamchatka',
            '(GMT+12:00) Magadan'                         => 'Asia/Magadan',
            '(GMT+13:00) Nuku\'alofa'                     => 'Pacific/Tongatapu'
        ];
    }
}

<?php

namespace App\Support;

final class GoogleAdsTargetingCatalog
{
    /** @var list<string> Google Ads Editor location names (English). */
    public const LOCATIONS = [
        'Afghanistan',
        'Albania',
        'Algeria',
        'Argentina',
        'Armenia',
        'Australia',
        'Austria',
        'Azerbaijan',
        'Bahrain',
        'Bangladesh',
        'Belarus',
        'Belgium',
        'Bolivia',
        'Bosnia and Herzegovina',
        'Brazil',
        'Bulgaria',
        'Cambodia',
        'Cameroon',
        'Canada',
        'Chile',
        'Colombia',
        'Costa Rica',
        'Croatia',
        'Cyprus',
        'Czechia',
        'Denmark',
        'Dominican Republic',
        'Ecuador',
        'Egypt',
        'El Salvador',
        'Estonia',
        'Ethiopia',
        'Finland',
        'France',
        'Georgia',
        'Germany',
        'Ghana',
        'Greece',
        'Guatemala',
        'Hong Kong',
        'Hungary',
        'Iceland',
        'India',
        'Indonesia',
        'Iraq',
        'Ireland',
        'Israel',
        'Italy',
        'Japan',
        'Jordan',
        'Kazakhstan',
        'Kenya',
        'Kuwait',
        'Latvia',
        'Lebanon',
        'Lithuania',
        'Luxembourg',
        'Malaysia',
        'Malta',
        'Mexico',
        'Morocco',
        'Myanmar (Burma)',
        'Nepal',
        'Netherlands',
        'New Zealand',
        'Nigeria',
        'North Macedonia',
        'Norway',
        'Oman',
        'Pakistan',
        'Panama',
        'Paraguay',
        'Peru',
        'Philippines',
        'Poland',
        'Portugal',
        'Puerto Rico',
        'Qatar',
        'Romania',
        'Russia',
        'Saudi Arabia',
        'Senegal',
        'Serbia',
        'Singapore',
        'Slovakia',
        'Slovenia',
        'South Africa',
        'South Korea',
        'Spain',
        'Sri Lanka',
        'Sweden',
        'Switzerland',
        'Taiwan',
        'Thailand',
        'Tunisia',
        'Turkey',
        'Uganda',
        'Ukraine',
        'United Arab Emirates',
        'United Kingdom',
        'United States',
        'Uruguay',
        'Uzbekistan',
        'Venezuela',
        'Vietnam',
        'Yemen',
        'Zambia',
        'Zimbabwe',
    ];

    /** @var list<string> Google Ads Editor language names. */
    public const LANGUAGES = [
        'All languages',
        'Arabic',
        'Bengali',
        'Bulgarian',
        'Catalan',
        'Chinese (simplified)',
        'Chinese (traditional)',
        'Croatian',
        'Czech',
        'Danish',
        'Dutch',
        'English',
        'Estonian',
        'Filipino',
        'Finnish',
        'French',
        'German',
        'Greek',
        'Hebrew',
        'Hindi',
        'Hungarian',
        'Indonesian',
        'Italian',
        'Japanese',
        'Korean',
        'Latvian',
        'Lithuanian',
        'Malay',
        'Norwegian',
        'Polish',
        'Portuguese',
        'Romanian',
        'Russian',
        'Serbian',
        'Slovak',
        'Slovenian',
        'Spanish',
        'Swedish',
        'Thai',
        'Turkish',
        'Ukrainian',
        'Vietnamese',
    ];

    /** @var array<string, string> */
    public const NETWORKS = [
        'network_search' => 'Google search',
        'network_search_partners' => 'Search partners',
        'network_display' => 'Display Network',
    ];

    /** @return list<string> */
    public static function allLocations(): array
    {
        return self::LOCATIONS;
    }

    /** @return list<string> */
    public static function allLanguages(): array
    {
        return self::LANGUAGES;
    }

    /** @return list<string> Every language except the "All languages" sentinel. */
    public static function concreteLanguages(): array
    {
        return array_values(array_filter(
            self::LANGUAGES,
            static fn (string $language): bool => $language !== 'All languages'
        ));
    }
}

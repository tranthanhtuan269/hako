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

    /**
     * Google Ads Editor language codes (campaign Languages column).
     *
     * @var array<string, string>
     */
    public const LANGUAGE_CODES = [
        'Arabic' => 'ar',
        'Bengali' => 'bn',
        'Bulgarian' => 'bg',
        'Catalan' => 'ca',
        'Chinese (simplified)' => 'zh_CN',
        'Chinese (traditional)' => 'zh_TW',
        'Croatian' => 'hr',
        'Czech' => 'cs',
        'Danish' => 'da',
        'Dutch' => 'nl',
        'English' => 'en',
        'Estonian' => 'et',
        'Filipino' => 'tl',
        'Finnish' => 'fi',
        'French' => 'fr',
        'German' => 'de',
        'Greek' => 'el',
        'Hebrew' => 'iw',
        'Hindi' => 'hi',
        'Hungarian' => 'hu',
        'Indonesian' => 'id',
        'Italian' => 'it',
        'Japanese' => 'ja',
        'Korean' => 'ko',
        'Latvian' => 'lv',
        'Lithuanian' => 'lt',
        'Malay' => 'ms',
        'Norwegian' => 'no',
        'Polish' => 'pl',
        'Portuguese' => 'pt',
        'Romanian' => 'ro',
        'Russian' => 'ru',
        'Serbian' => 'sr',
        'Slovak' => 'sk',
        'Slovenian' => 'sl',
        'Spanish' => 'es',
        'Swedish' => 'sv',
        'Thai' => 'th',
        'Turkish' => 'tr',
        'Ukrainian' => 'uk',
        'Vietnamese' => 'vi',
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

    /**
     * @param  list<string>  $languages
     */
    public static function languageCodesCsv(array $languages): string
    {
        $concrete = self::concreteLanguages();

        if (in_array('All languages', $languages, true)) {
            return '';
        }

        $codes = [];
        foreach ($languages as $language) {
            $code = self::LANGUAGE_CODES[$language] ?? null;
            if ($code !== null && ! in_array($code, $codes, true)) {
                $codes[] = $code;
            }
        }

        // Selecting every language = Editor default (all languages).
        if (count($codes) >= count($concrete)) {
            return '';
        }

        return implode(';', $codes);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public static function networksCsv(array $settings): string
    {
        $parts = [];
        foreach (self::NETWORKS as $key => $label) {
            if (! empty($settings[$key])) {
                $parts[] = $label;
            }
        }

        return implode(';', $parts);
    }
}

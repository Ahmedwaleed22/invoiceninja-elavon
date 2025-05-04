<?php
/**
 * Invoice Ninja (https://invoiceninja.com).
 *
 * @link https://github.com/invoiceninja/invoiceninja source repository
 *
 * @copyright Copyright (c) 2025. Invoice Ninja LLC (https://invoiceninja.com)
 *
 * @license https://www.elastic.co/licensing/elastic-license
 */

/**
 * Returns a custom translation string
 * falls back on defaults if no string exists.
 *
 * //Cache::forever($custom_company_translated_string, 'mogly');
 *
 * @param string $string
 * @param array|mixed $replace
 * @param null $locale
 * @return string
 */
function ctrans(string $string, $replace = [], $locale = null): string
{
    // Ensure $replace is always a properly formatted array
    if (!is_array($replace)) {
        $replace = [];
    } else {
        // Make sure all values in the array are safe for translation
        foreach ($replace as $key => $value) {
            if (is_int($value)) {
                $replace[$key] = (string)$value;
            } elseif (is_object($value) && !method_exists($value, '__toString')) {
                $replace[$key] = '';
            }
        }
    }
    
    try {
        return html_entity_decode(trans($string, $replace, $locale));
    } catch (\Exception $e) {
        // Fallback if translation fails
        return html_entity_decode(trans($string, [], $locale));
    }
}

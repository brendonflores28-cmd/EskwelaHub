<?php

namespace App\Services\Chatbot;

use App\Support\NameIndex;

/**
 * Tells Tagalog from English (and Taglish) by counting common words.
 * Spelling mistakes are fine: it only needs a few words to be recognised.
 */
class Language
{
    /** Very common Tagalog words and short forms people type in chat. */
    private const TAGALOG = [
        'ang', 'ng', 'mga', 'sa', 'na', 'ko', 'mo', 'ba', 'po', 'opo', 'ako', 'ikaw', 'ka', 'siya', 'kami', 'tayo', 'sila',
        'ano', 'paano', 'pano', 'saan', 'kailan', 'bakit', 'sino', 'magkano', 'gaano', 'ilan', 'pwede', 'puwede', 'pede',
        'gusto', 'ayaw', 'kailangan', 'dapat', 'hindi', 'di', 'wala', 'may', 'meron', 'mayroon', 'ito', 'iyan', 'iyon', 'yan',
        'yung', 'yong', 'ung', 'lang', 'din', 'rin', 'naman', 'nga', 'kasi', 'pero', 'at', 'o', 'para', 'kung', 'kapag', 'pag',
        'anak', 'magulang', 'guro', 'mag', 'nag', 'pa', 'daw', 'raw', 'talaga', 'salamat', 'kumusta', 'musta', 'magandang',
        'umaga', 'hapon', 'gabi', 'paki', 'pakituro', 'turuan', 'tulong', 'tulungan', 'sagot', 'tanong', 'itanong', 'ipakita',
        'saan', 'nasaan', 'makita', 'kuha', 'kunin', 'humingi', 'magrequest', 'mag-request', 'magenroll', 'mag-enroll',
        'paalam', 'pasensya', 'sige', 'oo', 'hindi', 'bakit', 'lahat', 'bago', 'luma', 'ngayon', 'bukas', 'kahapon',
    ];

    /** @return 'tl'|'en' */
    public static function detect(string $text): string
    {
        $words = NameIndex::tokens($text);
        if ($words === []) {
            return 'en';
        }

        $tagalog = count(array_filter($words, fn (string $w) => in_array($w, self::TAGALOG, true)));

        // Two Tagalog words, or one in a very short message ("salamat", "kumusta"), is enough.
        return ($tagalog >= 2 || ($tagalog === 1 && count($words) <= 3)) ? 'tl' : 'en';
    }

    /** True when the person asks how or where to do something, so a step-by-step guide should start by itself. */
    public static function wantsTeaching(string $text): bool
    {
        $teach = ['how', 'paano', 'pano', 'saan', 'where', 'show', 'ipakita', 'ituro', 'turuan', 'teach', 'guide', 'steps', 'tutorial', 'gabay', 'hakbang', 'tulungan', 'help', 'tulong'];

        foreach (NameIndex::tokens($text) as $word) {
            if (self::like($word, $teach)) {
                return true;
            }
        }

        return false;
    }

    /** Same word, allowing one typo in words of five letters or more. @param  list<string>  $words */
    public static function like(string $word, array $words): bool
    {
        foreach ($words as $candidate) {
            if ($word === $candidate || (strlen($word) >= 5 && strlen($candidate) >= 5 && levenshtein($word, $candidate) <= 1)) {
                return true;
            }
        }

        return false;
    }

    /** All Tagalog marker words, used to ignore them when matching topics. @return list<string> */
    public static function tagalogWords(): array
    {
        return self::TAGALOG;
    }
}

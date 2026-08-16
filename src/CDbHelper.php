<?php
/**
 * DBHelper – PHP equivalents of the C# “NameValueCollection” utilities.
 *
 * The original C# code works with System.Collections.Specialized.NameValueCollection,
 * which is essentially a multivalue dictionary. In this PHP port we treat it as a
 * simple associative array (`['col' => 'value', …]`).  If you need true multivalues
 * you can adapt the code to use arrays of values.
 *
 * -----------------------------------------------------------------------
 * IMPORTANT:
 *   * All values are automatically escaped with addslashes() when they are
 *     interpolated into an SQL fragment.  For production code you **must**
 *     use prepared statements (`?` placeholders) rather than building raw strings.
 *   * The `placeholder` parameter ("?" in the original) enables the safe path.
 *   * The helper `FOV()` builds a fragment like `column LIKE ?` or
 *     `column LIKE '%value%'`.
 * -----------------------------------------------------------------------
 */
class CDbHelper
{
    /* --------------------------------------------------------------------
     * Helper: builds a “field op value” fragment.
     *
     * @param string $field      Column name
     * @param string $op         Operator (normally "LIKE")
     * @param string $value      Either a literal value or a placeholder (e.g. "?")
     * @return string            Fragment ready to be concatenated into a WHERE clause
     * -------------------------------------------------------------------- */
    private static function FOV(string $field, string $op, string $value): string
    {
        // If the caller supplied a placeholder we do *not* quote it.
        if ($value === '?') {
            return sprintf("%s %s ?", $field, $op);
        }

        // For literal values we wrap them in single quotes and escape any inner quotes.
        $escaped = addslashes($value);
        return sprintf("%s %s '%s'", $field, $op, $escaped);
    }

    /* --------------------------------------------------------------------
     * Helper: trims the trailing separator from a StringBuilder‑style string.
     *
     * @param string $str   The built string (e.g. "a=1 OR b=2 OR ")
     * @param string $sep   The separator that should be stripped (e.g. "OR")
     * @return string       The string without the last separator
     * -------------------------------------------------------------------- */
    private static function sb2s(string $str, string $sep): string
    {
        // Remove the *last* occurrence of the separator (plus any surrounding spaces)
        $pattern = '/' . preg_quote(' ' . $sep . ' ', '/') . '$/';
        return preg_replace($pattern, '', $str);
    }

    /* --------------------------------------------------------------------
     * 1️⃣ nv2NvLike – adds % wildcards to every non‑empty value.
     *
     *  C# logic:
     *      foreach (key in source) {
     *          if (source[key].Length > 0) dest.Add(key, "%"+source[key]+"%");
     *      }
     *
     *  PHP version returns a **new associative array**.
     * -------------------------------------------------------------------- */
    public static function nv2NvLike(array $source): array
    {
        $dest = [];

        foreach ($source as $key => $val) {
            if (strlen($val) > 0) {
                $dest[$key] = '%' . $val . '%';
            }
        }

        return $dest;
    }

    /* --------------------------------------------------------------------
     * 2️⃣ Nv2sLike – creates a concatenated WHERE‑clause fragment that
     *                uses the LIKE operator.  It can either:
     *                * use a placeholder “?” for each field (safer), or
     *                * embed the literal value directly (risky).
     *
     *  Parameters:
     *      $source      – associative array of column => value
     *      $placeholder – if set to "?" each condition becomes `col LIKE ?`
     *                     otherwise it becomes `col LIKE '%value%'`
     *      $separator   – string used to join the fragments (default “OR”)
     *
     *  The method also removes any keys named "t" or "a" (mirroring the
     *  C# `source.Remove("t"); source.Remove("a");`).
     *
     *  Returns the final string **without** a trailing separator.
     * -------------------------------------------------------------------- */
    public static function nv2sLike(
        array $source,
        string $placeholder = '',
        string $separator = 'OR'
    ): string {
        // Make a mutable copy so we can unset items without affecting the caller.
        $src = $source;

        // Remove the “t” and “a” keys if they exist – the same as the C# code.
        unset($src['t'], $src['a']);

        if (empty($src)) {
            return '';
        }

        $op   = 'LIKE';
        $sep  = " {$separator} ";
        $sb   = '';

        foreach ($src as $field => $value) {
            if (strlen($value) === 0) {
                continue; // skip empty values (C# checks Length > 0)
            }

            // Build single fragment
            if ($placeholder === '?') {
                // Safer path – we keep the placeholder; actual binding must be done later.
                $fragment = self::FOV($field, $op, '?');
                // Remember the raw value for later binding (optional – developer can call bindValue themselves)
                // In this plain helper we only return the SQL fragment.
            } else {
                // Direct embedding – wrap value in % wildcards.
                $wild = '%' . $value . '%';
                $fragment = self::FOV($field, $op, $wild);
            }

            $sb .= $fragment . $sep;
        }

        // Trim the final separator (identical to sb2s in the original code)
        $result = self::sb2s($sb, $separator);
        return $result;
    }

    /* --------------------------------------------------------------------
     * 3️⃣ AndOrNot – wraps an expression in parentheses and optionally
     *                prefixes it with an operator (AND / OR).
     *
     *  C# logic:
     *      if (!CString.IsEmpty(str)) {
     *          if (!CString.IsEmpty(opr))
     *              ret = $" {opr} ({str})";
     *          else
     *              ret = $" ({str})";
     *      }
     *
     *  PHP version follows the same rules.
     * -------------------------------------------------------------------- */
    public static function andOrNot(string $str, string $op = ''): string
    {
        $trimmed = trim($str);
        if ($trimmed === '') {
            return '';
        }

        if (trim($op) !== '') {
            return sprintf(' %s (%s)', $op, $trimmed);
        }

        return sprintf(' (%s)', $trimmed);
    }
}

/* --------------------------------------------------------------------
 * Example usage (you can delete this block in production)
 * -------------------------------------------------------------------- */
if (php_sapi_name() === 'cli') {
    // Sample data
    $filters = [
        'first_name' => 'John',
        'last_name'  => 'Doe',
        'age'        => '',
        't'          => 'should be removed',
        'a'          => 'also removed',
    ];

    // 1️⃣ nv2NvLike – add % wildcards
    $wildcards = self::nv2NvLike($filters);
    // $wildcards => ['first_name' => '%John%', 'last_name' => '%Doe%']

    // 2️⃣ nv2sLike – build a WHERE clause fragment
    // Safer prepared‑statement version (uses "?")
    $wherePrepared = self::nv2sLike($filters, '?', 'AND');
    // $wherePrepared => "first_name LIKE ? AND last_name LIKE ?"

    // Direct‑value (dangerous) version
    $whereDirect = self::nv2sLike($filters, '', 'OR');
    // $whereDirect => "first_name LIKE '%John%' OR last_name LIKE '%Doe%'"

    // 3️⃣ andOrNot – wrap expression
    $wrapped = self::andOrNot($whereDirect, 'AND');
    // $wrapped => " AND (first_name LIKE '%John%' OR last_name LIKE '%Doe%')"

    // Output for demonstration
    echo "nv2NvLike:\n";
    print_r($wildcards);
    echo "\nnv2sLike (prepared):\n$wherePrepared\n";
    echo "\nnv2sLike (direct):\n$whereDirect\n";
    echo "\nandOrNot:\n$wrapped\n";
}
?>
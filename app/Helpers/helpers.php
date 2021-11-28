<?php
function sort_mark($field, $sort_params)
{
    $icon = '';
    if (isset($sort_params)) {
        $i = 0;
        foreach ($sort_params as $prm) {
            $i++;
            if ($prm['field'] == $field) {
                if ($prm['dir'] == 'asc')
                    $icon = '<i class="fa fa-sort-amount-asc text-success" aria-hidden="true">' . $i . '</i>';
                else
                    $icon = '<i class="fa fa-sort-amount-desc text-danger" aria-hidden="true">' . $i . '</i>';
                break;
            }
        }
    }
    return $icon;
}

function trim_characters($text, $length = 45, $append = '&hellip;')
{

    $length = (int)$length;
    $text = trim(strip_tags($text));

    if (strlen($text) > $length) {
        $text = substr($text, 0, $length + 1);
        $words = preg_split("/[\s]|&nbsp;/", $text, -1, PREG_SPLIT_NO_EMPTY);
        preg_match("/[\s]|&nbsp;/", $text, $lastchar, 0, $length);
        if (empty($lastchar))
            array_pop($words);

        $text = implode(' ', $words) . $append;
    }

    return $text;
}

    //Performs a regex-texthighlight
    function textHighlight($text, $search, $highlightColor = '#0000FF', $casesensitive = false)
    {
        $modifier = ($casesensitive) ? 'i' : '';
        //quote search-string, cause preg_replace wouldn't work correctly if chars like $?. were in search-string
        $quotedSearch = preg_quote($search, '/');
        //generate regex-search-pattern
        $checkPattern = '/' . $quotedSearch . '/' . $modifier;
        //generate regex-replace-pattern
        $strReplacement = '$0';
        $strReplacement = '<span style="color:' . $highlightColor . ';">$0</span>';
        return preg_replace($checkPattern, $strReplacement, $text);
    }
?>
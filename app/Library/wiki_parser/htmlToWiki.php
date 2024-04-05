<?php namespace App\Library\wiki_parser {

    /**
     * Created by Sunil Singh.
     * User: sunilspr7 (Github)
     * Date: 2022.12.19
     */
    class htmlToWiki {

        //@formatter:off
        private static $inline = array(
            '<i>'       => "''",        // italic
            '</i>'      => "''",
            '<b>'       => " '''",       // bold
            '</b>'      => "''' ",
            '<strong>'  => " '''",
            '</strong>' => "''' ",
            '<span>'    => "",          // span
            '</span>'   => "",
            '&nbsp;'    => " "
        );

        private static $block = array(
            '<div>'     => "",          // div
            '</div>'    => "\n",
            '<p>'       => "\n",        // p
            '</p>'      => "\n",
            '<ul>'      => "\n",        // ul
            '</ul>'     => "\n",
            '<ol>'      => "\n",        // ol
            '</ol>'     => "\n",
            '<li>'      => "* ",        // li
            '</li>'     => "\n"
        );

        private static $heading = array(
            '<h1>'      => "\n= ",
            '</h1>'     => " =\n",
            '<h2>'      => "\n== ",
            '</h2>'     => " ==\n",
            '<h3>'      => "\n=== ",
            '</h3>'     => " ===\n",
            '<h4>'      => "\n==== ",
            '</h4>'     => " ====\n",
            '<h5>'      => "\n===== ",
            '</h5>'     => " =====\n",
            '<h6>'      => "\n====== ",
            '</h6>'     => " ======\n",
        );

        private static $separator = array(
            '<br>'      => "\n",
            '<br/>'     => "\n",
            '<hr>'      => "---\n",
            '<hr/>'     => "---\n"
        );

        private static $bullets_and_hyphens = array(
            // BULLETS
            "•",    // \x{2022} --> BULLET
            "∙",    // \x{2219} --> BULLET OPERATOR
            "◦",    // \x{25E6} --> WHITE BULLET
            "◘",   // \x{25D8} --> INVERSE BULLET
            "⦿",   // \x{29BF} --> CIRCLED BULLET
            "‣",    // \x{2023} --> TRIANGULAR BULLET
            "⁃",    // \x{2043} --> HYPHEN BULLET
            "·",    // \x{00B7} --> MIDDLE DOT

            // HYPHENS
            "-",     // \x{002D} --> HYPHEN-MINUS
            "⁻",     // \x{207B} --> SUPERSCRIPT MINUS
            "₋",     // \x{208B} --> SUBSCRIPT MINUS
            "﹣",    // \x{FE63} --> SMALL HYPHEN-MINUS
            "－"     // \x{FF0D} --> FULLWIDTH HYPHEN-MINUS
        );
        //@formatter:on


        /**
         * @param $html String
         * @return String
         */
        public function toWiki($html) {
            // TRIM --------------------------------
            $result = trim($html);

            // CONVERSION --------------------------
            // 1. Get the payload
            $result = $this->parseHTMLBody($result);
            // 2. Make single line to prevent new-lines problems
            $result = $this->removeNewLines($result);
            // 3. Parse elements (that depend attributes like <a href...>)
            $result = $this->replaceHTMLLinks($result);
            // 4. Clean unnecessary HTML attributes
            $result = $this->removeHTMLArguments($result);
            // 5. Parse elements
            $result = $this->replaceHTMLInlineElements($result);
            $result = $this->replaceHTMLBlockElements($result);
            $result = $this->replaceHTMLHeadingElements($result);
            $result = $this->replaceHTMLSeparatorElements($result);
            // 6. Clean Wiki
            $result = $this->trimLines($result);
            $result = $this->removeWhitespaces($result);
            $result = $this->removeBlankLines($result);
            $result = $this->removeHTMLElements($result);
            // 7. Fix Wiki content
            $result = $this->fixWiki($result);

            return trim($result);
        }


        // HTML CLEANING & CONVERTER FUNCTIONS ----------------------------------------------
        public function parseHTMLBody($html) {
            preg_match('/<body[^>]*>(.*?)<\/body>/s', $html, $matches);
            return !empty($matches) ? trim($matches[1]) : trim($html);
        }

        public function replaceHTMLLinks($html) {
            // Based on: http://www.the-art-of-web.com/php/parse-links/
            preg_match_all('/<a\s[^>]*href\s*=\s*(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>/siU', $html, $matches, PREG_PATTERN_ORDER);
            if (!empty($matches)) {
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $match = trim($matches[0][$i]);
                    $link = trim($matches[2][$i]);
                    $text = $this->removeHTMLElements(trim($matches[3][$i]));
                    if (!empty($match) && !empty($link) && !empty($text)) {
                        $wiki_link = '[' . $link . ' ' . $text . ']';
                        $html = str_replace($match, $wiki_link, $html);
                    }
                }
            }
            return $html;
        }

        public function removeHTMLArguments($html) {
            return preg_replace('/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i', '<$1$2>', $html);
        }

        public function removeHTMLElements($html) {
            return trim(strip_tags($html));
        }

        public function replaceHTMLInlineElements($html) {
            foreach (self::$inline as $k => $v)
                $html = str_replace($k, $v, $html);
            return $html;
        }

        public function replaceHTMLBlockElements($html) {
            foreach (self::$block as $k => $v)
                $html = str_replace($k, $v, $html);
            return $html;
        }

        public function replaceHTMLHeadingElements($html) {
            foreach (self::$heading as $k => $v)
                $html = str_replace($k, $v, $html);
            return $html;
        }

        public function replaceHTMLSeparatorElements($html) {
            foreach (self::$separator as $k => $v)
                $html = str_replace($k, $v, $html);
            return $html;
        }

        // SIMPLE TEXT CLEANING FUNCTIONS ----------------------------------------------
        public function removeNewLines($text) {
            return trim(preg_replace('/\s+/', ' ', $text));
        }

        public function removeWhitespaces($text) {
            for ($i = 0; $i < 5; $i++)
                $text = str_replace('  ', ' ', $text);
            return $text;
        }

        public function removeBlankLines($text) {
            return preg_replace('/\n(\s*\n)+/', "\n\n", $text);
        }

        public function trimLines($text) {
            return implode("\n", array_map('trim', explode("\n", $text)));
        }


        // CONTENT FIXING -------------------------------------------------------------
        public function fixWiki($wiki) {
            $rows = explode("\n", $wiki);
            for($i=0; $i<count($rows); $i++){
                if(!empty($rows[$i])){
                    $rows[$i] = $this -> fixHeadings($rows[$i]);
                    $rows[$i] = $this -> fixBulletsAndHyphens($rows[$i]);
                    $rows[$i] = $this -> fixRow($rows[$i]);
                }
            }
            $wiki = implode("\n", $rows);
            $wiki = $this -> fixListsEmptyRows($wiki);
            return $wiki;
        }

        public function fixHeadings($row){
            if($this->startsWith($row, "="))
                return preg_replace("/^(=+\s+)'''(.*)'''\s+(=+)$/u", '$1 $2 $3', $row);
            return $row;
        }

        public function fixBulletsAndHyphens($row){
            $end = min(mb_strpos($row, ' '), mb_strlen($row));
            $part = mb_substr($row, 0, $end);
            foreach(self::$bullets_and_hyphens as $bullet)
                $part = str_ireplace($bullet, "*", $part);
            $only_first_match = 1;
            $row = substr_replace($row, $part, 0, $end);
            $row = str_replace("'''* '''", "* ", $row, $only_first_match);
            return $row;
        }

        public function fixRow($row){
            if(trim($row, "'= ") == '') return '';
            return $row;
        }

        public function fixListsEmptyRows($wiki){
            return preg_replace("/\n\n\*/i", "\n*", $wiki);
        }


        // Utils
        private function startsWith($haystack, $needle)
        {
            $length = strlen($needle);
            return (substr($haystack, 0, $length) === $needle);
        }

    }
}
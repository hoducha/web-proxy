<?php
/**
 * Created by PhpStorm.
 * User: Ha
 * Date: 7/10/2015
 * Time: 4:32 PM
 */

namespace Dootech\WebProxy\Parser;

/**
 * Class ContentParser
 * This class is heavily borrowed from KnProxy Parser
 * https://github.com/jabbany/knProxy/blob/master/includes/module_parser.php
 *
 * @package Dootech\WebProxy
 */
class ContentParser
{
    private $content;
    private $urlParser;
    private $urlPrefix;

    // TODO: Verify it works
    private $enableJavascriptParsing = false;
    private $enableInjectedAjaxFix = false;

    public function __construct($content, $url, $urlPrefix)
    {
        $this->content = $content;
        $this->urlParser = new UrlParser($url);
        $this->urlPrefix = $urlPrefix;
    }

    function parseCss()
    {
        return $this->cssParse($this->content);
    }

    function parseJS()
    {
        return $this->jsParse($this->content);
    }

    function parseHTML()
    {
        $noJS = false;
        $code = preg_replace_callback('~<script([^>]*)>(.*)</script>~iUs', Array('self', '__cb_escapeJSLT'), $this->content);
        //Prevents lt signs messing up the parser
        if (preg_last_error() != PREG_NO_ERROR) {
            $noJS = true;
            $code = $this->content;
        }
        $code = preg_replace_callback('~<([^!].*)>~iUs', Array('self', '__cb_htmlTag'), $code);

        if ($this->enableInjectedAjaxFix) {
            $code = preg_replace("~<\s*head\s*>~iUs", '<head><script type="text/javascript" language="javascript" src="js/ajaxfix.js"></script>', $code);
        }

        $code = preg_replace_callback('~(<\s*style[^>]*>)(.*)<\s*/style\s*>~iUs', Array('self', '__cb_cssTag'), $code);
        if (!$noJS) {
            $code = preg_replace_callback('~<script([^>]*)>(.*)<\s*/\s*script>~iUs', Array('self', '__cbJSParser'), $code);
        }
        return $code;
    }

    protected function toAbsoluteUrl($urlField)
    {
        if ($urlField == '') {
            return '';
        }

        if (strtolower(substr($urlField, 0, 5)) == 'data:' || strtolower(substr($urlField, 0, 1)) == '#') {
            return $urlField;
        } elseif (strtolower(substr($urlField, 0, 11)) == 'javascript:') {
            return 'javascript:' . $this->jsParse(substr($urlField, 10, strlen($urlField)));
        }

        $urlBase = $this->urlParser->getAbsolute($urlField);

        return $this->urlPrefix .urlencode($urlBase);
    }

    /**
     * Parse JS string
     *
     * @param string $js
     * @return string
     */
    protected function jsParse($js)
    {
        if($this->enableJavascriptParsing == false)
            return $js;

        //Remove the comments
        $replace = Array();
        $ptr = 0;
        $len = strlen($js);
        $in = false;
        $temp = 0;
        $regex = false;
        $comment = false;
        $slcmt = false;
        while ($ptr < $len) {
            if (!$comment && $js[$ptr] == "\\") {
                $ptr += 2;
                continue;
            }
            if (($js[$ptr] == "'" || $js[$ptr] == '"') && !$in && !$comment && !$regex && !$slcmt) {
                $li = $js[$ptr];
                $temp = $ptr;
                $in = true;
                $ptr++;
                continue;
            }
            if ($js[$ptr] == '/' && $js[$ptr + 1] == '*' && !$in && !$regex && !$comment && !$slcmt) {
                $comment = true;
                $ptr++;
                continue;
            }
            if ($js[$ptr] == '/' && $ptr > 0 && $js[$ptr - 1] == '*' && $comment) {
                $comment = false;
                $ptr++;
                continue;
            }

            if ($js[$ptr] == '/' && $js[$ptr + 1] == '/' && !$in && !$regex && !$comment && !$slcmt) {
                $slcmt = true;
                $ptr++;
                continue;
            }
            if ($slcmt && $js[$ptr] == "\n") {
                $slcmt = false;
                $ptr++;
                continue;
            }

            if ($js[$ptr] == '/' && !$in && !$comment && !$slcmt) {
                //Might be A division sign!
                if (!$regex) {
                    $lookAhead = substr($js, $ptr + 1, 256);
                    $lookBehind = substr($js, $ptr - 10, 10);
                    if (preg_match('~[a-zA-Z0-9)]\s*$~iUs', $lookBehind) || !preg_match('~/~', $lookAhead)) {
                        $ptr++;
                        continue;
                    }
                }
                $regex = !$regex;
                $ptr++;
                continue;
            }
            if (isset($li) && $js[$ptr] == $li && $in) {
                $replace[] = Array($temp + 1, $ptr, $this->__cb_jsStr(substr($js, $temp + 1, $ptr - $temp - 1)));
                $temp = 0;
                $in = false;
            }
            $ptr++;
        }
        $offset = 0;
        foreach ($replace as $r) {
            $before = substr($js, 0, $r[0] + $offset);
            $after = substr($js, $r[1] + $offset, strlen($js) + $offset);
            $diff = strlen($r[2]) - $r[1] + $r[0];
            $js = $before . $r[2] . $after;
            $offset += $diff;
        }
        return $js;
    }

    /**
     * Parse css string
     *
     * @param $css
     * @return mixed
     */
    protected function cssParse($css)
    {
        $css = preg_replace_callback('~(url|src)(\()\s*([^\s]*.*)\s*\)~iUs', array('self', '__cb_std'), $css);
        $css = preg_replace_callback('~(@import\s*)(["\'\(])\s*([^\s].*)\s*["\'\)]~iUs', array('self', '__cb_std'), $css);
        return $css;
    }

    /**
     * Standard URL parse callback function
     *
     * @param $m
     * @return string
     */
    protected function __cb_std($m)
    {
        $url = $m[3];
        $delimiter = $m[2];
        $method = $m[1];
        $wrapper = '';
        //Find Wrappers for the URL
        if (preg_match('~^([\'"])(.+)\1$~iUs', $url, $wrp)) {
            $url = $wrp[2];
            $wrapper = $wrp[1];
        }
        $url = $wrapper . $this->toAbsoluteUrl($url) . $wrapper;
        if ($delimiter == '(')
            return $method . '(' . $url . ')';
        return $method . $delimiter . $url . $delimiter;
    }

    /**
     * Javascript parser callback function
     *
     * @param $jsStr
     * @return mixed|string
     */
    protected function __cb_jsStr($jsStr)
    {
        //Unescape this
        if (preg_match('~^http://(www\.)*w3\.org~', $jsStr))
            return $jsStr;//This is for initing namespaces probably.
        $unesc = preg_replace('~\\\\/~', '/', $jsStr);
        if (preg_match('~^https*://~', $unesc, $m) || preg_match('~^//~', $unesc, $m)) {
            if ($unesc == $jsStr)
                return $this->toAbsoluteUrl($unesc) . '&x=';
            else
                return preg_replace('~/~', "\\/", $this->toAbsoluteUrl($unesc)) . '&x=';
        }
        if (preg_match('~^/~', $unesc) && (preg_match('~\..{0,5}$~', $unesc) || preg_match('~/[a-zA-Z0-9\-_=]$~iUs', $unesc))) {
            if ($unesc == $jsStr)
                return $this->toAbsoluteUrl($unesc) . '&x=';
            else
                return preg_replace('~/~', "\\/", $this->toAbsoluteUrl($unesc)) . '&x=';
        }
        if ($unesc != $jsStr)
            $esc = true;
        $unesc = preg_replace_callback('~(href|src|codebase|url|action)\s*=\s*([\'\"])(?(2) (.*?)\\2 | ([^\s\>]+))~isx', array('self', '__cb_url'), $unesc);
        if (isset($esc) && $esc)
            $unesc = preg_replace('~/~', "\\/", $unesc);
        return $unesc;
    }

    /**
     * Html tag callback function
     *
     * @param $match
     * @return string
     */
    protected function __cb_htmlTag($match)
    {
        if ($match[1][0] == '/')
            return '<' . $match[1] . '>';
        if (preg_match('~^\s*form~iUs', $match[1])) {
            if (preg_match('~method\s*=~', $match[1])) {
                if (preg_match('~method\s*=\s*([\'\"]?)get~isx', $match[1])) {
                    $match[1] = preg_replace('~(method)\s*=\s*([\'\"])?(?(2) (.*?)\\2 | ([^\s\>]+))~isx', '$1="POST"', $match[1]);
                }
            } else {
                $match[1] .= ' method="POST"';
            }
        }
        $code = preg_replace_callback('~(href|src|codebase|url|action)\s*=\s*([\'\"])?(?(2) (.*?)\\2 | ([^\s\>]+))~isx', array('self', '__cb_url'), $match[1]);
        $code = preg_replace_callback('~(style\s*=\s*)([\'\"])(.*)\2~iUs', Array('self', '__cb_cssEmbed'), $code);
        return '<' . $code . '>';
    }

    protected function __cb_url($matches)
    {
        $method = $matches[1];
        $delim = $matches[2];
        if ($delim == '')
            $url = $matches[4];
        else
            $url = $matches[3];
        return $method . '=' . $delim . $this->toAbsoluteUrl($url) . $delim . ' ';
    }

    protected function __cb_cssEmbed($m)
    {
        return $m[1] . $m[2] . $this->cssParse($m[3]) . $m[2];
    }

    protected function __cb_cssTag($m)
    {
        return $m[1] . $this->cssParse($m[2]) . '</style>';
    }

    protected function __cb_escapeJSLT($matches)
    {
        $tagInner = preg_replace('~<~iUs', '#KNPROXY_SCRIPT_LT#', $matches[2]);
        return '<script' . $matches[1] . '>' . $tagInner . '</script>';
    }

    protected function __cbJSParser($matches)
    {
        $tagInner = preg_replace('~#knproxy_script_lt#~iUs', '<', $matches[2]);
        $tagInner = $this->jsParse($tagInner);
        return '<script' . $matches[1] . '>' . $tagInner . '</script>';
    }

    /**
     * @return boolean
     */
    public function isEnableInjectedAjaxFix()
    {
        return $this->enableInjectedAjaxFix;
    }

    /**
     * @param boolean $enableInjectedAjaxFix
     */
    public function setEnableInjectedAjaxFix($enableInjectedAjaxFix)
    {
        $this->enableInjectedAjaxFix = $enableInjectedAjaxFix;
    }

    /**
     * @return boolean
     */
    public function isEnableJavascriptParsing()
    {
        return $this->enableJavascriptParsing;
    }

    /**
     * @param boolean $enableJavascriptParsing
     */
    public function setEnableJavascriptParsing($enableJavascriptParsing)
    {
        $this->enableJavascriptParsing = $enableJavascriptParsing;
    }

}
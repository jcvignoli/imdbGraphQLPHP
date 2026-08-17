<?php

function esc($str)
{
    if ($str === null) {
        return '';
    }
    return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8');
}

function renderValue($value)
{
    if ($value === null) {
        return '<em>null</em>';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_scalar($value)) {
        $str = (string)$value;
        if (preg_match('~^https?://~i', $str)) {
            return '<a href="' . esc($str) . '" target="_blank">' . esc($str) . '</a>';
        }
        return esc($str);
    }
    if (is_array($value)) {
        if (empty($value)) {
            return '<em>(empty array)</em>';
        }
        // Check if sequential or associative
        $isSeq = array_keys($value) === range(0, count($value) - 1);
        if ($isSeq) {
            $items = [];
            foreach ($value as $item) {
                $items[] = '<li>' . renderValue($item) . '</li>';
            }
            return '<ul class="demo-list">' . implode('', $items) . '</ul>';
        } else {
            $rows = [];
            foreach ($value as $k => $v) {
                $rows[] = '<tr><th>' . esc($k) . '</th><td>' . renderValue($v) . '</td></tr>';
            }
            return '<table class="demo-subtable">' . implode('', $rows) . '</table>';
        }
    }
    if (is_object($value)) {
        if (method_exists($value, '__toString')) {
            return esc((string)$value);
        }
        return esc(get_class($value));
    }
    return esc((string)$value);
}

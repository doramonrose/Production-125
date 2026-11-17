<?php

/** Resolve signature files, including links saved before the personnel move. */
function pm_approve_memo_signature_url(string $file, string $base = ''): string
{
    $file = trim($file);
    if ($file === '') return '';
    $currentBase = 'https://mis.pharmacy.cmu.ac.th/person/MS/files_sig';
    $normalize = static function (string $url) use ($currentBase): string {
        return preg_replace('#^https?://(?:backoffice|mis)\.pharmacy\.cmu\.ac\.th/person/files_sig(?=/|$)#i', $currentBase, $url);
    };
    if (preg_match('#^(?:https?://|data:image/)#i', $file)) return $normalize($file);
    $base = $normalize(rtrim(trim($base), '/'));
    $file = preg_replace('#^/?files_sig/#i', '', $file);
    return ($base !== '' ? $base : $currentBase) . '/' . ltrim($file, '/');
}

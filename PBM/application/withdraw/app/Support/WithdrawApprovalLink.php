<?php

namespace App\Support;

class WithdrawApprovalLink
{
    public static function approvalPath(): string
    {
        return '/withdraw/approval';
    }

    public static function documentReviewPath(): string
    {
        return '/withdraw/document-review';
    }

    public static function nextApprover(int $id, string $kind = '', string $stepCode = ''): string
    {
        if ($id <= 0) {
            return url(self::approvalPath());
        }

        $path = trim($stepCode) === 'finance_officer'
            ? self::documentReviewPath()
            : self::approvalPath();

        $query = ['id' => $id];
        $normalizedKind = trim($kind);
        if ($normalizedKind !== '') {
            $query['kind'] = $normalizedKind;
        }

        return url($path).'?'.http_build_query($query);
    }
}

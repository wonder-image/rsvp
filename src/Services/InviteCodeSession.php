<?php

namespace Wonder\Plugin\Rsvp\Services;

use RuntimeException;
use Wonder\Plugin\Rsvp\Models\Authorization;
use Wonder\Plugin\Rsvp\Models\InviteCode;
use Wonder\Plugin\Rsvp\Models\InviteGroup;
use Wonder\Plugin\Rsvp\Models\Response;

final class InviteCodeSession
{
    private const SESSION_KEY = 'wonder_rsvp_invite_code_id';

    public static function login(string $code, array $allowedGroups = []): array
    {
        $code = self::normalizeCode($code);

        // Codice 905 = "Password errata" nelle traduzioni framework
        // (resources/lang/<lang>/notifications.json). Lato client il
        // toast viene mostrato con alertToast(905). Usiamo lo stesso
        // codice per tutti i casi di "credenziale rifiutata" così l'UX
        // è uniforme: l'ospite vede sempre "Password errata".
        $rejected = static function (string $reason): RuntimeException {
            return new RuntimeException($reason, 905);
        };

        if ($code === '') {
            throw $rejected(__t('pages.rsvp.api.login.missing_code'));
        }

        $record = self::findByCode($code);

        if ($record === null) {
            throw $rejected(__t('pages.rsvp.api.login.invalid_code'));
        }

        if (($record['active'] ?? 'false') !== 'true') {
            throw $rejected(__t('pages.rsvp.api.login.inactive_code'));
        }

        $groupCode = self::groupCode((int) ($record['invite_group_id'] ?? 0));

        if ($allowedGroups !== [] && !in_array($groupCode, $allowedGroups, true)) {
            throw $rejected(__t('pages.rsvp.api.login.unauthorized_group'));
        }

        $_SESSION[self::SESSION_KEY] = (int) $record['id'];

        return self::current();
    }

    public static function logout(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    public static function current(): array
    {
        $inviteCodeId = (int) ($_SESSION[self::SESSION_KEY] ?? 0);

        if ($inviteCodeId <= 0) {
            return [];
        }

        $rows = InviteCode::query()->Select(InviteCode::$table, [
            'id' => $inviteCodeId,
            'deleted' => 'false',
        ], 1);

        if (!$rows->success || !$rows->exists) {
            self::logout();

            return [];
        }

        $record = $rows->row;
        $active = (string) ($record['active'] ?? 'false') === 'true';

        if (!$active) {
            self::logout();

            return [];
        }

        $usageCount = self::usageCount($inviteCodeId);
        $usageMode = (string) ($record['usage_mode'] ?? 'multiple_use');
        $authorizationId = (int) ($record['authorization_id'] ?? 0);

        return [
            'id' => (int) ($record['id'] ?? 0),
            'code' => (string) ($record['code'] ?? ''),
            'usage_mode' => $usageMode,
            'usage_count' => $usageCount,
            'can_submit' => $usageMode !== 'single_use' || $usageCount <= 0,
            'active' => $active,
            'authorization_id' => $authorizationId,
            'authorization_code' => self::authorizationCode($authorizationId),
            'invite_group_id' => (int) ($record['invite_group_id'] ?? 0),
            'invite_group_code' => self::groupCode((int) ($record['invite_group_id'] ?? 0)),
        ];
    }

    public static function authorize(array $allowedGroups = []): array
    {
        $current = self::current();

        if ($current === []) {
            throw new RuntimeException(__t('pages.rsvp.api.session.not_active'));
        }

        if ($allowedGroups !== [] && !in_array($current['invite_group_code'], $allowedGroups, true)) {
            throw new RuntimeException(__t('pages.rsvp.api.session.unauthorized_group'));
        }

        return $current;
    }

    public static function usageCount(int $inviteCodeId): int
    {
        $result = Response::query()->Select(Response::$table, [
            'invite_code_id' => $inviteCodeId,
            'deleted' => 'false',
        ]);

        return (int) ($result->Nrow ?? 0);
    }

    private static function findByCode(string $normalizedCode): ?array
    {
        $exact = InviteCode::query()->Select(InviteCode::$table, [
            'code' => $normalizedCode,
            'deleted' => 'false',
        ], 1);

        if ($exact->success && $exact->exists && is_array($exact->row ?? null)) {
            return $exact->row;
        }

        $rows = InviteCode::query()->Select(InviteCode::$table, [
            'deleted' => 'false',
        ]);

        foreach ((array) ($rows->row ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            if (self::normalizeCode((string) ($row['code'] ?? '')) !== $normalizedCode) {
                continue;
            }

            return $row;
        }

        return null;
    }

    private static function normalizeCode(string $code): string
    {
        $code = trim($code);

        if ($code === '') {
            return '';
        }

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($code, 'UTF-8')
            : strtoupper($code);
    }

    private static function groupCode(int $inviteGroupId): string
    {
        if ($inviteGroupId <= 0) {
            return '';
        }

        $rows = InviteGroup::query()->Select(InviteGroup::$table, [
            'id' => $inviteGroupId,
            'deleted' => 'false',
        ], 1);

        return $rows->success && $rows->exists
            ? (string) ($rows->row['code'] ?? '')
            : '';
    }

    private static function authorizationCode(int $authorizationId): string
    {
        if ($authorizationId <= 0) {
            return '';
        }

        $rows = Authorization::query()->Select(Authorization::$table, [
            'id' => $authorizationId,
            'deleted' => 'false',
        ], 1);

        return $rows->success && $rows->exists
            ? (string) ($rows->row['code'] ?? '')
            : '';
    }
}

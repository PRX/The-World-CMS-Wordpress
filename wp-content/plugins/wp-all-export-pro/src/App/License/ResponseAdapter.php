<?php

namespace Wpae\App\License;

/**
 * The one place the wire format is read. A definitive answer is evidence about
 * the licence and may change stored state; a non-definitive one is evidence
 * about the network and must change nothing.
 */
final class ResponseAdapter
{
    /** @var array<string,string>  anything unmapped is BLOCKED, which entitles nothing */
    const LEGACY = [
        'valid' => LicenseStatus::VALID,
        'invalid' => LicenseStatus::BLOCKED,
        'active sites limit reached' => LicenseStatus::LIMIT_REACHED,
        'no_activations_left' => LicenseStatus::LIMIT_REACHED,
        'expired' => LicenseStatus::EXPIRED,
        'disabled' => LicenseStatus::DISABLED,
        'revoked' => LicenseStatus::DISABLED,
        'inactive' => LicenseStatus::BLOCKED,
        'site_inactive' => LicenseStatus::HOST_MISMATCH,
        'item_name_mismatch' => LicenseStatus::BLOCKED,
        'invalid_item_id' => LicenseStatus::BLOCKED,
        'missing' => LicenseStatus::UNKNOWN_KEY,
        'deactivated' => LicenseStatus::BLOCKED,
        'failed' => LicenseStatus::BLOCKED,
    ];

    /**
     * @param  array{error:bool,code:int,body:string}  $response
     * @return LicenseStatus|null  null when the answer is non-definitive
     */
    public function adapt(array $response, $checkedAt)
    {
        if (! empty($response['error'])) {
            return null;
        }

        // 503 is the server's explicit "ask me later", and no other code is an answer either.
        if ((int) $response['code'] !== 200) {
            return null;
        }

        $body = json_decode(isset($response['body']) ? (string) $response['body'] : '', true);

        if (! is_array($body) || $body === []) {
            return null;
        }

        // Neither key: not a check_license answer at all.
        if (! array_key_exists('license', $body) && ! array_key_exists('status', $body)) {
            return null;
        }

        return new LicenseStatus([
            'status' => $this->resolveStatus($body),
            'server_status' => isset($body['status']) && is_string($body['status']) ? $body['status'] : null,
            'license' => isset($body['license']) && is_string($body['license']) ? $body['license'] : null,
            'problems' => isset($body['problems']) ? $body['problems'] : [],
            'grace' => isset($body['grace']) ? $body['grace'] : false,
            'grace_reason' => isset($body['grace_reason']) && is_string($body['grace_reason'])
                ? $body['grace_reason']
                : null,
            'grace_until' => isset($body['grace_until']) ? $body['grace_until'] : null,
            'expires' => isset($body['expires']) ? $body['expires'] : null,
            'checked_at' => (int) $checkedAt,
        ]);
    }

    /** @return string */
    private function resolveStatus(array $body)
    {
        $status = $this->literalStatus($body);

        // The server still answers `valid` inside its own expiry window so ungated
        // plugins stay up. Here that window is grace, so only this plugin's clock runs.
        if ($status === LicenseStatus::VALID && ! empty($body['grace'])) {
            return LicenseStatus::GRACE;
        }

        return $status;
    }

    /**
     * An unrecognised `status` must fail OPEN, by deferring to `license`, or the
     * next value the server adds locks out the fleet. An unrecognised `license`
     * fails CLOSED: that vocabulary is frozen, and a 200 is definitive, so
     * believing one would cache a VALID that never expires.
     */
    private function literalStatus(array $body)
    {
        $status = isset($body['status']) && is_string($body['status']) ? $body['status'] : null;

        // `unavailable` is excluded on purpose: its wire form is the 503, never a 200.
        if ($status !== null && in_array($status, LicenseStatus::SERVER_STATES, true)) {
            return $status;
        }

        // Presence of the key, not readability: an unreadable `license` fails closed.
        if (! array_key_exists('license', $body)) {
            return LicenseStatus::VALID;
        }

        if (! is_string($body['license'])) {
            return LicenseStatus::BLOCKED;
        }

        // The literals are machine-emitted, so a difference in case is a middlebox.
        $literal = strtolower(trim($body['license']));

        if (isset(self::LEGACY[$literal])) {
            return self::LEGACY[$literal];
        }

        return LicenseStatus::BLOCKED;
    }
}

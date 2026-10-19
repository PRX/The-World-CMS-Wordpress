<?php

namespace Wpae\App\License;

final class LicenseCheck
{
    /** @var LicenseState */
    private $state;

    /** @var ResponseAdapter */
    private $adapter;

    /** @var callable */
    private $transport;

    /** @param  callable  $transport  fn(string $url): array{error:bool,code:int,body:string} */
    public function __construct(LicenseState $state, ResponseAdapter $adapter, $transport)
    {
        $this->state = $state;
        $this->adapter = $adapter;
        $this->transport = $transport;
    }

    /**
     * @param  string  $itemName  Matched exactly against package.name.
     * @return LicenseStatus|null  null when the answer was non-definitive
     */
    public function run($apiUrl, $key, $itemName, $siteUrl)
    {
        // The server trims too, so an untrimmed key is cached against the wrong licence.
        $key = LicenseState::normaliseKey($key);

        $this->state->syncKey($key);
        $this->state->recordAttempt();

        if ($key === '') {
            return null;
        }

        $url = rtrim((string) $apiUrl, '/') . '/check_license?' . http_build_query([
            'edd_action' => 'check_license',
            'license' => $key,
            'item_name' => (string) $itemName,
            'url' => (string) $siteUrl,
        ]);

        $response = call_user_func($this->transport, $url);

        if (! is_array($response)) {
            $response = ['error' => true, 'code' => 0, 'body' => ''];
        }

        $status = $this->adapter->adapt($response, $this->state->now());

        if ($status === null) {
            $this->state->recordNonDefinitive();

            return null;
        }

        $this->state->recordDefinitive($status);

        return $status;
    }
}

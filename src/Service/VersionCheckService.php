<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final class VersionCheckService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $appVersion,
        private readonly string $githubRepo,
    ) {
    }

    public function getCurrentVersion(): string
    {
        return trim($this->appVersion) !== '' ? trim($this->appVersion) : 'dev';
    }

    private function getGithubRepo(): string
    {
        $repo = trim($this->githubRepo);

        return $repo !== '' ? $repo : 'ricktg/my-invoice';
    }

    /**
     * @return array{
     *     current_version: string,
     *     latest_version: string|null,
     *     has_update: bool,
     *     release_url: string|null,
     *     checked_at: string,
     *     error: string|null
     * }
     */
    public function check(): array
    {
        $current = $this->getCurrentVersion();

        try {
            $response = $this->httpClient->request('GET', sprintf('https://api.github.com/repos/%s/releases/latest', $this->getGithubRepo()), [
                'headers' => [
                    'Accept' => 'application/vnd.github+json',
                    'User-Agent' => 'my-invoice-version-check',
                ],
                'timeout' => 8,
            ]);

            if ($response->getStatusCode() !== 200) {
                return $this->buildFallback($current, sprintf('GitHub API retornou status %d', $response->getStatusCode()));
            }

            $data = $response->toArray(false);
            $latest = isset($data['tag_name']) ? (string) $data['tag_name'] : null;
            $releaseUrl = isset($data['html_url']) ? (string) $data['html_url'] : null;

            if ($latest === null || $latest === '') {
                return $this->buildFallback($current, 'Tag da última release não encontrada.');
            }

            return [
                'current_version' => $current,
                'latest_version' => $latest,
                'has_update' => $this->isLatestGreater($latest, $current),
                'release_url' => $releaseUrl,
                'checked_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return $this->buildFallback($current, $e->getMessage());
        }
    }

    private function buildFallback(string $current, string $error): array
    {
        return [
            'current_version' => $current,
            'latest_version' => null,
            'has_update' => false,
            'release_url' => null,
            'checked_at' => (new \DateTimeImmutable())->format(DATE_ATOM),
            'error' => $error,
        ];
    }

    private function isLatestGreater(string $latest, string $current): bool
    {
        $latestNormalized = ltrim(trim($latest), 'vV');
        $currentNormalized = ltrim(trim($current), 'vV');

        if ($latestNormalized === '' || $currentNormalized === '') {
            return false;
        }

        return version_compare($latestNormalized, $currentNormalized, '>');
    }
}

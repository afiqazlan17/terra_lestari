<?php

namespace App\Services;

use Google\Auth\OAuth2;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class GoogleDriveBackupService
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    public function __construct(
        private readonly string $clientId,
        private readonly string $clientSecret,
        private readonly string $refreshToken,
        private readonly string $rootFolderId,
    ) {
    }

    /**
     * Upload a file (given a path on the `public` disk) into a dated subfolder
     * of the backup root, creating that subfolder if it doesn't exist yet.
     * Returns the created Drive file ID.
     */
    public function uploadReceipt(string $publicDiskPath, string $dateFolder, string $driveFileName): string
    {
        if (! Storage::disk('public')->exists($publicDiskPath)) {
            throw new RuntimeException("File not found on public disk: {$publicDiskPath}");
        }

        $folderId = $this->findOrCreateDateFolder($dateFolder);

        $contents = Storage::disk('public')->get($publicDiskPath);
        $mimeType = Storage::disk('public')->mimeType($publicDiskPath) ?: 'application/octet-stream';

        $metadata = json_encode([
            'name' => $driveFileName,
            'parents' => [$folderId],
        ]);

        $boundary = 'drive-backup-'.bin2hex(random_bytes(8));

        $body = "--{$boundary}\r\n".
            "Content-Type: application/json; charset=UTF-8\r\n\r\n".
            $metadata."\r\n".
            "--{$boundary}\r\n".
            "Content-Type: {$mimeType}\r\n\r\n".
            $contents."\r\n".
            "--{$boundary}--";

        $response = Http::withToken($this->accessToken())
            ->withBody($body, "multipart/related; boundary={$boundary}")
            ->timeout(30)
            ->post('https://www.googleapis.com/upload/drive/v3/files?uploadType=multipart&fields=id');

        if ($response->failed()) {
            throw new RuntimeException('Google Drive upload failed: '.$response->status().' '.$response->body());
        }

        return $response->json('id');
    }

    private function findOrCreateDateFolder(string $dateFolder): string
    {
        $safeName = str_replace("'", "\\'", $dateFolder);

        $query = http_build_query([
            'q' => "name = '{$safeName}' and mimeType = 'application/vnd.google-apps.folder' and '{$this->rootFolderId}' in parents and trashed = false",
            'fields' => 'files(id,name)',
        ]);

        $response = Http::withToken($this->accessToken())
            ->timeout(15)
            ->get("https://www.googleapis.com/drive/v3/files?{$query}");

        if ($response->failed()) {
            throw new RuntimeException('Google Drive folder lookup failed: '.$response->status().' '.$response->body());
        }

        $existing = $response->json('files');
        if (! empty($existing)) {
            return $existing[0]['id'];
        }

        $createResponse = Http::withToken($this->accessToken())
            ->timeout(15)
            ->post('https://www.googleapis.com/drive/v3/files?fields=id', [
                'name' => $dateFolder,
                'mimeType' => 'application/vnd.google-apps.folder',
                'parents' => [$this->rootFolderId],
            ]);

        if ($createResponse->failed()) {
            throw new RuntimeException('Google Drive folder creation failed: '.$createResponse->status().' '.$createResponse->body());
        }

        return $createResponse->json('id');
    }

    private function accessToken(): string
    {
        $oauth = new OAuth2([
            'clientId' => $this->clientId,
            'clientSecret' => $this->clientSecret,
            'refreshToken' => $this->refreshToken,
            'tokenCredentialUri' => self::TOKEN_URI,
        ]);

        $token = $oauth->fetchAuthToken();

        if (empty($token['access_token'])) {
            throw new RuntimeException('Failed to refresh Google Drive access token: '.json_encode($token));
        }

        return $token['access_token'];
    }
}

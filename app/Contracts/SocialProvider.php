<?php

namespace App\Contracts;

interface SocialMediaProvider
{
 // السماح بإضافة منصات أخرى

   public function getAuthUrl(string $clientId = null): string;
    public function getAccessToken(string $code): array;
    public function getUserPages(string $userToken): array; 
    public function post(string $token, string $pageId, array $data): string;
}
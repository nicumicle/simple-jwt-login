<?php

namespace SimpleJwtLoginTests\Unit\Helpers\Jwt;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Repositories\Wordpress\WordPressRepository;

class JwtKeyBase extends TestCase
{
    /**
     * @param array $settingsArray
     * @return SimpleJWTLoginSettings
     */
    public function getSettingsMock($settingsArray)
    {
        $wordPressDataMock = $this->createStub(WordPressRepository::class);
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settingsArray));

        return new SimpleJWTLoginSettings($wordPressDataMock);
    }
}

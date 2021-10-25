<?php

namespace SimpleJwtLoginTests\Helpers\Jwt;

use PHPUnit\Framework\TestCase;
use SimpleJWTLogin\Modules\SimpleJWTLoginSettings;
use SimpleJWTLogin\Modules\WordPressData;

class JwtKeyBase extends TestCase
{
    /**
     * @param array $settingsArray
     * @return SimpleJWTLoginSettings
     */
    public function getSettingsMock($settingsArray)
    {
        $wordPressDataMock = $this->getMockBuilder(WordPressData::class)
            ->disableOriginalConstructor()
            ->getMock();
        $wordPressDataMock->method('getOptionFromDatabase')
            ->willReturn(json_encode($settingsArray));

        return new SimpleJWTLoginSettings($wordPressDataMock);
    }
}

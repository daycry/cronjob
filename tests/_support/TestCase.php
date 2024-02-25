<?php

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;
use Codeigniter4\Settings\Config\Settings as SettingsConfig;
use Codeigniter4\Settings\Settings;

/**
 * @internal
 */
class TestCase extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        /** @var SettingsConfig $configSettings */
        $configSettings           = config('Settings');
        $configSettings->handlers = ['array'];
        $settings                 = new Settings($configSettings);
        Services::injectMock('settings', $settings);

        helper('setting');
    }
}

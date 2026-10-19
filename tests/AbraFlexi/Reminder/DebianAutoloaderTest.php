<?php

declare(strict_types=1);

/**
 * This file is part of the AbraFlexi Reminder package
 *
 * https://github.com/VitexSoftware/abraflexi-reminder
 *
 * (c) Vítězslav Dvořák <http://vitexsoftware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\AbraFlexi\Reminder;

use PHPUnit\Framework\TestCase;

/**
 * The packaged debian/autoload.php must load the Debian autoloader of every library
 * the sources use, otherwise the installed package fails at runtime (not in CI, where
 * Composer's vendor/autoload.php hides the problem).
 *
 * @no-named-arguments
 */
class DebianAutoloaderTest extends TestCase
{
    private const ROOT = __DIR__.'/../../..';

    public function testEaseHtmlAutoloaderIsLoaded(): void
    {
        $usesEaseHtml = false;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::ROOT.'/src')) as $file) {
            if ($file->getExtension() === 'php' && str_contains((string) file_get_contents($file->getPathname()), 'Ease\\Html\\')) {
                $usesEaseHtml = true;

                break;
            }
        }

        $this->assertTrue($usesEaseHtml, 'Precondition: sources use Ease\\Html');
        $this->assertStringContainsString(
            "require_once '/usr/share/php/EaseHtml/autoload.php';",
            (string) file_get_contents(self::ROOT.'/debian/autoload.php'),
            'debian/autoload.php must load EaseHtml, or e.g. RemindMailer fails with "Class Ease\\Html\\HtmlTag not found"',
        );
    }
}

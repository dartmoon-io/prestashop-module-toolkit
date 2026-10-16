<?php

declare(strict_types=1);

namespace Dartmoon\Tests;

use Dartmoon\TabManager\TabManager;
use PHPUnit\Framework\TestCase;
use Tab;

final class TabManagerTest extends TestCase
{
    protected function setUp(): void
    {
        Tab::$rows = Tab::$deleted = Tab::$failDelete = [];
        Tab::$failSave = null;
    }
    public function testInstallIsIdempotentAndDeletesChildrenFirst(): void
    {
        $manager = new TabManager();
        $module = new \Module();
        $tabs = [['class_name' => 'AdminParent'], ['class_name' => 'AdminChild', 'parent_class_name' => 'AdminParent']];
        self::assertTrue($manager->install($tabs, $module));
        self::assertTrue($manager->install($tabs, $module));
        self::assertCount(2, Tab::$rows);
        self::assertTrue(Tab::$rows[1]->active);
        self::assertSame(1, Tab::$rows[2]->id_parent);
        self::assertTrue($manager->uninstallForModule($module));
        self::assertSame(['AdminChild', 'AdminParent'], Tab::$deleted);
    }
    public function testFailedSaveRollsBackPersistedTabs(): void
    {
        Tab::$failSave = 'AdminFail';
        self::assertFalse((new TabManager())->install([['class_name' => 'AdminParent'], ['class_name' => 'AdminFail']], new \Module()));
        self::assertSame([], Tab::$rows);
        self::assertSame(['AdminFail', 'AdminParent'], Tab::$deleted);
    }
    public function testExceptionsAlsoRollback(): void
    {
        try {
            (new TabManager())->install([['class_name' => 'AdminParent'], ['class_name' => 'AdminChild', 'parent_class_name' => 'Missing']], new \Module());
            self::fail('Expected an exception');
        } catch (\InvalidArgumentException $exception) {
            self::assertSame([], Tab::$rows);
        }
    }
    public function testRollbackAttemptsAllTabsAndReportsCleanupFailures(): void
    {
        Tab::$failSave = 'AdminFail';
        Tab::$failDelete = ['AdminFail'];
        try {
            (new TabManager())->install([['class_name' => 'AdminParent'], ['class_name' => 'AdminFail']], new \Module());
            self::fail('Expected a rollback failure');
        } catch (\RuntimeException $exception) {
            self::assertStringContainsString('AdminFail', $exception->getMessage());
            self::assertSame(['AdminFail', 'AdminParent'], Tab::$deleted);
            self::assertCount(1, Tab::$rows);
        }
    }
    public function testCollisionDoesNotClaimOtherModulesTab(): void
    {
        $manager = new TabManager();
        $owner = new \Module();
        $owner->name = 'othermodule';
        $manager->install([['class_name' => 'AdminShared']], $owner);
        $this->expectException(\InvalidArgumentException::class);
        $manager->install([['class_name' => 'AdminShared']], new \Module());
    }
    public function testUninstallAttemptsRemainingTabsAfterFailure(): void
    {
        $manager = new TabManager();
        $tabs = [['class_name' => 'AdminOne'], ['class_name' => 'AdminTwo']];
        $manager->install($tabs, new \Module());
        Tab::$failDelete = ['AdminTwo'];
        self::assertFalse($manager->uninstall($tabs));
        self::assertSame(['AdminTwo', 'AdminOne'], Tab::$deleted);
        self::assertTrue($manager->uninstall([['class_name' => 'Absent']]));
    }
}

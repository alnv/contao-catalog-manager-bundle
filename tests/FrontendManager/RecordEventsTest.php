<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordCreatedEvent;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordUpdatedEvent;
use Alnv\ContaoCatalogManagerBundle\Event\FrontendManager\RecordVisibilityChangedEvent;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;
use PHPUnit\Framework\TestCase;

class RecordEventsTest extends TestCase
{
    public function testUpdatedEventReportsChangesWithoutTimestamp(): void
    {
        $event = new RecordUpdatedEvent(
            'cm_events', 5, new MemberContext(7, 'max'), 36,
            ['id' => '5', 'tstamp' => '100', 'name' => 'Alt', 'price' => '5', 'note' => null],
            ['id' => '5', 'tstamp' => '200', 'name' => 'Neu', 'price' => 5, 'note' => null, 'extra' => 'x'],
            ['name', 'price'],
        );

        $this->assertSame(['name', 'extra'], $event->getChangedFields());
        $this->assertSame(['old' => 'Alt', 'new' => 'Neu'], $event->getChanges()['name']);
        $this->assertSame(['old' => null, 'new' => 'x'], $event->getChanges()['extra']);
        $this->assertTrue($event->isChanged('name'));
        $this->assertFalse($event->isChanged('price'), '5 und "5" sind keine Änderung');
        $this->assertFalse($event->isChanged('tstamp'));
        $this->assertTrue($event->hasChanges());
        $this->assertSame(['name', 'price'], $event->getSubmittedFields());
        $this->assertSame(['cm_events', 5, 7, 'max', 36], [$event->getTable(), $event->getId(), $event->getMember()->id, $event->getMember()->username, $event->getModuleId()]);
    }

    public function testUpdatedEventWithoutChanges(): void
    {
        $row = ['id' => '5', 'name' => 'Gleich'];
        $event = new RecordUpdatedEvent('cm_events', 5, new MemberContext(7), 1, $row + ['tstamp' => '1'], $row + ['tstamp' => '2']);

        $this->assertFalse($event->hasChanges());
        $this->assertSame([], $event->getChangedFields());
    }

    public function testCreatedEventCopy(): void
    {
        $created = new RecordCreatedEvent('cm_events', 9, new MemberContext(7), 1, ['id' => '9']);
        $copied = new RecordCreatedEvent('cm_events', 10, new MemberContext(7), 1, ['id' => '10'], 9);

        $this->assertFalse($created->isCopy());
        $this->assertNull($created->getSourceId());
        $this->assertTrue($copied->isCopy());
        $this->assertSame(9, $copied->getSourceId());
    }

    public function testVisibilityEvent(): void
    {
        $event = new RecordVisibilityChangedEvent('cm_events', 5, new MemberContext(7), 1, false, 'invisible', ['invisible' => ''], ['invisible' => '1']);

        $this->assertFalse($event->isVisible());
        $this->assertSame('invisible', $event->getField());
        $this->assertSame('1', $event->getNewRecord()['invisible']);
    }
}

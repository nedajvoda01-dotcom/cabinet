<?php
// cabinet/tests/unit/backend/stateMachine.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Backend\Modules\Cards\CardsModel;

final class StateMachineTest extends TestCase
{
    public function testHappyPathTransitions(): void
    {
        $card = CardsModel::fromArray([
            'id' => 1,
            'status' => 'draft',
        ]);

        $this->assertSame('draft', $card->status);

        $card->status = 'parser_queued';
        $this->assertSame('parser_queued', $card->status);

        $card->status = 'parser_done';
        $this->assertSame('parser_done', $card->status);

        $card->status = 'photos_queued';
        $this->assertSame('photos_queued', $card->status);

        $card->status = 'photos_done';
        $this->assertSame('photos_done', $card->status);

        $card->status = 'export_queued';
        $this->assertSame('export_queued', $card->status);

        $card->status = 'export_done';
        $this->assertSame('export_done', $card->status);

        $card->status = 'publish_queued';
        $this->assertSame('publish_queued', $card->status);

        $card->status = 'published';
        $this->assertSame('published', $card->status);
    }

    public function testFailureStatesAllowed(): void
    {
        $card = CardsModel::fromArray(['id'=>1,'status'=>'parser_queued']);

        $card->status = 'parser_failed';
        $this->assertSame('parser_failed', $card->status);

        // retry path
        $card->status = 'parser_queued';
        $this->assertSame('parser_queued', $card->status);
    }

    public function testPhotosFailureState(): void
    {
        $card = CardsModel::fromArray(['id'=>1,'status'=>'photos_queued']);

        $card->status = 'photos_failed';
        $this->assertSame('photos_failed', $card->status);

        // retry allowed
        $card->status = 'photos_queued';
        $this->assertSame('photos_queued', $card->status);
    }

    public function testPublishFailureState(): void
    {
        $card = CardsModel::fromArray(['id'=>1,'status'=>'publish_queued']);

        $card->status = 'publish_failed';
        $this->assertSame('publish_failed', $card->status);

        // retry allowed
        $card->status = 'publish_queued';
        $this->assertSame('publish_queued', $card->status);
    }
}

<?php

namespace Alnv\ContaoCatalogManagerBundle\Tests\FrontendManager;

use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\AccessDeniedException;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\MemberContext;
use Alnv\ContaoCatalogManagerBundle\Library\FrontendManager\RecordScope;
use PHPUnit\Framework\TestCase;

final class RecordScopeTest extends TestCase
{
    public function testOwnerCondition(): void
    {
        $scope = $this->scope();

        $this->assertSame(['columns' => ['`cm_test`.`owner`=?'], 'values' => [7]], $scope->getConditions());
    }

    public function testNoRestrictionAtAllIsDenied(): void
    {
        $this->expectDenied(AccessDeniedException::RECORD);
        $this->scope(owner: null)->getConditions();
    }

    public function testOwnerWithoutMemberIdIsDenied(): void
    {
        $this->expectDenied(AccessDeniedException::RECORD);
        $this->scope(member: new MemberContext(0))->getConditions();
    }

    public function testMultilingualAddsBaseLanguage(): void
    {
        $scope = new RecordScope('cm_test', Fixtures::dca(['dataContainer' => 'Terminal42\DcMultilingualBundle\Driver']), Fixtures::config(), 'owner', Fixtures::member());

        $this->assertSame(['`cm_test`.`owner`=?', '`cm_test`.`lid`=0'], $scope->getConditions()['columns']);
    }

    public function testExpertFilterBindsInsertTagsAsParameters(): void
    {
        $scope = $this->scope(
            owner: null,
            restriction: ['type' => 'expert', 'wizard' => '', 'column' => 'category=?;title={{user::firstname}}', 'value' => 'a'],
            tags: ['{{user::firstname}}' => "Max' OR 1=1 --"],
        );

        $this->assertSame(
            ['columns' => ['category=?', 'title=?'], 'values' => ['a', "Max' OR 1=1 --"]],
            $scope->getConditions(),
            'Der aufgelöste Wert landet nie im SQL, sondern als Parameter'
        );
    }

    public function testExpertFilterResolvingEmptyIsDenied(): void
    {
        // Würde die Bedingung entfernt (wie in den Listen-Modulen), wären alle Datensätze sichtbar
        $this->expectDenied(AccessDeniedException::RECORD);

        $this->scope(
            owner: null,
            restriction: ['type' => 'expert', 'wizard' => '', 'column' => 'memberId={{user::id}}', 'value' => ''],
            tags: ['{{user::id}}' => ''],
        )->getConditions();
    }

    public function testExpertFilterWithEmptyPlaceholderValueIsDenied(): void
    {
        $this->expectDenied(AccessDeniedException::RECORD);

        $this->scope(owner: null, restriction: ['type' => 'expert', 'wizard' => '', 'column' => 'category=?', 'value' => ''])->getConditions();
    }

    public function testExpertFilterWithTwoPlaceholdersIsDenied(): void
    {
        $this->expectDenied(AccessDeniedException::RECORD);

        $this->scope(owner: null, restriction: ['type' => 'expert', 'wizard' => '', 'column' => 'category IN (?,?)', 'value' => 'a'])->getConditions();
    }

    public function testUserFilterConditions(): void
    {
        $result = $this->scope()->getUserFilterConditions(
            ['title' => 'Foo%_', 'category' => 'b', 'tags' => 'x', 'published' => '1', 'evil' => '1', 'image' => ['array']],
            ['title', 'category', 'tags', 'published', 'image'],
        );

        $this->assertSame([
            'LOWER(CAST(`cm_test`.`title` AS CHAR)) LIKE LOWER(?)',
            '`cm_test`.`category`=?',
            'FIND_IN_SET(?, `cm_test`.`tags`)',
            "`cm_test`.`published`='1'",
        ], $result['columns']);
        $this->assertSame(['%Foo\%\_%', 'b', 'x'], $result['values'], 'LIKE-Platzhalter des Benutzers werden maskiert');
        $this->assertSame(['title' => 'Foo%_', 'category' => 'b', 'tags' => 'x', 'published' => '1'], $result['active']);
    }

    public function testUserFilterUnpublished(): void
    {
        $result = $this->scope()->getUserFilterConditions(['published' => '0'], ['published']);

        $this->assertSame(["(`cm_test`.`published`='' OR `cm_test`.`published`='0' OR `cm_test`.`published` IS NULL)"], $result['columns']);
    }

    public function testUserFilterIgnoresOverlongValues(): void
    {
        $result = $this->scope()->getUserFilterConditions(['title' => str_repeat('a', 256)], ['title']);

        $this->assertSame([], $result['columns']);
    }

    public function testInvalidTableName(): void
    {
        $this->expectDenied(AccessDeniedException::TABLE);
        new RecordScope('cm_test`; DROP', Fixtures::dca(), Fixtures::config(), 'owner', Fixtures::member());
    }

    private function scope(?string $owner = 'owner', ?MemberContext $member = null, ?array $restriction = null, array $tags = []): RecordScope
    {
        $config = Fixtures::config(null !== $restriction ? ['restriction' => $restriction] : []);
        $replace = static fn (string $value): string => strtr($value, $tags);

        return new RecordScope('cm_test', Fixtures::dca(), $config, $owner, $member ?? Fixtures::member(), $replace);
    }

    private function expectDenied(string $level): void
    {
        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessageMatches('/\(' . preg_quote($level, '/') . '\)/');
    }
}

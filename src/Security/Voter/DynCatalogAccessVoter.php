<?php

namespace Alnv\ContaoCatalogManagerBundle\Security\Voter;

use Alnv\ContaoCatalogManagerBundle\Library\Catalog;
use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Contao\Input;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;


class DynCatalogAccessVoter extends AbstractDataContainerVoter
{
    public function __construct(private readonly AccessDecisionManagerInterface $accessDecisionManager)
    {
        //
    }

    public function supportsAttribute(string $attribute): bool
    {
        return true;
    }

    protected function getTable(): string
    {
        $strModule = Input::get('do') ?? '';

        if (!$strModule) {
            return '';
        }

        return (new Catalog($strModule))->getCatalog()['table'] ?? '';
    }

    public function supportsType(string $subjectType): bool
    {
        return true;
    }

    public function vote(TokenInterface $token, $subject, array $attributes): int
    {
        return self::ACCESS_GRANTED;
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        return true;
    }
}

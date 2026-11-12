<?php

namespace Alnv\ContaoCatalogManagerBundle\Library\FrontendManager;

use Contao\FrontendUser;
use Contao\System;

final class MemberContext
{
    public function __construct(
        public readonly int $id,
        public readonly string $username = '',
    )
    {
    }

    public static function fromSecurity(): ?self
    {
        $container = System::getContainer();

        if (!$container->get('contao.security.token_checker')->hasFrontendUser()) {
            return null;
        }

        $user = $container->get('security.helper')->getUser();

        if (!$user instanceof FrontendUser || !$user->id) {
            return null;
        }

        return new self((int)$user->id, (string)$user->username);
    }
}

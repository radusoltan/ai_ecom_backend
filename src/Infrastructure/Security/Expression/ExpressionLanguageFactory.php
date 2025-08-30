<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Uid\Uuid;

final class ExpressionLanguageFactory
{
    public function create(): ExpressionLanguage
    {
        $el = new ExpressionLanguage();

        $el->addFunction(new ExpressionFunction(
            'is_granted',
            fn($attribute, $subject = 'null') => sprintf('$auth_checker->isGranted(%s, %s)', $attribute, $subject),
            fn(array $variables, $attribute, $subject = null) => $variables['auth_checker']->isGranted($attribute, $subject)
        ));

        $el->addFunction(new ExpressionFunction(
            'tenant',
            fn($expr = 'true') => sprintf('$tenant_context->has() ? %s : false', $expr),
            fn(array $variables, $expr = true) => $variables['tenant_context']->has() ? $expr : false
        ));

        $el->addFunction(new ExpressionFunction(
            'feature',
            fn($name) => sprintf('$tenant_context->has() ? $feature_flags->enabled(%s, \\Symfony\\Component\\Uid\\Uuid::fromString($tenant_context->get()->toString())) : false', $name),
            fn(array $variables, $name) => $variables['tenant_context']->has()
                ? $variables['feature_flags']->enabled($name, Uuid::fromString($variables['tenant_context']->get()->toString()))
                : false
        ));

        return $el;
    }
}


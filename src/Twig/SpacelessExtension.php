<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

final class SpacelessExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('spaceless_custom', [$this, 'spaceless']),
        ];
    }
    
    public function spaceless(string $value): string
    {
        return preg_replace('#\s+#', ' ', $value);
    }

}
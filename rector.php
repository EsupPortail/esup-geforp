<?php
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\AnnotationToAttributeRector;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\Symfony\Rector\ClassMethod\RemoveServiceFromSensioRouteRector;
use Rector\Symfony\Rector\MethodCall\StringFormTypeToClassRector;
use Rector\Symfony\Set\SymfonyLevelSetList;
use Rector\Symfony\Set\SymfonySetList;
use Rector\Symfony\Set\SensiolabsSetList;


return static function (RectorConfig $rectorConfig): void {
// Définir les chemins à analyser (assurez-vous que ces chemins sont corrects)
$rectorConfig->paths([
__DIR__ . '/config',    // Configurations, si nécessaires
__DIR__ . '/node_modules', // Dossier node_modules, si pertinent
__DIR__ . '/public',  // Dossier public, à ajuster si nécessaire
__DIR__ . '/src',      // Dossier source de votre application
__DIR__ . '/tests',    // Dossier des tests
]);

    $rectorConfig->sets([
        DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES,
        DoctrineSetList::DOCTRINE_CODE_QUALITY,
        DoctrineSetList::DOCTRINE_COMMON_20,
        DoctrineSetList::DOCTRINE_DBAL_40,
        LevelSetList::UP_TO_PHP_82,
        PHPUnitLevelSetList::UP_TO_PHPUNIT_100,
        PHPUnitSetList::ANNOTATIONS_TO_ATTRIBUTES,
        PHPUnitSetList::PHPUNIT_100,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        SetList::CODE_QUALITY,
        SetList::CODING_STYLE,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::NAMING,
        SetList::PHP_82,
        SetList::PRIVATIZATION,
        SetList::TYPE_DECLARATION,
        SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES,
        SymfonySetList::SYMFONY_CODE_QUALITY,
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,

    ]);


// Appliquer directement les transformations pour Symfony et PHP 8.0, sans utiliser de sets

    $rectorConfig->ruleWithConfiguration(AnnotationToAttributeRector::class, [
            new AnnotationToAttribute('Symfony\Component\Routing\Annotation\Route'),
        ]);

// Exemple d'ajout d'autres règles spécifiques
// Ajoutez d'autres règles spécifiques pour PHP 8.3 ou autres transformations nécessaires
// Par exemple : transformer des propriétés typées en PHP 8.3, etc.
// $rectorConfig->ruleWithConfiguration(SomeOtherRector::class, ...);
};

<?php

declare(strict_types=1);

namespace Wwwision\TypesGlossary;

use InvalidArgumentException;
use Webmozart\Assert\Assert;
use Wwwision\Types\Parser;
use Wwwision\Types\Schema\EnumSchema;
use Wwwision\Types\Schema\IntegerSchema;
use Wwwision\Types\Schema\ListSchema;
use Wwwision\Types\Schema\LiteralBooleanSchema;
use Wwwision\Types\Schema\LiteralIntegerSchema;
use Wwwision\Types\Schema\LiteralStringSchema;
use Wwwision\Types\Schema\OptionalSchema;
use Wwwision\Types\Schema\Schema;
use Wwwision\Types\Schema\ShapeSchema;
use Wwwision\Types\Schema\StringSchema;

use function chr;
use function preg_replace;
use function strtolower;

final class GlossaryGenerator
{
    /**
     * @var array<string, array<class-string>>
     */
    private array $classNamesByGroup = [];

    public function registerClassNames(string $group, string ...$classNames): void
    {
        Assert::allClassExists($classNames);
        $this->classNamesByGroup[$group] = [...$this->classNamesByGroup[$group] ??= [], ...$classNames];
    }

    public function generate(): string
    {
        $result = '';
        foreach ($this->classNamesByGroup as $group => $classNames) {
            $result .= "# $group\n\n";
            foreach ($classNames as $className) {
                $schema = Parser::getSchema($className);
                $result .= "## " . self::typeLabel($schema) . "\n\n";
                if ($schema->getDescription() !== null) {
                    $result .= "_" . $schema->getDescription() . "_\n\n";
                }
                $result .= "### Schema\n\n";
                $result .= $this->renderSchema($schema);
            }
        }
        return $result;
    }

    private function renderSchema(Schema $schema): string
    {
        $type = match ($schema::class) {
            LiteralBooleanSchema::class => 'boolean',
            LiteralStringSchema::class, StringSchema::class => 'string',
            LiteralIntegerSchema::class, IntegerSchema::class => 'integer',
            EnumSchema::class => $schema->getBackingType() === 'int' ? 'integer' : $schema->getBackingType(),
            ListSchema::class => 'array',
            ShapeSchema::class => 'object',
            default => throw new InvalidArgumentException(sprintf('Unsupported schema type "%s"', get_debug_type($schema))),
        };
        $result = " * **type**: $type\n";
        $result .= match ($schema::class) {
            LiteralBooleanSchema::class, LiteralStringSchema::class, LiteralIntegerSchema::class => '',
            EnumSchema::class => $this->enumDetails($schema),
            IntegerSchema::class => $this->intDetails($schema),
            ListSchema::class => $this->listDetails($schema),
            ShapeSchema::class => $this->shapeDetails($schema),
            StringSchema::class => $this->stringDetails($schema),
            default => throw new InvalidArgumentException(sprintf('Unsupported schema type "%s"', get_debug_type($schema))),
        };
        $result .= chr(10);
        return $result;
    }

    private function enumDetails(EnumSchema $schema): string
    {
        $result = " * **enum**:\n";
        foreach ($schema->caseSchemas as $caseSchema) {
            $result .= "    * " . $caseSchema->getName();
            $caseDescription = $caseSchema->getDescription();
            if ($caseDescription !== null) {
                $result .= " _– ${caseDescription}_";
            }
            $result .= "\n";
        }
        return $result;
    }

    private function intDetails(IntegerSchema $schema): string
    {
        $result = '';
        if ($schema->minimum !== null) {
            $result .= " * **minimum**: $schema->minimum\n";
        }
        if ($schema->maximum !== null) {
            $result .= " * **maximum**: $schema->maximum\n";
        }
        return $result;
    }

    private function listDetails(ListSchema $schema): string
    {
        $result = " * **items.type**: " . self::linkType($schema->itemSchema) . "\n";
        if ($schema->minCount !== null) {
            $result .= " * **minItems**: $schema->minCount\n";
        }
        if ($schema->maxCount !== null) {
            $result .= " * **maxItems**: $schema->maxCount\n";
        }
        return $result;
    }

    private function shapeDetails(ShapeSchema $schema): string
    {
        $result = chr(10) . '#### Properties' . chr(10) . chr(10);
        foreach ($schema->propertySchemas as $propertyName => $propertySchema) {
            $result .= "* $propertyName (" . self::linkType($propertySchema) . ")";
            if ($propertySchema instanceof OptionalSchema) {
                $result .= ' (optional)';
            }
            $overriddenDescription = $schema->overriddenPropertyDescription($propertyName);
            if ($overriddenDescription !== null) {
                $result .= ' – _' . $overriddenDescription . '_';
            }
            $result .= "\n";
        }
        return $result;
    }

    private function stringDetails(StringSchema $schema): string
    {
        $result = '';
        if ($schema->minLength !== null) {
            $result .= " * **minLength**: $schema->minLength\n";
        }
        if ($schema->maxLength !== null) {
            $result .= " * **maxLength**: $schema->maxLength\n";
        }
        if ($schema->pattern !== null) {
            $result .= " * **pattern**: `$schema->pattern`\n";
        }
        if ($schema->format !== null) {
            $result .= " * **format**: `" . $schema->format->name . "`\n";
        }
        return $result;
    }

    private static function linkType(Schema $schema): string
    {
        $anchorLink = strtolower((string)preg_replace('/(?<!^)[A-Z]/', '-$0', $schema->getName()));
        return '[' . self::typeLabel($schema) . '](#' . $anchorLink . ')';
    }

    private static function typeLabel(Schema $schema): string
    {
        return (string)preg_replace('/(?<!^)[A-Z]/', ' $0', $schema->getName());
    }
}

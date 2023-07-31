<?php
declare(strict_types=1);

namespace Wwwision\TypesGLossary\Tests\PHPUnit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Wwwision\Types\Attributes\Description;
use Wwwision\Types\Attributes\IntegerBased;
use Wwwision\Types\Attributes\ListBased;
use Wwwision\Types\Attributes\StringBased;
use Wwwision\Types\Schema\StringTypeFormat;
use Wwwision\TypesGlossary\GlossaryGenerator;

#[CoversClass(GlossaryGenerator::class)]
final class GlossaryGeneratorTest extends TestCase
{

    public function test(): void
    {
        $generator = new GlossaryGenerator();
        $generator->registerClassNames('Group 01', SomeNumber::class, SomeNumbers::class, Date::class, SomeShape::class);
        $generator->registerClassNames('Group 02', SomeOtherShape::class, Title::class, Severity::class);

        $expectedGlossary = <<<GLOSSARY
            # Group 01
            
            ## Some Number
            
            _Some number description_
            
            ### Schema
            
             * **type**: integer
             * **minimum**: 3
             * **maximum**: 20
            
            ## Some Numbers
            
            ### Schema
            
             * **type**: array
             * **items.type**: [Some Number](#some-number)
             * **minItems**: 1
             * **maxItems**: 5
            
            ## Date
            
            _Some date description_
            
            ### Schema
            
             * **type**: string
             * **minLength**: 3
             * **maxLength**: 10
             * **pattern**: `\d{4}-\d{2}-\d{2}`
             * **format**: `date`
            
            ## Some Shape
            
            _Some shape description_
            
            ### Schema
            
             * **type**: object
            
            #### Properties
            
             * number ([Some Number](#some-number))
             * numbers ([Some Numbers](#some-numbers))
             * boolean ([boolean](#boolean)) (optional)
             * int ([int](#int)) (optional) – Overridden description
             * string ([string](#string)) (optional)
             * date ([Date](#date)) (optional)
             * nestedShape ([Some Other Shape](#some-other-shape)) (optional)
            
            # Group 02
            
            ## Some Other Shape
            
            ### Schema
            
             * **type**: object
            
            #### Properties
            
             * title ([Title](#title))
            
            ## Title
            
            ### Schema
            
             * **type**: string
             * **enum**:
                * MR
                * MRS
                * OTHER
            
            ## Severity
            
            ### Schema
            
             * **type**: integer
             * **enum**:
                * LOW
                * MEDIUM
                * HIGH _– Highest severity_


            GLOSSARY;

        self::assertSame($expectedGlossary, $generator->generate());
    }

}


#[IntegerBased(minimum: 3, maximum: 20)]
#[Description('Some number description')]
final class SomeNumber {
    private function __construct(public readonly int $value) {}
}

#[ListBased(itemClassName: SomeNumber::class, minCount: 1, maxCount: 5)]
final class SomeNumbers {
    private function __construct(private readonly array $numbers) {}
}

#[StringBased(minLength: 3, maxLength: 10, pattern: '\d{4}-\d{2}-\d{2}', format: StringTypeFormat::date)]
#[Description('Some date description')]
final class Date {
    private function __construct(public readonly string $value) {}
}

#[Description('Some shape description')]
final class SomeShape {
    public function __construct(
        public readonly SomeNumber $number,
        public readonly SomeNumbers $numbers,
        public readonly bool $boolean = false,
        #[Description('Overridden description')]
        public readonly ?int $int = null,
        public readonly ?string $string = null,
        public readonly ?Date $date = null,
        public readonly ?SomeOtherShape $nestedShape = null,
    ) {}
}

final class SomeOtherShape {
    public function __construct(
        public readonly Title $title,
    ) {}
}

enum Title {
    case MR;
    case MRS;
    case OTHER;
}

enum Severity: int {
    case LOW = 0;
    case MEDIUM = 1;
    #[Description('Highest severity')]
    case HIGH = 3;
}
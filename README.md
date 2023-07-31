# types-glossary

Simple integration for the wwwision/types package that allows to generate Markdown Glossary for all types

## Usage

This package can be installed via [composer](https://getcomposer.org):

```bash
composer require wwwision/types-glossary
```

To generate a Glossary, at least one class is required:

```php
#[StringBased(minLength: 1, maxLength: 200)]
final class Name {
    private function __construct(public readonly string $value) {}
}

#[IntegerBased(minimum: 1, maximum: 130)]
final class Age {
    private function __construct(public readonly int $value) {}
}

final class Contact {
    public function __construct(
        public readonly Name $name,
        public readonly Age $age,
    ) {}
}

#[ListBased(itemClassName: Contact::class, minCount: 1, maxCount: 5)]
final class Contacts {
    private function __construct(private readonly array $contacts) {}
}
```

Now, a glossary can be rendered via:

```php
// ...
$generator = new GlossaryGenerator();
$generator->registerClassNames('Group 01', Name::class, Age::class);
$generator->registerClassNames('Group 02', Contact::class, Contacts::class);

assert($generator->generate() === '# Group 01

## Name

### Schema

 * **type**: string
 * **minLength**: 1
 * **maxLength**: 200

## Age

### Schema

 * **type**: integer
 * **minimum**: 1
 * **maximum**: 130

# Group 02

## Contact

### Schema

 * **type**: object

#### Properties

 * name ([Name](#name))
 * age ([Age](#age))

## Contacts

### Schema

 * **type**: array
 * **items.type**: [Contact](#contact)
 * **minItems**: 1
 * **maxItems**: 5

');
```

See [GlossaryGeneratorTest.php](tests%2FPHPUnit%2FGlossaryGeneratorTest.php) for more examples

## Contribution

Contributions in the form of [issues](https://github.com/bwaidelich/types-glossary/issues) or [pull requests](https://github.com/bwaidelich/types-glossary/pulls) are highly appreciated

## License

See [LICENSE](./LICENSE)
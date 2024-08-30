
# Data Transfer Object for PHP 8

Easily manage Data Transfer Objects in PHP.

This DTO library, for PHP 8 and above, is designed to keep data consistent throughout your project thanks to strict type-hinted properties, nested data mapping and value manipulation.


## Authors

- Dom McLaughlin [@d3jmc](https://www.github.com/d3jmc)


## Installation

We recommend installing this library via Composer.

```bash
composer require d3jmc/data-transfer-object
```

Or, if you'd prefer, you can clone this repository into your project.


## Usage

Each developer and/or project will have their own preference in creating and managing DTOs. Please feel free to use whichever method you'd like. These are just guidelines.

In this example, we'll be creating a simple User DTO which will store all of the user's profile information, GDPR preferneces and roles.

#### Creating the User DTO Class

We first need to start out by creating a DTO class for the user. This class will contain all of the properties we want to store and any custom logic we need to use to modify incoming data.

```php
<?php

namespace App\Dto;

use D3jmc\DataTransferObject\DataTransferObject;

class UserDto extends DataTransferObject
{
    public ?string $firstName = null;
    public ?string $lastName = null;
}
```

Property names should always be camelCase. When populating data into the DTO, you can use snake_case as this will be converted automatically. If you are passing data into the DTO but the output is not what you're expecting, this is likely to be due to name mismatch.

#### Populating the User DTO Class

Populating data into a DTO class is really simple. You can either pass your data into the constructor or use the `fill` method. 

Please be aware that the data must be an array.

```php
<?php

$data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
];

// via the constructor
$userDto = new App\Dto\UserDto($data);

// via the `fill` method
$userDto = new App\Dto\UserDto();
$userDto->fill($data);
```

#### Outputting the User DTO Class properties

There are 2 methods you can use to return the properties inside the DTO class. These are `get` and `toArray`. The former will return an instance of the DTO class, whereas toArray will do what it says on the tin and convert the properties to an array.

```php
<?php

// App\Dto\UserDto {#293 ▼
//  +firstName: "John"
//  +lastName: "Doe"
// }
$userDto->get();

// array:5 [▼
//  "firstName" => "John"
//  "lastName" => "Doe"
// ]
$userDto->toArray();
```

#### Type-hinting another DTO class to a property

Let's say we want to store some GDPR preferences for the user. While we could create a separate property for each setting in the User DTO Class, it would be better to group them together in their own DTO class. Not only will this make it easier to pull out all of the GDPR properties in one go, we can also reuse this class elsewhere.

So, let's create a new DTO class for GDPR preferences.

```php
<?php

namespace App\Dto;

use D3jmc\DataTransferObject\DataTransferObject;

class UserGdprDto extends DataTransferObject
{
    public bool $email = false;
    public bool $post = false;
    public bool $sms = false;
}
```

Now, in the User DTO class, we need to create the property. Pay attention to the type-hint for the $gdpr property. This is neccessary so when it comes to populating the data, it will be automatically mapped to the UserGdprDto class.

```php
<?php

namespace App\Dto;

use D3jmc\DataTransferObject\DataTransferObject;

class UserDto extends DataTransferObject
{
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?UserGdprDto $gdpr = null;
}
```

Finally, in our data array, we can add the user's GDPR preferences.

```php
<?php

$data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'gdpr' => [
        'email' => true,
        'post' => false,
        'sms' => false,
    ],
];
```

The output would be something like:

```php
<?php

print_r($userDto->get(), true);

// App\Dto\UserDto {#293 ▼
//  +firstName: "John"
//  +lastName: "Doe"
//  +gdpr: App\Dto\UserGdprDto {#298 ▼
//    +email: true
//    +post: false
//    +sms: false
//  }
// }
```

#### Type-hinting another DTO class to a property as an array

In this example, we want to store the user's roles, however a user can have many roles so this property will need to be an array. Like in the previous example, we may reference roles elsewhere in the project, so it's better to create a separate UserRoleDto class.

```php
<?php

namespace App\Dto;

use D3jmc\DataTransferObject\DataTransferObject;

class UserRoleDto extends DataTransferObject
{
    public ?string $ident = null;
    public ?string $name = null;
    public ?string $description = null;
}
```

Now, in our main User DTO class, we'll need to create a property for roles. This will be slightly different to our `$gdpr` property as we need to create a doc comment to tell the library that each array should be an instance of the UserRoleDto class.

This must be specified as the full namespace.

```php
<?php

namespace App\Dto;

use D3jmc\DataTransferObject\DataTransferObject;

class UserDto extends DataTransferObject
{
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?UserGdprDto $gdpr = null;

    /**
     * @var array<App\Dto\UserRoleDto>
     */
    public array $roles = [];
}
```

In our data array, we can now add the roles.

```php
<?php

$data = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'gdpr' => [
        'email' => true,
        'post' => false,
        'sms' => false,
    ],
    'roles' => [
        [
            'ident' => '*',
            'name' => 'Super Admin',
            'description' => 'Unrestricted access to the system',
        ],
    ],
];
```

The output would be something like:

```php
<?php

print_r($userDto->get(), true);

// App\Dto\UserDto {#293 ▼
//  +firstName: "John"
//  +lastName: "Doe"
//  +gdpr: App\Dto\UserGdprDto {#298 ▼
//    +email: true
//    +post: false
//    +sms: true
//  }
//  +roles: array:1 [▼
//    0 => App\Dto\UserRoleDto {#300 ▼
//      +ident: "*"
//      +name: "Super Admin"
//      +description: "Unrestricted access to the system"
//    }
//  ]
// }
```

This functionality will work for multi-nested arrays too, so say you have a permissions array inside of roles, just repeat this process.

#### Mapping Data

In some cases, the data you are sending to the DTO class won't match the properties you have set up. A common example is when processing data from an API. In your project, you refer to a user's last name as 'last_name', whereas the API refers to it as 'surname'. This can be easily solved by creating a map.

```php
<?php

$apiData = [
    'first_name' => 'John',
    'surname' => 'Doe',
];

$userDto = new App\Dto\UserDto($apiData);
```

The output of the above would return John as the first name, but null as the last name as our DTO class doesn't have a property for 'surname'.

A map is just a key value array, where the key is the property name in your DTO class and the value is the key of the data you want to use.

```php
<?php

$map = [
    // last_name is our DTO property
    // surname is what is returned by the API
    'last_name' => 'surname',  
];
```

Your DTO class can either take a map through its constructor or by using the `map` function. If you choose to use the function, please ensure it is triggered before the `fill` function, otherwise the mappings won't work.

```php
<?php

// via the constructor
$userDto = new App\Dto\UserDto($apiData, $map);

// via the `map` method
$userDto = new App\Dto\UserDto();
$userDto->map($map);
$userDto->fill($apiData);
```

Mappings work for type-hinted DTO properties too.

```php
<?php

$apiData = [
    'gdpr' => [
        'by_email' => true,
        'by_post' => false,
        'by_sms' => false,
    ],
    'roles' => [
        'id' => '*',
    ],
];

$map = [
    'gdpr' => [
        'email' => 'by_email',
        'post' => 'by_post',
        'sms' => 'by_sms',
    ],
    'roles' => [
        'ident' => 'id',  
    ],
];

$userDto = new App\Dto\UserDto($apiData, $map);
```

#### Manipulating Data

Sometimes, the data you receive in your DTO class isn't in the format you'd like, or perhaps you want to create a fallback if the value is null, or even populate a whole new property based on other property values.

This can be achieved by using the magic set function in your DTO class. You can override any property value by calling `setPropertyName()`. The function will include a default value parameter.

In this example, we want to create a `displayName` property that will use the value of `firstName` and `lastName`, or the original `displayName` value if one is passed in with the data.

```php
<?php

namespace App\Dto;

use D3jmc\DataTransferObject\DataTransferObject;

class UserDto extends DataTransferObject
{
    public ?string $firstName = null;
    public ?string $lastName = null;
    public ?string $displayName = null;

    public function setDisplayName(string $displayName = null): void
    {
        $this->displayName = $displayName ?: "{$this->firstName} {$this->lastName}";
    }
}
```

Another example is in our UserRoleDto class, we want to specify whether they are a super user.

```php
<?php

namespace App\Dto;

use D3jmc\DataTransferObject\DataTransferObject;

class UserRoleDto extends DataTransferObject
{
    public ?string $ident = null;
    public ?bool $isSu = false;

    public function setIsSu(bool $isSu = false): void
    {
        $this->isSu = ($isSu ?: (bool) ($this->ident === '*'));
    }
}
```


## Contributing

Contributions are always welcome!

Please submit your PR and we will review it as soon as possible.


## License

[MIT](https://choosealicense.com/licenses/mit/)
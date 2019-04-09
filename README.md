# Booking bundle
## Installation
```
composer req comsa/booking-bundle dev-master
```

Add to AppKernel.php to bundles []

```
new \Comsa\BookingBundle\ComsaBookingBundle(),
new \JMS\SerializerBundle\JMSSerializerBundle()
```

Add following to top of routing.yaml:

```
comsa_booking:
  resource: '@ComsaBookingBundle/Resources/config/routes.yaml'
```

## Installation client
Install the npm package (local only) http://lab.comsa.be/ciryk/booking-bundle-vue
```
npm install <path-to-package> 
```

Add following entries to webpack:
```
'booking_admin': './node_modules/booking-bundle-vue/js/admin/main.js',
'booking_front': './node_modules/booking-bundle-vue/js/front/main.js'
```

In parameters.yml, add a parameter called `theme.assets_url`, which will point to the assets folder:

```
theme.assets_url: 'src/Frontend/Themes/Comsa/Build/'
```

Update the database
```
bin/console doctrine:schema:update -f
```

Enable the security bundle by requiring it and enable it in AppKernel
```
new \Symfony\Bundle\SecurityBundle\SecurityBundle()
```

And add the following to config.yml or security.yml
```
security:
    encoders:
        Symfony\Component\Security\Core\User\User: plaintext
    providers:
        in_memory:
            memory:
                users:
                    admin:
                        password: booking
                        roles: 'ROLE_ADMIN'
    firewalls:
        dev:
            pattern: ^/(_(profiler|wdt)|css|images|js)/
            security: false

        main:
            anonymous: ~
            http_basic: ~

    access_control:
        - { path: ^/booking/admin, roles: ROLE_ADMIN }
```

## Security
```
composer req lexik/jwt-authentication-bundle
```
```
new \Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle()
```
Generate SSH keys
```
mkdir -p config/jwt # For Symfony3+, no need of the -p option
openssl genrsa -out config/jwt/private.pem -aes256 4096
openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
```
Configure the SSH keys path in your config/packages/lexik_jwt_authentication.yaml :
```
lexik_jwt_authentication:
    secret_key:       '%kernel.project_dir%/config/jwt/private.pem' # required for token creation
    public_key:       '%kernel.project_dir%/config/jwt/public.pem'  # required for token verification
    pass_phrase:      'your_secret_passphrase' # required for token creation, usage of an environment variable is recommended
    token_ttl:        3600
```

Add to .htaccess
```
RewriteCond %{HTTP:Authorization} .
RewriteRule ^ - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

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

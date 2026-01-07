# Migrating to v8.1

This guide explains how to upgrade your application when updating CalendarBundle to the version 8.1.

## What Changed

The bundle has been modernized to follow Symfony 8 best practices:

| Before | After |
|--------|-------|
| `src/CalendarBundle.php` extends `Bundle` | Extends `AbstractBundle` |
| `src/DependencyInjection/CalendarExtension.php` | Removed (merged into bundle) |
| `src/DependencyInjection/Configuration.php` | Removed |
| `src/Resources/config/services.yaml` | `config/services.yaml` |
| `src/Resources/config/routing.yaml` | `config/routing.yaml` |
| `src/Resources/views/` | `templates/` |
| `src/Resources/doc/` | `docs/` |
| `tests/DependencyInjection/CalendarExtensionTest.php` | `tests/DependencyInjection/CalendarBundleTest.php` |

### New Service Aliases

Short service aliases have been added for convenience:

| Class Name | Alias |
|------------|-------|
| `CalendarBundle\Serializer\Serializer` | `calendar.serializer` |
| `CalendarBundle\Controller\CalendarController` | `calendar.controller` |

Both naming conventions are supported.

## Breaking Changes

### 1. Routing Configuration Path

If you import the bundle's routing manually, update the path:

**Before:**
```yaml
# config/routes/calendar.yaml
calendar:
    resource: '@CalendarBundle/Resources/config/routing.yaml'
```

**After:**
```yaml
# config/routes/calendar.yaml
calendar:
    resource: '@CalendarBundle/config/routing.yaml'
```

### 2. Template Path (if overriding)

If you override the bundle's templates in your application, no change is required. Symfony automatically resolves the correct location.

## Migration Steps

1. **Update the bundle** via Composer:
   ```bash
   composer update tattali/calendar-bundle
   ```

2. **Update routing import** in `config/routes/calendar.yaml` (if using manual import):
   ```yaml
   calendar:
       resource: '@CalendarBundle/config/routing.yaml'
   ```

3. **Clear cache**:
   ```bash
   php bin/console cache:clear
   ```

## No Changes Required

The following remain unchanged and fully backward compatible:

- **Service IDs** - `CalendarBundle\Serializer\Serializer`, `CalendarBundle\Controller\CalendarController`, and `CalendarBundle\Serializer\SerializerInterface` alias
- **Event subscriber pattern** - Your `SetDataEvent` subscribers work exactly the same
- **API endpoint** - `/fc-load-events` route and behavior unchanged
- **Event entity** - `CalendarBundle\Entity\Event` unchanged
- **Frontend integration** - No JavaScript changes needed

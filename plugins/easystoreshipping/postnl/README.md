# PostNL Shipping Plugin for Joomla 6 / EasyStore

Production-ready PostNL shipping integration for EasyStore with automated label creation, tracking, and customer notifications.

## Version
1.0.0 (2025-10-26)

## Features

✅ **Label Creation**
- Create PostNL shipments directly from order admin
- Automatic or manual shipment confirmation
- Support for PDF and ZPL label formats
- Test mode with mock labels for development

✅ **Track & Trace**
- Automatic T&T URL generation
- Barcode saved to order
- Tracking URL with customizable language (NL/EN/DE/FR)

✅ **Email Notifications**
- Optional automatic tracking emails to customers
- Customizable email templates via language files
- Includes order number, barcode, and tracking link

✅ **Admin Integration**
- Admin action button in order details
- Clear success/error messaging
- Comprehensive logging

✅ **Joomla 6 Compliance**
- Modern namespaces (PlgEasystoreshippingPostnl)
- Dependency Injection via services/provider.php
- Strict typing throughout
- PSR-12 coding standards

## File Structure

```
plugins/easystoreshipping/postnl/
├── postnl.php                      # Main plugin file
├── postnl.xml                      # Plugin manifest
├── services/
│   └── provider.php                # DI container registration
├── src/
│   ├── PostnlClient.php           # PostNL API client
│   └── OrderHelper.php            # Order mapping & utilities
├── language/
│   ├── en-GB/
│   │   ├── plg_easystoreshipping_postnl.ini
│   │   └── plg_easystoreshipping_postnl.sys.ini
│   └── nl-NL/
│       ├── plg_easystoreshipping_postnl.ini
│       └── plg_easystoreshipping_postnl.sys.ini
└── assets/
    └── images/
        └── logo.svg                # PostNL logo
```

## Installation

### Method 1: Fresh Install
1. Create ZIP file of the `postnl` directory
2. Go to Extensions → Install
3. Upload the ZIP file
4. Enable the plugin in Extensions → Plugins

### Method 2: Discover (if already in place)
1. Go to Extensions → Discover
2. Click "Discover"
3. Find "PostNL" and click Install
4. Enable the plugin

## Configuration

Navigate to Extensions → Plugins → PostNL Shipping

### API Configuration
- **Test Mode**: Enable to generate mock labels without API calls
- **API Base URL**: https://api.postnl.nl (default)
- **Auth Type**: Choose `apikey` or `bearer` based on your contract
- **API Key**: Your PostNL API key or bearer token
- **Customer Code**: Your PostNL customer code
- **Customer Number**: Your PostNL customer number

### Shipper Information
Configure your company's return address:
- Company Name
- Street, House Number, Suffix
- Zipcode, City, Country Code
- Email, Phone

### Shipment Settings
- **Product Code**: Default 3085 (standard NL delivery)
- **Label Format**: PDF or ZPL
- **Auto Confirm**: Automatically confirm shipments (recommended)
- **Default Weight**: Fallback weight in grams if not in order

### Tracking & Email
- **Auto Send Tracking**: Send email to customer automatically
- **Tracking Lang**: Language for T&T link (NL/EN/DE/FR)

## Usage

### Creating a Shipment

1. Go to EasyStore → Orders
2. Open an order in edit mode
3. Click "PostNL: Create Label + T&T" button
4. The plugin will:
   - Build shipment payload from order data
   - Call PostNL API to create shipment
   - Confirm shipment (if auto-confirm enabled)
   - Download and save label (PDF/ZPL)
   - Save tracking number and URL to order
   - Send tracking email (if auto-send enabled)
   - Show success message with barcode and T&T link

### Test Mode

When test mode is enabled:
- No actual API calls are made
- Mock response with test barcode (3STEST + 9 digits)
- Mock label file is created
- Perfect for development and testing

### Accessing Labels

Labels are saved to:
```
/media/com_easystore/postnl/{orderId}/{barcode}.pdf
```

## API Integration

### PostNL Shipping API v2

The plugin uses PostNL's Shipping API v2 with the following endpoints:

**Create Shipment**
```
POST /v2/shipment
```

**Confirm Shipment** (if auto-confirm enabled)
```
POST /v2/shipment/confirm
```

### Address Parsing

The plugin automatically parses Dutch addresses:
- Input: "Hoofdstraat 123 A"
- Parsed: Street="Hoofdstraat", HouseNr="123", HouseNrExt="A"

### Weight Calculation

Priority order:
1. Sum of order item weights × quantities
2. Default weight from plugin config
3. Fallback: 1000 grams

### Track & Trace URL Format

```
https://jouw.postnl.nl/track-and-trace/?B={barcode}&P={postcode}&D={country}&L={lang}&T=C
```

Parameters:
- **B**: Barcode
- **P**: Postcode (no spaces, uppercase)
- **D**: ISO2 country code
- **L**: Language (NL/EN/DE/FR)
- **T**: Type (C = Consumer)

## Logging

All operations are logged to:
```
logs/plg_easystoreshipping_postnl.php
```

Log levels:
- **INFO**: Successful operations
- **WARNING**: Non-critical issues
- **ERROR**: Failures and exceptions
- **DEBUG**: API requests/responses

## Error Handling

The plugin handles errors gracefully:
- API errors are parsed and displayed to admin
- Failed requests are logged with details
- User receives clear error message
- Order is not modified on failure

## Email Template

Customize email content in language files:

```ini
PLG_EASYSTORESHIPPING_POSTNL_EMAIL_SUBJECT="Your order %s has been shipped!"
PLG_EASYSTORESHIPPING_POSTNL_EMAIL_BODY="Your order %s has been shipped via PostNL....."
```

Variables:
- `%s` (first): Order number
- `%s` (second): Barcode
- `%s` (third): Tracking URL

## Troubleshooting

### Plugin not visible in EasyStore

1. Check cache: `/cache/easystore/shipping_carriers.json`
2. Ensure PostNL is in the schema
3. Clear Joomla cache
4. Hard refresh browser (Ctrl+Shift+R)

### API Errors

Enable test mode to verify plugin works correctly, then:
1. Verify API credentials are correct
2. Check auth type matches your contract
3. Review logs for detailed error messages
4. Ensure customer code/number are valid

### Labels not generating

1. Check label format (PDF/ZPL) matches your printer
2. Verify media directory is writable: `/media/com_easystore/postnl/`
3. Check logs for file write errors

### No tracking email sent

1. Verify "Auto Send Tracking" is enabled
2. Check customer has email address in order
3. Verify Joomla mail configuration
4. Check logs for email errors

## Development

### Adding Custom Fields

Edit `postnl.xml` to add configuration fields.

### Modifying Payload

Edit `OrderHelper::buildPostnlPayload()` to customize shipment data.

### Custom Email Template

Override language strings or create custom mailer in `OrderHelper::sendTrackingEmail()`.

## Support

For issues or questions:
1. Check logs: `logs/plg_easystoreshipping_postnl.php`
2. Enable test mode to isolate issues
3. Review PostNL API documentation
4. Contact your PostNL account manager for API issues

## Credits

- Built for EasyStore (JoomShaper)
- Joomla 6 compatible
- PostNL Shipping API v2

## License

GNU General Public License version 3

## Changelog

### 1.0.0 (2025-10-26)
- Initial production release
- Full PostNL Shipping API v2 integration
- Label creation (PDF/ZPL)
- Track & Trace URL generation
- Automatic email notifications
- Test mode support
- NL + EN language support
- Comprehensive logging

# Thermal Printer Design Guide - RetailNova

## Overview
A professional thermal printer receipt template has been created for RetailNova to generate invoice prints optimized for thermal printers (80mm and 58mm widths).

## Features

✅ **Professional Layout** - Matches the design from your reference image
✅ **Multiple Thermal Sizes** - Supports 80mm and 58mm thermal printers  
✅ **Auto-Print** - Automatically opens print dialog on page load
✅ **Currency Support** - Uses your configured currency symbol and position
✅ **Complete Invoice Data** - Shows all invoice details, items, totals, payments
✅ **Responsive Design** - Optimized for thermal printer paper width
✅ **Dashed Separators** - Professional receipt-style dashed lines
✅ **Print-Ready CSS** - Proper margins and page sizing for thermal printers

## File Locations

| File | Purpose |
|------|---------|
| `resources/views/invoice/thermalPrint.blade.php` | Thermal printer template |
| `app/Http/Controllers/invoiceController.php` | Updated controller with thermal print method |
| `routes/web.php` | New route for thermal printer |

## How to Use

### Method 1: Direct URL
```
/invoice/thermal-print?id={invoice_id}
```

Example:
```
http://your-domain.com/invoice/thermal-print?id=5
```

### Method 2: From Invoice Page
Add a button to your invoice page that links to:
```blade
<a href="{{ route('invoiceThermalPrint', ['id' => $invoice->id]) }}" 
   class="btn btn-sm btn-secondary" 
   target="_blank">
   <i class="fas fa-print"></i> Thermal Print
</a>
```

### Method 3: From Blade Template
```blade
@if(auth('admin')->check())
    <a href="{{ route('invoiceThermalPrint') }}?id={{ $invoice->id }}" 
       class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-print"></i> Thermal Receipt
    </a>
@endif
```

## Design Details

### Receipt Structure
```
┌─────────────────────────────────┐
│    Business Header Section      │
│  - Business Name                │
│  - Address                      │
│  - Phone                        │
├─────────────────────────────────┤
│    Invoice Information          │
│  - Invoice #, Date              │
│  - Customer, Mobile             │
│  - Cashier, Status              │
├─────────────────────────────────┤
│    Items Table                  │
│  - Item | Qty | Rate | Amount   │
│  - Product details with codes   │
├─────────────────────────────────┤
│    Totals Section               │
│  - Subtotal                     │
│  - Additional Charges (if any)  │
│  - Discount (if any)            │
│  - Grand Total                  │
│  - Paid Amount                  │
│  - Due Amount                   │
├─────────────────────────────────┤
│    Notes & Footer               │
│  - Special notes (if any)       │
│  - "Thank you for business"     │
│  - "Powered by RetailNova"      │
└─────────────────────────────────┘
```

### Printer Specifications

#### 80mm Thermal Printer (Standard)
- Width: 80mm
- Font: Monospace (Courier New)
- Font Size: 8px - 14px
- Character Limit: ~40-45 characters

#### 58mm Thermal Printer (Compact)
- Width: 58mm
- Font: Monospace (Courier New)  
- Font Size: 7px - 12px
- Character Limit: ~30-35 characters

## Customization

### Change Business Name/Details
The template automatically pulls from your BusinessSetup model:
```php
$b = $business ?? \App\Models\BusinessSetup::orderBy('id','desc')->first();
```

Edit from Admin Panel → Business Setup

### Modify Font Sizes
Edit in `thermalPrint.blade.php`:
```css
.business-name { font-size: 14px; }     /* Header business name */
.info-section { font-size: 8px; }       /* Invoice details */
.item-name { font-size: 8px; }          /* Product name */
.receipt-footer { font-size: 8px; }     /* Footer text */
```

### Add Custom Footer Text
Look for this section in the template:
```blade
<!-- Footer -->
<div class="receipt-footer">
    <div class="footer-text">Thank you for your business</div>
    <div class="footer-text">Powered by RetailNova</div>
</div>
```

### Change Divider Style
```css
.divider {
    border-top: 1px dashed #000;  /* Change to: solid, dotted, double */
    margin: 2mm 0;
}
```

## Browser Printing

### Chrome/Chromium
1. Open the thermal print page
2. Print dialog opens automatically
3. Settings:
   - Orientation: Portrait
   - Margins: None
   - Scale: 100%
   - Destination: Your thermal printer

### Firefox
1. Open the thermal print page
2. File → Print or Ctrl+P
3. Settings:
   - Orientation: Portrait  
   - Margins: None
   - Scale: 100%

### Safari/Mac
1. Command+P to print
2. Settings:
   - Orientation: Portrait
   - Margins: None
   - Scale: 100%

## Troubleshooting

### Issue: Text is cut off
**Solution:** Reduce font sizes in CSS or use 80mm printer instead of 58mm

### Issue: Print dialog doesn't appear automatically
**Solution:** Check browser print settings; some may block auto-print for security

### Issue: Currency symbol not showing
**Solution:** Verify BusinessSetup currency settings are configured correctly

### Issue: Items overflow to multiple pages
**Solution:** Use 80mm printer width or reduce font size

## Integration with Invoice Page

To add a thermal printer button to your existing invoice page:

1. Find `resources/views/invoice/invoicePage.blade.php`
2. Add this button in the action buttons section:
```blade
<a href="{{ route('invoiceThermalPrint', ['id' => $invoice->id]) }}" 
   class="btn btn-sm btn-secondary" 
   target="_blank">
   <i class="las la-print"></i> Thermal Print
</a>
```

## Route Information

### Route Name
`invoiceThermalPrint`

### Route URL
`/invoice/thermal-print`

### Route Parameters
- `id` (required) - The invoice ID to print

### Access Control
- Admin users: Can print any invoice
- Sales staff: Can only print their own invoices
- Unauthenticated: Access denied

## Variables Available in Template

| Variable | Description |
|----------|-------------|
| `$invoice` | SaleProduct model with invoice details |
| `$items` | Collection of InvoiceItem records |
| `$customer` | Customer model with customer details |
| `$b` | BusinessSetup model with company details |
| `$paymentStatus` | Current payment status (PAID/DUE/PARTIAL) |
| `$thermalSubtotal` | Calculated subtotal |
| `$thermalAddCharge` | Additional charges amount |
| `$thermalDiscount` | Discount amount |

## Performance Considerations

- Template uses minimal JavaScript (auto-print only)
- All calculations done server-side
- No external dependencies or AJAX calls
- Fast rendering and printing
- Suitable for high-volume printing

## Security

- Invoice access restricted to authorized users only
- Admin users can view all invoices
- Sales staff restricted to their own invoices
- Uses standard Laravel authentication

## Future Enhancements

Consider adding:
- QR code with invoice URL
- Barcode for item tracking
- Multiple language support
- Tax calculation breakdown
- Payment method details
- Signature line for delivery confirmation
- Custom header logo support
- Customizable footer messages

## Support

For issues or questions:
1. Check this guide's Troubleshooting section
2. Verify BusinessSetup configuration
3. Check browser console for errors
4. Review Laravel logs in `storage/logs/`

---

**Version:** 1.0  
**Last Updated:** March 26, 2026  
**Compatible With:** RetailNova, Laravel 10+

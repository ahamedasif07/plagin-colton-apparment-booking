# Appartali Booking Plugin
**Version:** 1.0.0 | **WordPress:** 5.8+ | **PHP:** 7.4+

---

## 📦 Installation

1. Upload the `appartali-booking` folder to `/wp-content/plugins/`
2. Activate the plugin in **WordPress Admin → Plugins**
3. On activation, the plugin automatically:
   - Creates the `wp_appt_bookings` database table
   - Registers the **Apartment** custom post type
   - Registers the **Room Categories** taxonomy
   - Flushes rewrite rules (permalinks)

---

## 🏠 Adding Apartments

1. Go to **WordPress Admin → Apartments → Add New**
2. Fill in:
   - **Title** – apartment name
   - **Description** (editor) – "About this place" text
   - **Featured Image** – main photo
   - **Apartment Details** meta box:
     - Price Per Night, Location, Room ID, Rating, Room Type, Max Guests, Cleaning Fee, Service Fee
   - **Gallery Images** – additional photos (clickable on single page)
   - **Amenities** – tick available facilities
   - **Host Information** – host name, photo, stats, superhost status
3. Set **Room Type** (used for category tab filtering)
4. **Publish** the post

---

## 🔧 Shortcode

Place this shortcode anywhere on a page or post:

```
[explore_rooms]
```

### Options:
| Parameter | Default | Description |
|-----------|---------|-------------|
| `limit`   | `8`     | Number of rooms to display |
| `columns` | `4`     | Grid columns (CSS handles responsive breakpoints) |

**Example:**
```
[explore_rooms limit="12"]
```

The section includes:
- Dark-themed card grid matching your design reference
- **All Category / Apartment / Studio / Villa / House / Cottage** filter tabs
- AJAX filtering (no page reload)
- **Browse More** button linking to the apartment archive

---

## 📋 Booking Flow

### Frontend (Guest):
1. Guest visits a single apartment page
2. Selects **Check-In** and **Check-Out** dates
3. Widget shows real-time availability check + price breakdown
4. Guest clicks **Reserve** → booking popup appears
5. Guest fills: Full Name, Email, Phone, Special Requests
6. Guest clicks **Confirm Booking**
7. Booking saved as **Pending**
8. **Guest receives email:** "Booking Request Received – Pending"
9. **Admin receives email:** "New Booking Request" with full details + link to manage

### Admin:
1. Go to **WordPress Admin → Bookings**
2. See all bookings in a table with filters (status, search by name/email)
3. Click **Confirm** → status changes to **Confirmed** → guest notified by email → dates blocked
4. Click **Cancel** → status changes to **Cancelled** → guest notified by email
5. Click **View** → modal shows full booking details

### Availability Logic:
- Only **Confirmed** bookings block dates
- **Pending** bookings do NOT block dates (allows admin to manually confirm)
- When admin confirms, those dates become unavailable to new bookers
- Frontend checks availability in real-time via AJAX

---

## ⚙️ Settings

Go to **WordPress Admin → Bookings → Settings** to configure:
- **Notification Email** – which email receives admin notifications
- **Currency Symbol** – default `$`

---

## 📧 Email Notifications

All emails use a dark-themed HTML template.

| Trigger | Recipient | Subject |
|---------|-----------|---------|
| New booking submitted | Guest | "Booking Request Received – #ID" |
| New booking submitted | Admin | "New Booking Request #ID" |
| Admin confirms booking | Guest | "✅ Booking Confirmed – #ID" |
| Admin cancels booking  | Guest | "Booking Cancelled – #ID" |

Emails are sent via `wp_mail()`. For reliable delivery, install an SMTP plugin (e.g., WP Mail SMTP, FluentSMTP).

---

## 🎨 Customization

### CSS Variables (frontend.css):
```css
:root {
  --appt-bg:     #0a0a0a;   /* page background */
  --appt-card:   #111111;   /* card background */
  --appt-gold:   #f5c842;   /* accent color */
  --appt-muted:  #999999;   /* secondary text */
}
```

Override in your theme's `style.css` or the WordPress Customizer.

---

## 🗂 File Structure

```
appartali-booking/
├── appartali-booking.php        ← Main plugin file
├── includes/
│   ├── class-cpt.php            ← Custom Post Type + Meta Boxes
│   ├── class-shortcode.php      ← [explore_rooms] shortcode
│   ├── class-booking.php        ← AJAX booking handlers
│   ├── class-email.php          ← Email notifications
│   └── class-admin.php          ← Admin bookings panel
├── templates/
│   └── single-apartment.php    ← Single room page template
└── assets/
    ├── css/frontend.css         ← Frontend dark theme styles
    ├── js/frontend.js           ← Frontend interactions + AJAX
    └── admin/
        ├── admin.css            ← Admin panel styles
        └── admin.js             ← Admin panel JS
```

---

## 🔌 Compatible Plugins

- **WP Mail SMTP / FluentSMTP** – for reliable email delivery
- **Yoast SEO / Rank Math** – works with Apartment CPT
- **Elementor / Divi** – use shortcode widget for the explore rooms grid
- **WPML / Polylang** – CPT is registered with standard WordPress APIs

---

## ❓ FAQ

**Q: The single apartment page is blank / shows wrong template.**  
A: After activating the plugin, go to **Settings → Permalinks** and click **Save Changes** to flush rewrite rules.

**Q: Emails are not being received.**  
A: WordPress `wp_mail()` depends on your server's mail configuration. Install WP Mail SMTP and configure it with an SMTP provider (Gmail, SendGrid, Mailgun, etc.).

**Q: Can I use a custom page template instead of the plugin template?**  
A: Yes. Create `single-apartment.php` in your theme folder. WordPress will prefer it over the plugin template.

**Q: How do I show all rooms (more than 8)?**  
A: Use `[explore_rooms limit="100"]` or link the Browse More button to a custom archive page.

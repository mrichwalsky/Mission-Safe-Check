# Mission Safe Check

Mission Safe Check is a WordPress plugin that helps site administrators monitor keyword activity across posts, pages, and optionally PDFs. Designed with nonprofits and small organizations in mind, it provides easy search, reporting, and notification tools right inside the WordPress dashboard.

---

## 🛠 Features

- 🔍 **Quick Search**: Instantly search your site content for keywords.
- ✅ **Saved Keywords**: Maintain a list of key terms to monitor.
- 📨 **Email Reports**: Send branded test reports to an email address.
- 📄 **PDF Scanning**: Optionally scan and index text inside uploaded PDF files.
- 📁 **CSV Export**: Download reports filtered by keyword and post type.
- ⚙️ **Admin Tools**: Re-index PDFs, customize post types, and toggle file scanning.
- 🧠 **Clean UI**: Designed to be intuitive and non-intrusive in the WordPress admin area.

---

## 📦 Installation

1. Download or clone the repository.
2. Place it in your `/wp-content/plugins/` directory.
3. Activate the plugin through the WordPress dashboard.

---

## 🔧 Usage

### 1. Admin Interface

After activation, navigate to:
**Admin > Mission Safe Check**

From here, you can:

- Search content quickly
- Add/remove saved keywords
- Export CSVs
- Trigger test emails
- Enable PDF scanning

### 2. Email Template

Emails sent use a simple HTML template with customizable inline styles and branding.

### 3. CSV Export

Export reports by selecting one or more keywords and filtering post types. Includes a timestamped file name.

---

## 📁 Optional PDF Scanning

- ✅ Enable this feature manually in the admin settings.
- 🗂 Creates a database table `wp_msc_media_index` to store PDF text.
- 🧠 Uses `smalot/pdfparser` (no external dependencies).

> ⚠️ Be aware this may increase storage and indexing time on large media libraries.

---

## 📚 Developer Notes

- Uses WordPress hooks and AJAX for interactivity.
- Clean separation of logic and UI: `admin-page.php`, `admin.js`, `admin.css`.
- Supports extensibility via standard WordPress functions.

---

## 🧪 Testing

You can:

- Run a test email with a custom address
- Trigger a manual PDF reindex
- Use the browser console to debug AJAX requests

---

## 🚫 Known Limitations

- DOC/DOCX file search is currently unsupported.
- PDF parsing may fail for poorly formatted or image-based PDFs.
- Does not support multisite by default.

---

## 📅 Changelog

**v1.0.0** — _Released on June 13, 2025_

- Initial release with core keyword search, CSV export, and email testing
- Added optional PDF scanning with local text extraction
- Custom post type and keyword filtering
- Admin interface with inline results, spinners, and feedback UI

---

## 🧑‍💻 Credits

Plugin created by [Mike Richwalsky](https://github.com/mrichwalsky)  
Part of the [Gas Mark 8](https://gasmark8.com) plugin ecosystem

---

## 📜 License

GPL 3.0 — Free for personal and commercial use.  
Please attribute when possible.

---

## 💡 Want More Features?

Open an issue or submit a pull request. Community contributions are welcome!

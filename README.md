# COMMON. — Open Source Classifieds

An account-free, privacy-first local classifieds bulletin board. Built because Facebook Marketplace is a data-harvesting maze riddled with corporate tracking pixels, and Craigslist is great but still looks like it was designed for a 90s terminal.

This platform does exactly one job: letting neighbors buy and sell things locally without profile passwords, tracking cookies, or tracking algorithms. 

---

## 🪵 Our Core Ethos (Why this isn't Depop or OfferUp)

Common is built to be a simple community utility, not a financial optimization game. 

* **No Side-Hustle Bloat:** This space is meant for cleaning out your garage and passing items along to neighbors—not optimizing a clothing resale business. There are no flashing notification badges, "trending tags," or fake countdown urgency loops.
* **Neighborhood-First Privacy:** Instead of forcing sellers to pin their exact street addresses or home location coordinates, the site draws an open-source neighborhood radius overlay. Buyers get an honest spatial understanding of the local area while keeping the seller's house secure.
* **0% Middleman Layer:** We do not tax, store, or process your peer-to-peer exchanges. The application sets up the communication bridge, leaving you to trade freely in person.

---

## ⚙️ How it Works (Under the Hood)

Instead of a database holding tracking profiles and password hashes, the site operates on a temporary transactional model.

1. **The Fast List:** A seller drops a few photos, sets a price, and registers a contact method. No email verification wait-times, no account activation steps.
2. **Permanent Passwordless Links:** When you publish, the site hands you a unique, private token link (e.g., `manage.php?key=8f3b9a12...`). This link is your absolute master key. Bookmark it to edit the price, mark the item as pending, or delete the post when it's sold. If you lose the link, you lose access to the post.
3. **Anonymized Reply Hub:** Buyers can click a clean, Craigslist-inspired reply tab panel on the listing. The platform generates an anonymized email alias (`reply-8f3b9a12@://slothscape.com`) and creates direct, one-click links to open Gmail, Yahoo, or Outlook in a new tab with your pre-filled inquiry.
4. **Scraper-Proof Payments:** Sellers can save their Venmo or Cash App handle securely in an encrypted backend file layer. The public listing page simply renders a cashless button. When tapped on mobile, it deep-links natively straight into the buyer's payment app with the amount pre-filled, so malicious scrapers can't steal usernames off the open web.
5. **Strictly Local Meetups:** Everything is sorted cleanly by localized ZIP codes. Transactions happen face-to-face using physical cash or peer-to-peer apps at safe swap zones, like a local library or police department parking lot.

---

## 🧱 Repository Structure

Built using lightweight procedural PHP and native vanilla CSS. No massive Node modules, no React bloat, and absolutely zero framework dependencies.

* `index.php` - Homepage featuring a streamlined search matrix, zip code range drop-downs, and a quick-access category matrix layout.
* `browse.php` - Wide two-column chronological feed with localized search filters and status condition tags.
* `listing.php` - Human-friendly individual detail view showcasing an active image slider, lightbox modals, anonymized messaging tabs, and an interactive open-source map.
* `header.php` - Unified side-by-side global layout controller housing the sliding "Manage Ad" key-verification drawer.
* `style.css` - Centralized design token stylesheet managing dark accent slate lines, focus borders, and clean status color states.

---

## 📍 Privacy-First Interactive Maps

The platform features an embedded interactive map built using **Leaflet.js** and **OpenStreetMap**. 
* **Zero Tracking:** Unlike Google Maps, it streams open-source geographic image tiles without injecting background tracking profiles, saving cookies, or requiring expensive API keys.
* **Dynamic Geocoding:** The application references localized ZIP code strings on the fly via a tracking-free lookup module, plotting an 800-meter blurred neighborhood circle overlay.

---

## 🚀 Running it Locally

Because the system uses standard PHP processing files, you can launch a local test runtime using any terminal environment:

```bash
# Clone the repository files
git clone https://github.com
cd my-marketplace-project

# Spin up a native local PHP web server
php -S localhost:8000
```
Open your browser and navigate to `http://localhost:8000` to view the running classifieds layout.

---

## 🤝 Current Development Goals

The layout mechanics and core frontend interface components are complete, responsive, and verified fluid. We are currently looking for contributions to lock down our automated backend handlers:

- [ ] **Automated Image Optimization:** Writing a backend image scaling workflow using the PHP GD library to automatically strip metadata and downsize massive smartphone photos to under 500KB on submission.
- [ ] **Data Encryption routines:** Ensuring private Venmo/Cash App file layers use robust OpenSSL encryption standards out of the box.
- [ ] **Smart Infrastructure Distance Calculations:** Integrating an open-source route matching system (like OSRM) instead of simple straight-line distance math to prevent geographical barriers (like lakes or rivers) from polluting local range results.

Feel free to fork the codebase, add enhancements, and send over a pull request!
